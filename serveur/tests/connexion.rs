//! Connexion par code et jetons, de bout en bout sur une base MySQL neuve.
//! Ces tests reprennent ceux de Laravel (`AuthTest`, `EmailLoginTest`) et
//! vérifient que les jetons passent d'un serveur à l'autre.

mod commun;

use axum::http::StatusCode;
use commun::Banc;
use idee_serveur::domaine::jetons;
use serde_json::{Value, json};
use sqlx::MySqlPool;

async fn creer_membre(base: &MySqlPool, telephone: Option<&str>, email: Option<&str>) -> String {
    let id = ulid::Ulid::generate().to_string().to_lowercase();
    sqlx::query(
        "INSERT INTO users (id, name, phone, email, phone_verified_at, email_verified_at, \
         verification_level, created_at, updated_at) \
         VALUES (?, 'Membre test', ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP(), 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())",
    )
    .bind(&id)
    .bind(telephone)
    .bind(email)
    .execute(base)
    .await
    .unwrap();
    id
}

async fn nombre(base: &MySqlPool, requete: &'static str) -> i64 {
    sqlx::query_scalar(requete).fetch_one(base).await.unwrap()
}

fn premiere_erreur(corps: &Value, champ: &str) -> String {
    corps["errors"][champ][0]
        .as_str()
        .unwrap_or_default()
        .to_owned()
}

#[sqlx::test(migrations = "tests/schema")]
async fn un_nouveau_membre_s_inscrit_avec_un_code(base: MySqlPool) {
    let banc = Banc::new(base);

    let (statut, corps) = banc
        .post("/api/auth/code", json!({ "phone": "77 123 45 67" }))
        .await;
    assert_eq!(statut, StatusCode::ACCEPTED);
    assert_eq!(
        corps["message"],
        "Un code de connexion vous a été envoyé. Il est valable 10 minutes."
    );

    let code = banc.code("+221771234567");
    let (statut, corps) = banc
        .post(
            "/api/auth/verify",
            json!({ "phone": "77 123 45 67", "code": code, "name": "Aminata Diop" }),
        )
        .await;

    assert_eq!(statut, StatusCode::OK);
    assert_eq!(corps["user"]["name"], "Aminata Diop");
    assert_eq!(corps["user"]["phone"], "+221771234567");
    assert_eq!(corps["user"]["verification"]["value"], 1);
    assert!(!corps["token"].as_str().unwrap().is_empty());
    assert_eq!(
        nombre(
            &banc.base,
            "SELECT COUNT(*) FROM users WHERE phone = '+221771234567' AND phone_verified_at IS NOT NULL"
        )
        .await,
        1
    );
}

#[sqlx::test(migrations = "tests/schema")]
async fn un_membre_existant_se_connecte_sans_doublon(base: MySqlPool) {
    let id = creer_membre(&base, Some("+221770000001"), None).await;
    let banc = Banc::new(base);

    let (statut, corps) = banc.connecter("+221770000001", None).await;

    assert_eq!(statut, StatusCode::OK);
    assert_eq!(corps["user"]["id"], id.as_str());
    assert_eq!(nombre(&banc.base, "SELECT COUNT(*) FROM users").await, 1);
}

#[sqlx::test(migrations = "tests/schema")]
async fn un_mauvais_code_est_refuse_et_compte_comme_un_essai(base: MySqlPool) {
    let banc = Banc::new(base);
    banc.post("/api/auth/code", json!({ "phone": "+221770000002" }))
        .await;

    let (statut, corps) = banc
        .post(
            "/api/auth/verify",
            json!({ "phone": "+221770000002", "code": banc.mauvais_code("+221770000002") }),
        )
        .await;

    assert_eq!(statut, StatusCode::UNPROCESSABLE_ENTITY);
    assert_eq!(
        premiere_erreur(&corps, "code"),
        "Code incorrect. Il vous reste 4 essai(s)."
    );
    assert_eq!(corps["message"], corps["errors"]["code"][0]);
}

#[sqlx::test(migrations = "tests/schema")]
async fn le_code_est_refuse_apres_cinq_mauvais_essais(base: MySqlPool) {
    let banc = Banc::new(base);
    banc.post("/api/auth/code", json!({ "phone": "+221770000003" }))
        .await;
    let mauvais = banc.mauvais_code("+221770000003");

    for _ in 0..5 {
        banc.post(
            "/api/auth/verify",
            json!({ "phone": "+221770000003", "code": mauvais }),
        )
        .await;
    }
    let (statut, corps) = banc
        .post(
            "/api/auth/verify",
            json!({ "phone": "+221770000003", "code": banc.code("+221770000003") }),
        )
        .await;

    assert_eq!(statut, StatusCode::UNPROCESSABLE_ENTITY);
    assert!(premiere_erreur(&corps, "code").contains("expiré"));
}

