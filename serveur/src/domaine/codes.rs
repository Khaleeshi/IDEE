//! Connexion par code à usage unique (CP-01, § 10), reprise de
//! `OneTimeCodeService`. Le code a 6 chiffres, vaut 10 minutes et accepte
//! 5 essais ; seule son empreinte bcrypt est enregistrée. Le membre est créé
//! à sa première connexion.

use chrono::{DateTime, TimeDelta, Utc};
use sqlx::MySqlPool;
use ulid::Ulid;

use super::membres::{Moderation, NIVEAU_TELEPHONE};
use super::{ErreurDomaine, maintenant};
use crate::config::{LONGUEUR_CODE, ReglesCodes};

const CODE_EXPIRE: &str = "Ce code a expiré ou n'est plus valable. Demandez un nouveau code.";
const ADRESSE_NON_CONFIRMEE: &str = "Cette adresse n'est pas encore confirmée. Connectez-vous avec votre téléphone, puis confirmez-la depuis votre profil.";
const NOM_PAR_DEFAUT: &str = "Membre IDEE";

/// Crée un nouveau code pour ce numéro ou cette adresse, et rend le code en
/// clair pour l'envoyer. Les codes précédents ne servent plus.
pub async fn emettre(
    base: &MySqlPool,
    regles: &ReglesCodes,
    cout_bcrypt: u32,
    identifiant: &str,
) -> Result<String, ErreurDomaine> {
    let instant = maintenant();
    sqlx::query(
        "UPDATE one_time_codes SET consumed_at = ?, updated_at = ? \
         WHERE identifier = ? AND consumed_at IS NULL",
    )
    .bind(instant)
    .bind(instant)
    .bind(identifiant)
    .execute(base)
    .await?;

    let plafond = 10_u32.pow(LONGUEUR_CODE as u32);
    let code = format!(
        "{:0largeur$}",
        rand::random_range(0..plafond),
        largeur = LONGUEUR_CODE
    );
    let empreinte = hacher(code.clone(), cout_bcrypt).await?;
    let validite = TimeDelta::from_std(regles.validite)
        .map_err(|_| ErreurDomaine::Interne("durée de validité du code trop grande".to_owned()))?;

    sqlx::query(
        "INSERT INTO one_time_codes (identifier, code_hash, attempts, expires_at, created_at, updated_at) \
         VALUES (?, ?, 0, ?, ?, ?)",
    )
    .bind(identifiant)
    .bind(empreinte)
    .bind(instant + validite)
    .bind(instant)
    .bind(instant)
    .execute(base)
    .await?;

    Ok(code)
}

#[derive(sqlx::FromRow)]
struct LigneCode {
    id: u64,
    code_hash: String,
    attempts: u8,
    expires_at: DateTime<Utc>,
}

#[derive(sqlx::FromRow)]
struct LigneMembre {
    id: String,
    email_verified_at: Option<DateTime<Utc>>,
    phone_verified_at: Option<DateTime<Utc>>,
    verification_level: u8,
    deleted_at: Option<DateTime<Utc>>,
    banned_at: Option<DateTime<Utc>>,
    precautionary_suspension_at: Option<DateTime<Utc>>,
    suspended_until: Option<DateTime<Utc>>,
}

