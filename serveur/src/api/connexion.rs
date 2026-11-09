//! Connexion par code (CP-01, CP-06, CP-07), reprise de `AuthController` :
//! demande d'un code, vérification, déconnexion. Les réponses et les erreurs
//! ont la forme de celles de Laravel, pour l'application Flutter.

use axum::Json;
use axum::extract::State;
use axum::http::StatusCode;
use serde::Serialize;
use serde_json::{Value, json};

use super::authentification::{AdresseClient, MembreConnecte};
use super::ressources::Profil;
use super::validation::{Champ, Corps, ErreursValidation, est_email, texte_facultatif};
use crate::config::LONGUEUR_CODE;
use crate::config::limites::{
    CODES_PAR_DESTINATAIRE, CODES_PAR_IP, HEURE, MINUTE, VERIFICATIONS_PAR_IP,
};
use crate::domaine::envoi::ErreurEnvoi;
use crate::domaine::telephone::{est_valide, normaliser};
use crate::domaine::{codes, jetons};
use crate::erreurs::ErreurApp;
use crate::etat::EtatApp;
use crate::limites::Limite;

const IDENTIFIANT_REQUIS: &str = "Indiquez votre numéro de téléphone ou votre adresse e-mail.";
const TELEPHONE_INVALIDE: &str =
    "Numéro invalide. Exemple : 77 123 45 67, ou +33 6 12 34 56 78 depuis l'étranger.";

/// `POST /api/auth/code` : envoie un code au numéro (WhatsApp) ou à l'adresse e-mail.
pub async fn demander_code(
    State(etat): State<EtatApp>,
    AdresseClient(ip): AdresseClient,
    corps: Corps,
) -> Result<(StatusCode, Json<Value>), ErreurApp> {
    // Limites `throttle:otp`, comptées avant la validation, comme dans Laravel.
    etat.limites
        .essayer(&[
            Limite::new(
                format!("otp:code:{}", cle_destinataire(&corps)),
                CODES_PAR_DESTINATAIRE,
                MINUTE,
            ),
            Limite::new(format!("otp:ip:{ip}"), CODES_PAR_IP, HEURE),
        ])
        .map_err(|attente| ErreurApp::TropDeTentatives { attente })?;

    let mut erreurs = ErreursValidation::default();
    let identifiant = identifiant(&corps, &mut erreurs);
    let canal = match corps.champ("channel") {
        Champ::Absent => None,
        Champ::Texte(canal) if canal == "whatsapp" || canal == "sms" => Some(canal),
        _ => {
            erreurs.ajouter("channel", "Choisissez WhatsApp ou SMS.");
            None
        }
    };
    texte_facultatif(
        &corps,
        &mut erreurs,
        "sms_purchase_id",
        "L'achat de SMS",
        40,
    );
    let Some(identifiant) = erreurs.ou(identifiant)? else {
        return Err(ErreurApp::Interne(
            "identifiant validé mais absent".to_owned(),
        ));
    };

    // CP-07 : le SMS payé d'avance passe encore par Laravel.
    if canal.as_deref() == Some("sms") && !identifiant.contains('@') {
        return Err(ErreurApp::Indisponible(
            "Le SMS payant n'est pas encore pris en charge par ce serveur. Choisissez WhatsApp ou l'e-mail."
                .to_owned(),
        ));
    }

    etat.expediteur
        .verifier_canal(&etat.config.codes, &identifiant)
        .map_err(|ErreurEnvoi::CanalNonPrisEnCharge(canal)| {
            ErreurApp::Indisponible(format!(
                "L'envoi des codes par « {canal} » n'est pas encore pris en charge par ce serveur."
            ))
        })?;

    let code = codes::emettre(
        &etat.base,
        &etat.config.codes,
        etat.config.cout_bcrypt,
        &identifiant,
    )
    .await?;

    if let Err(erreur) = etat.expediteur.envoyer(&identifiant, &code).await {
        tracing::error!(?erreur, "code de connexion non envoyé");
        return Err(ErreurApp::Validation(ErreursValidation::un(
            "phone",
            "Impossible d'envoyer le code pour le moment. Réessayez dans quelques minutes.",
        )));
    }

    let minutes = etat.config.codes.validite.as_secs() / 60;
    Ok((
        StatusCode::ACCEPTED,
        Json(json!({
            "message": format!("Un code de connexion vous a été envoyé. Il est valable {minutes} minutes."),
        })),
    ))
}