#[sqlx::test(migrations = "tests/schema")]
async fn un_code_expire_est_refuse(base: MySqlPool) {
    let banc = Banc::new(base);
    banc.post("/api/auth/code", json!({ "phone": "+221770000004" }))
        .await;
    sqlx::query("UPDATE one_time_codes SET expires_at = UTC_TIMESTAMP() - INTERVAL 1 MINUTE")
        .execute(&banc.base)
        .await
        .unwrap();

    let (statut, corps) = banc
        .post(
            "/api/auth/verify",
            json!({ "phone": "+221770000004", "code": banc.code("+221770000004") }),
        )
        .await;

    assert_eq!(statut, StatusCode::UNPROCESSABLE_ENTITY);
    assert!(premiere_erreur(&corps, "code").contains("expiré"));
}

#[sqlx::test(migrations = "tests/schema")]
async fn un_numero_invalide_est_refuse(base: MySqlPool) {
    let banc = Banc::new(base);

    let (statut, corps) = banc
        .post("/api/auth/code", json!({ "phone": "1234" }))
        .await;

    assert_eq!(statut, StatusCode::UNPROCESSABLE_ENTITY);
    assert!(premiere_erreur(&corps, "phone").starts_with("Numéro invalide."));
}

#[sqlx::test(migrations = "tests/schema")]
async fn un_second_code_dans_la_minute_est_refuse(base: MySqlPool) {
    let banc = Banc::new(base);

    let (premier, _) = banc
        .post("/api/auth/code", json!({ "phone": "+221770000006" }))
        .await;
    let (second, corps) = banc
        .post("/api/auth/code", json!({ "phone": "+221770000006" }))
        .await;

    assert_eq!(premier, StatusCode::ACCEPTED);
    assert_eq!(second, StatusCode::TOO_MANY_REQUESTS);
    assert!(
        corps["message"]
            .as_str()
            .unwrap()
            .starts_with("Trop de tentatives")
    );
}

#[sqlx::test(migrations = "tests/schema")]
async fn le_sms_payant_passe_encore_par_laravel(base: MySqlPool) {
    let banc = Banc::new(base);

    let (statut, _) = banc
        .post(
            "/api/auth/code",
            json!({ "phone": "+221770000007", "channel": "sms" }),
        )
        .await;

    assert_eq!(statut, StatusCode::SERVICE_UNAVAILABLE);
    assert_eq!(
        nombre(&banc.base, "SELECT COUNT(*) FROM one_time_codes").await,
        0
    );
}

#[sqlx::test(migrations = "tests/schema")]
async fn le_code_est_garde_hache_au_format_de_php(base: MySqlPool) {
    let banc = Banc::new(base);
    banc.post("/api/auth/code", json!({ "phone": "+221770000008" }))
        .await;

    let empreinte: String = sqlx::query_scalar("SELECT code_hash FROM one_time_codes")
        .fetch_one(&banc.base)
        .await
        .unwrap();

    assert!(empreinte.starts_with("$2y$04$"));
    assert!(!empreinte.contains(&banc.code("+221770000008")));
}

#[sqlx::test(migrations = "tests/schema")]
async fn un_membre_s_inscrit_avec_une_adresse_e_mail(base: MySqlPool) {
    let banc = Banc::new(base);
    banc.post("/api/auth/code", json!({ "email": "Awa.Diop@Exemple.SN" }))
        .await;

    let (statut, corps) = banc
        .post(
            "/api/auth/verify",
            json!({ "email": "awa.diop@exemple.sn", "code": banc.code("awa.diop@exemple.sn") }),
        )
        .await;

    assert_eq!(statut, StatusCode::OK);
    assert_eq!(corps["user"]["email"], "awa.diop@exemple.sn");
    assert_eq!(corps["user"]["phone"], Value::Null);
    assert_eq!(corps["user"]["name"], "Membre IDEE");
    assert_eq!(corps["user"]["verification"]["value"], 0);
}