/// Vérifie le code et rend l'identifiant du membre, créé à sa première
/// connexion. Un refus est rattaché au champ `code`.
pub async fn verifier(
    base: &MySqlPool,
    regles: &ReglesCodes,
    identifiant: &str,
    code: &str,
    nom: Option<&str>,
) -> Result<String, ErreurDomaine> {
    let ligne: Option<LigneCode> = sqlx::query_as(
        "SELECT id, code_hash, attempts, expires_at FROM one_time_codes \
         WHERE identifier = ? AND consumed_at IS NULL ORDER BY id DESC LIMIT 1",
    )
    .bind(identifiant)
    .fetch_optional(base)
    .await?;

    let instant = maintenant();
    let Some(ligne) =
        ligne.filter(|l| l.expires_at > instant && u32::from(l.attempts) < regles.essais_max)
    else {
        return Err(ErreurDomaine::refus("code", CODE_EXPIRE));
    };

    sqlx::query("UPDATE one_time_codes SET attempts = attempts + 1, updated_at = ? WHERE id = ?")
        .bind(instant)
        .bind(ligne.id)
        .execute(base)
        .await?;

    if !correspond(code.to_owned(), ligne.code_hash).await? {
        let restants = regles
            .essais_max
            .saturating_sub(u32::from(ligne.attempts) + 1);
        return Err(ErreurDomaine::refus(
            "code",
            if restants > 0 {
                format!("Code incorrect. Il vous reste {restants} essai(s).")
            } else {
                "Code incorrect. Demandez un nouveau code.".to_owned()
            },
        ));
    }

    // Un refus après ce point annule tout, y compris l'usage du code.
    let mut transaction = base.begin().await?;

    sqlx::query("UPDATE one_time_codes SET consumed_at = ?, updated_at = ? WHERE id = ?")
        .bind(instant)
        .bind(instant)
        .bind(ligne.id)
        .execute(&mut *transaction)
        .await?;

    let par_email = identifiant.contains('@');
    let requete = if par_email {
        "SELECT id, email_verified_at, phone_verified_at, verification_level, deleted_at, banned_at, \
         precautionary_suspension_at, suspended_until FROM users WHERE email = ? LIMIT 1 FOR UPDATE"
    } else {
        "SELECT id, email_verified_at, phone_verified_at, verification_level, deleted_at, banned_at, \
         precautionary_suspension_at, suspended_until FROM users WHERE phone = ? LIMIT 1 FOR UPDATE"
    };
    let membre: Option<LigneMembre> = sqlx::query_as(requete)
        .bind(identifiant)
        .fetch_optional(&mut *transaction)
        .await?;

    let id = match membre {
        Some(membre) => {
            // Une adresse ajoutée au profil mais jamais confirmée n'ouvre pas le compte.
            if par_email && membre.email_verified_at.is_none() {
                return Err(ErreurDomaine::refus("code", ADRESSE_NON_CONFIRMEE));
            }

            let moderation = Moderation {
                banni_le: membre.banned_at,
                suspendu_par_precaution_le: membre.precautionary_suspension_at,
                suspendu_jusqu_au: membre.suspended_until,
            };
            if let Some(message) = moderation.restriction(instant) {
                return Err(ErreurDomaine::refus("code", message));
            }

            let (email_verifie, telephone_verifie, niveau) = if par_email {
                (
                    membre.email_verified_at.or(Some(instant)),
                    membre.phone_verified_at,
                    membre.verification_level,
                )
            } else {
                (
                    membre.email_verified_at,
                    membre.phone_verified_at.or(Some(instant)),
                    membre.verification_level.max(NIVEAU_TELEPHONE),
                )
            };

            // Un compte supprimé revient à la connexion ; sans changement, rien n'est écrit.
            if membre.deleted_at.is_some()
                || email_verifie != membre.email_verified_at
                || telephone_verifie != membre.phone_verified_at
                || niveau != membre.verification_level
            {
                sqlx::query(
                    "UPDATE users SET deleted_at = NULL, email_verified_at = ?, phone_verified_at = ?, \
                     verification_level = ?, updated_at = ? WHERE id = ?",
                )
                .bind(email_verifie)
                .bind(telephone_verifie)
                .bind(niveau)
                .bind(instant)
                .bind(&membre.id)
                .execute(&mut *transaction)
                .await?;
            }

            membre.id
        }
        None => {
            // Comme Laravel (HasUlids), l'identifiant est un ULID en minuscules.
            let id = Ulid::generate().to_string().to_lowercase();
            let nom = nom.filter(|n| !n.is_empty()).unwrap_or(NOM_PAR_DEFAUT);
            sqlx::query(
                "INSERT INTO users (id, name, phone, email, phone_verified_at, email_verified_at, \
                 verification_level, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            )
            .bind(&id)
            .bind(nom)
            .bind((!par_email).then_some(identifiant))
            .bind(par_email.then_some(identifiant))
            .bind((!par_email).then_some(instant))
            .bind(par_email.then_some(instant))
            .bind(if par_email { 0 } else { NIVEAU_TELEPHONE })
            .bind(instant)
            .bind(instant)
            .execute(&mut *transaction)
            .await?;
            id
        }
    };

    transaction.commit().await?;
    Ok(id)
}

/// Empreinte bcrypt au format `$2y$` de PHP, pour que Laravel puisse aussi
/// vérifier les codes émis par le serveur Rust.
async fn hacher(code: String, cout: u32) -> Result<String, ErreurDomaine> {
    tokio::task::spawn_blocking(move || {
        bcrypt::hash_with_result(code, cout)
            .map(|parts| parts.format_for_version(bcrypt::Version::TwoY))
    })
    .await
    .map_err(|erreur| ErreurDomaine::Interne(format!("hachage interrompu : {erreur}")))?
    .map_err(|erreur| ErreurDomaine::Interne(format!("hachage impossible : {erreur}")))
}

async fn correspond(code: String, empreinte: String) -> Result<bool, ErreurDomaine> {
    tokio::task::spawn_blocking(move || bcrypt::verify(code, &empreinte))
        .await
        .map_err(|erreur| ErreurDomaine::Interne(format!("vérification interrompue : {erreur}")))?
        .map_err(|erreur| ErreurDomaine::Interne(format!("empreinte de code illisible : {erreur}")))
}

#[cfg(test)]
mod tests {
    use super::*;

    #[tokio::test]
    async fn l_empreinte_suit_le_format_de_php() {
        let empreinte = hacher("123456".to_owned(), 4).await.unwrap();

        assert!(empreinte.starts_with("$2y$04$"));
        assert!(
            correspond("123456".to_owned(), empreinte.clone())
                .await
                .unwrap()
        );
        assert!(!correspond("654321".to_owned(), empreinte).await.unwrap());
    }

    #[tokio::test]
    async fn verifie_une_empreinte_creee_par_php() {
        // password_hash('123456', PASSWORD_BCRYPT, ['cost' => 4]), comme Hash::make.
        let empreinte = "$2y$04$jfudgGZffQ8oJJSwhrJUaufcVdwYm8dIeJ711y9zJL/PL8rF/eQ2y";

        assert!(
            correspond("123456".to_owned(), empreinte.to_owned())
                .await
                .unwrap()
        );
        assert!(
            !correspond("123457".to_owned(), empreinte.to_owned())
                .await
                .unwrap()
        );
    }
}