#[derive(Debug, Serialize)]
pub struct ReponseConnexion {
    pub token: String,
    pub user: Profil,
}

/// `POST /api/auth/verify` : vérifie le code, crée le membre à sa première
/// connexion et remet un jeton à l'appareil.
pub async fn verifier_code(
    State(etat): State<EtatApp>,
    AdresseClient(ip): AdresseClient,
    corps: Corps,
) -> Result<Json<ReponseConnexion>, ErreurApp> {
    etat.limites
        .essayer(&[Limite::new(
            format!("otp-verify:{ip}"),
            VERIFICATIONS_PAR_IP,
            MINUTE,
        )])
        .map_err(|attente| ErreurApp::TropDeTentatives { attente })?;

    let mut erreurs = ErreursValidation::default();
    let identifiant = identifiant(&corps, &mut erreurs);
    let code = match corps.champ("code") {
        Champ::Absent => {
            erreurs.ajouter("code", "Saisissez le code reçu.");
            None
        }
        Champ::AutreType => {
            erreurs.ajouter("code", "Le code doit être un texte.");
            None
        }
        Champ::Texte(code)
            if code.len() != LONGUEUR_CODE || !code.bytes().all(|c| c.is_ascii_digit()) =>
        {
            erreurs.ajouter(
                "code",
                format!("Le code contient {LONGUEUR_CODE} chiffres."),
            );
            None
        }
        Champ::Texte(code) => Some(code),
    };
    let nom = texte_facultatif(&corps, &mut erreurs, "name", "Le nom", 120);
    let appareil = texte_facultatif(
        &corps,
        &mut erreurs,
        "device_name",
        "Le nom de l'appareil",
        120,
    );
    let (Some(identifiant), Some(code)) = erreurs.ou((identifiant, code))? else {
        return Err(ErreurApp::Interne("code validé mais absent".to_owned()));
    };

    let membre = codes::verifier(
        &etat.base,
        &etat.config.codes,
        &identifiant,
        &code,
        nom.as_deref(),
    )
    .await?;

    let token = jetons::creer(
        &etat.base,
        &membre,
        appareil.as_deref().unwrap_or("application"),
        &etat.config.prefixe_jetons,
        etat.config.sessions.duree_max_jours,
    )
    .await?;
    let user = Profil::charger(&etat.base, &etat.config.url_publique, &membre).await?;

    Ok(Json(ReponseConnexion { token, user }))
}

/// `POST /api/auth/logout` : déconnecte l'appareil du jeton présenté.
pub async fn deconnecter(
    State(etat): State<EtatApp>,
    membre: MembreConnecte,
) -> Result<StatusCode, ErreurApp> {
    jetons::supprimer(&etat.base, membre.jeton_id).await?;
    Ok(StatusCode::NO_CONTENT)
}