#[sqlx::test(migrations = "tests/schema")]
async fn une_adresse_non_confirmee_n_ouvre_pas_le_compte(base: MySqlPool) {
    let id = creer_membre(&base, Some("+221770000009"), Some("fatou@exemple.sn")).await;
    sqlx::query("UPDATE users SET email_verified_at = NULL WHERE id = ?")
        .bind(&id)
        .execute(&base)
        .await
        .unwrap();
    let banc = Banc::new(base);
    banc.post("/api/auth/code", json!({ "phone": "fatou@exemple.sn" }))
        .await;

    let (statut, corps) = banc
        .post(
            "/api/auth/verify",
            json!({ "email": "fatou@exemple.sn", "code": banc.code("fatou@exemple.sn") }),
        )
        .await;

    assert_eq!(statut, StatusCode::UNPROCESSABLE_ENTITY);
    assert!(premiere_erreur(&corps, "code").contains("pas encore confirmée"));
    // Le refus annule tout : le code n'est pas consommé.
    assert_eq!(
        nombre(
            &banc.base,
            "SELECT COUNT(*) FROM one_time_codes WHERE consumed_at IS NULL"
        )
        .await,
        1
    );
}

#[sqlx::test(migrations = "tests/schema")]
async fn un_compte_exclu_ne_peut_pas_se_connecter(base: MySqlPool) {
    let id = creer_membre(&base, Some("+221770000010"), None).await;
    sqlx::query("UPDATE users SET banned_at = UTC_TIMESTAMP() WHERE id = ?")
        .bind(&id)
        .execute(&base)
        .await
        .unwrap();
    let banc = Banc::new(base);

    let (statut, corps) = banc.connecter("+221770000010", None).await;

    assert_eq!(statut, StatusCode::UNPROCESSABLE_ENTITY);
    assert!(premiere_erreur(&corps, "code").contains("exclu"));
}

#[sqlx::test(migrations = "tests/schema")]
async fn un_compte_supprime_revient_a_la_connexion(base: MySqlPool) {
    let id = creer_membre(&base, Some("+221770000011"), None).await;
    sqlx::query("UPDATE users SET deleted_at = UTC_TIMESTAMP() WHERE id = ?")
        .bind(&id)
        .execute(&base)
        .await
        .unwrap();
    let banc = Banc::new(base);

    let (statut, corps) = banc.connecter("+221770000011", None).await;

    assert_eq!(statut, StatusCode::OK);
    assert_eq!(corps["user"]["id"], id.as_str());
    assert_eq!(
        nombre(
            &banc.base,
            "SELECT COUNT(*) FROM users WHERE deleted_at IS NULL"
        )
        .await,
        1
    );
}

#[sqlx::test(migrations = "tests/schema")]
async fn le_profil_renvoye_a_la_forme_de_laravel(base: MySqlPool) {
    let banc = Banc::new(base);

    let (_, corps) = banc.connecter("+221770000012", Some("Moussa Ndiaye")).await;
    let profil = corps["user"].as_object().unwrap();
    let cles: Vec<&str> = profil.keys().map(String::as_str).collect();

    let mut attendues = vec![
        "id",
        "name",
        "region",
        "photo_url",
        "availability",
        "bio",
        "expertise",
        "rate",
        "languages",
        "skills",
        "sectors",
        "roles",
        "verification",
        "member_since",
        "phone",
        "email",
        "contact_preferences",
        "contact_requests_sent_this_week",
        "terms_accepted",
    ];
    let mut cles_triees = cles.clone();
    cles_triees.sort_unstable();
    attendues.sort_unstable();
    assert_eq!(cles_triees, attendues);

    assert_eq!(profil["languages"], json!([]));
    assert_eq!(profil["roles"], json!([]));
    assert_eq!(
        profil["contact_preferences"],
        json!({ "accepts_requests": true })
    );
    assert_eq!(profil["verification"]["label"], "Téléphone vérifié");
    assert_eq!(profil["terms_accepted"], false);
}

#[sqlx::test(migrations = "tests/schema")]
async fn le_jeton_remis_est_lisible_par_laravel(base: MySqlPool) {
    let banc = Banc::new(base);

    let (_, corps) = banc.connecter("+221770000013", None).await;
    let jeton = corps["token"].as_str().unwrap();
    let (id, secret) = jeton.split_once('|').unwrap();

    let (type_, empreinte, jours): (String, String, i64) = sqlx::query_as(
        "SELECT tokenable_type, token, DATEDIFF(expires_at, created_at) \
         FROM personal_access_tokens WHERE id = ?",
    )
    .bind(id.parse::<u64>().unwrap())
    .fetch_one(&banc.base)
    .await
    .unwrap();

    // Laravel retrouve le jeton par son numéro, puis compare l'empreinte SHA-256 du secret.
    assert_eq!(type_, "App\\Models\\User");
    assert_eq!(empreinte, jetons::empreinte(secret));
    // Une session dure un an au plus (CP-06).
    assert_eq!(jours, 365);
}