/// Numéro ou adresse validés, comme `PhoneRequest` : le champ `phone`
/// accepte aussi une adresse e-mail, et un numéro est normalisé avec le pays
/// choisi (`country`) avant d'être vérifié.
fn identifiant(corps: &Corps, erreurs: &mut ErreursValidation) -> Option<String> {
    let pays = corps.champ("country");
    let (numero, adresse) = match corps.champ("phone") {
        Champ::Texte(saisie) if saisie.contains('@') => {
            (Champ::Absent, Champ::Texte(saisie.to_lowercase()))
        }
        Champ::Texte(saisie) => (
            Champ::Texte(normaliser(&saisie, pays.texte()).unwrap_or(saisie)),
            corps.champ("email"),
        ),
        autre => (autre, corps.champ("email")),
    };
    let adresse = match adresse {
        Champ::Texte(adresse) => Champ::Texte(adresse.to_lowercase()),
        autre => autre,
    };

    match &numero {
        Champ::Absent if adresse.est_absent() => erreurs.ajouter("phone", IDENTIFIANT_REQUIS),
        Champ::AutreType => erreurs.ajouter("phone", "Le numéro de téléphone doit être un texte."),
        Champ::Texte(numero) if !est_valide(numero) => {
            erreurs.ajouter("phone", TELEPHONE_INVALIDE);
        }
        _ => {}
    }
    match &adresse {
        Champ::Absent if numero.est_absent() => erreurs.ajouter(
            "email",
            "Indiquez une adresse e-mail si vous ne donnez pas de numéro.",
        ),
        Champ::AutreType => erreurs.ajouter("email", "L'adresse e-mail doit être un texte."),
        Champ::Texte(adresse) => {
            if !est_email(adresse) {
                erreurs.ajouter("email", "Adresse e-mail invalide.");
            }
            if adresse.chars().count() > 255 {
                erreurs.ajouter(
                    "email",
                    "L'adresse e-mail ne doit pas dépasser 255 caractères.",
                );
            }
        }
        Champ::Absent => {}
    }

    numero.texte().or(adresse.texte()).map(str::to_owned)
}

/// Clé de la limite par destinataire : `phone`, sinon `email`, tels que reçus.
fn cle_destinataire(corps: &Corps) -> String {
    [corps.champ("phone"), corps.champ("email")]
        .into_iter()
        .find_map(|champ| match champ {
            Champ::Texte(texte) => Some(texte),
            _ => None,
        })
        .unwrap_or_default()
}

#[cfg(test)]
mod tests {
    use super::*;

    fn valider(json: &str) -> (Option<String>, ErreursValidation) {
        let mut erreurs = ErreursValidation::default();
        let identifiant = identifiant(&Corps::depuis_octets(json.as_bytes()), &mut erreurs);
        (identifiant, erreurs)
    }

    #[test]
    fn normalise_un_numero_senegalais() {
        let (identifiant, erreurs) = valider(r#"{"phone": "77 123 45 67"}"#);

        assert!(erreurs.est_vide());
        assert_eq!(identifiant.as_deref(), Some("+221771234567"));
    }

    #[test]
    fn utilise_le_pays_choisi() {
        let (identifiant, _) = valider(r#"{"phone": "06 12 34 56 78", "country": "FR"}"#);

        assert_eq!(identifiant.as_deref(), Some("+33612345678"));
    }

    #[test]
    fn le_champ_telephone_accepte_une_adresse() {
        let (identifiant, erreurs) = valider(r#"{"phone": " Awa@Exemple.SN "}"#);

        assert!(erreurs.est_vide());
        assert_eq!(identifiant.as_deref(), Some("awa@exemple.sn"));
    }

    #[test]
    fn demande_un_numero_ou_une_adresse() {
        let (identifiant, erreurs) = valider("{}");

        assert_eq!(identifiant, None);
        assert_eq!(erreurs.premier_message(), IDENTIFIANT_REQUIS);
        assert!(erreurs.messages("email").is_some());
    }

    #[test]
    fn refuse_un_numero_invalide() {
        let (_, erreurs) = valider(r#"{"phone": "1234"}"#);

        assert_eq!(
            erreurs.messages("phone"),
            Some([TELEPHONE_INVALIDE.to_owned()].as_slice())
        );
    }

    #[test]
    fn refuse_une_adresse_invalide() {
        let (_, erreurs) = valider(r#"{"email": "awa@"}"#);

        assert_eq!(
            erreurs.messages("email"),
            Some(["Adresse e-mail invalide.".to_owned()].as_slice())
        );
    }
}