#[sqlx::test(migrations = "tests/schema")]
async fn un_jeton_cree_par_laravel_est_accepte(base: MySqlPool) {
    let id = creer_membre(&base, Some("+221770000014"), None).await;
    let insertion = sqlx::query(
        "INSERT INTO personal_access_tokens (tokenable_type, tokenable_id, name, token, abilities, \
         expires_at, created_at, updated_at) VALUES ('App\\\\Models\\\\User', ?, 'application', ?, '[\"*\"]', \
         UTC_TIMESTAMP() + INTERVAL 1 YEAR, UTC_TIMESTAMP(), UTC_TIMESTAMP())",
    )
    .bind(&id)
    .bind(jetons::empreinte("secret-cree-par-laravel"))
    .execute(&base)
    .await
    .unwrap();
    let banc = Banc::new(base);

    let (statut, _) = banc
        .post_avec_jeton(
            "/api/auth/logout",
            &format!("{}|secret-cree-par-laravel", insertion.last_insert_id()),
        )
        .await;

    assert_eq!(statut, StatusCode::NO_CONTENT);
}

#[sqlx::test(migrations = "tests/schema")]
async fn la_deconnexion_supprime_le_jeton(base: MySqlPool) {
    let banc = Banc::new(base);
    let (_, corps) = banc.connecter("+221770000015", None).await;
    let jeton = corps["token"].as_str().unwrap();

    let (premiere, _) = banc.post_avec_jeton("/api/auth/logout", jeton).await;
    let (seconde, _) = banc.post_avec_jeton("/api/auth/logout", jeton).await;

    assert_eq!(premiere, StatusCode::NO_CONTENT);
    assert_eq!(seconde, StatusCode::UNAUTHORIZED);
}

#[sqlx::test(migrations = "tests/schema")]
async fn un_jeton_inconnu_ou_absent_est_refuse(base: MySqlPool) {
    let banc = Banc::new(base);

    let (inconnu, corps) = banc.post_avec_jeton("/api/auth/logout", "999|faux").await;
    let (absent, _) = banc.post("/api/auth/logout", json!({})).await;

    assert_eq!(inconnu, StatusCode::UNAUTHORIZED);
    assert_eq!(corps["message"], "Non authentifié.");
    assert_eq!(absent, StatusCode::UNAUTHORIZED);
}

#[sqlx::test(migrations = "tests/schema")]
async fn une_session_finit_apres_quatre_vingt_dix_jours_sans_usage(base: MySqlPool) {
    let id = creer_membre(&base, Some("+221770000016"), None).await;
    let recent = jetons::creer(&base, &id, "récent", "", 365).await.unwrap();
    let oublie = jetons::creer(&base, &id, "oublié", "", 365).await.unwrap();
    for (jeton, jours) in [(&recent, 80), (&oublie, 91)] {
        sqlx::query(
            "UPDATE personal_access_tokens SET last_used_at = UTC_TIMESTAMP() - INTERVAL ? DAY WHERE id = ?",
        )
        .bind(jours)
        .bind(jeton.split_once('|').unwrap().0.parse::<u64>().unwrap())
        .execute(&base)
        .await
        .unwrap();
    }
    let banc = Banc::new(base);

    let (statut_recent, _) = banc.post_avec_jeton("/api/auth/logout", &recent).await;
    let (statut_oublie, _) = banc.post_avec_jeton("/api/auth/logout", &oublie).await;

    assert_eq!(statut_recent, StatusCode::NO_CONTENT);
    assert_eq!(statut_oublie, StatusCode::UNAUTHORIZED);
}

#[sqlx::test(migrations = "tests/schema")]
async fn une_session_ne_dure_jamais_plus_d_un_an(base: MySqlPool) {
    let id = creer_membre(&base, Some("+221770000017"), None).await;
    let jeton = jetons::creer(&base, &id, "application", "", 365)
        .await
        .unwrap();
    sqlx::query(
        "UPDATE personal_access_tokens SET expires_at = UTC_TIMESTAMP() - INTERVAL 1 SECOND, \
         last_used_at = UTC_TIMESTAMP()",
    )
    .execute(&base)
    .await
    .unwrap();
    let banc = Banc::new(base);

    let (statut, _) = banc.post_avec_jeton("/api/auth/logout", &jeton).await;

    assert_eq!(statut, StatusCode::UNAUTHORIZED);
}
