//! Membres : niveaux de vérification, restrictions de modération et données
//! du profil du membre connecté.

use chrono::{DateTime, Datelike, Utc};
use serde_json::Value;
use sqlx::MySqlPool;
use sqlx::types::Json;

/// Niveau « téléphone vérifié » (`VerificationLevel::Phone`).
pub const NIVEAU_TELEPHONE: u8 = 1;

/// Libellé d'un niveau de vérification (`VerificationLevel`, section 8).
pub fn libelle_verification(niveau: u8) -> &'static str {
    match niveau {
        0 => "Non vérifié",
        1 => "Téléphone vérifié",
        2 => "Identité vérifiée",
        3 => "Organisation vérifiée",
        _ => "Investisseur qualifié",
    }
}

/// Libellé d'un rôle (`UserRole`, CP-03).
pub fn libelle_role(role: &str) -> Option<&'static str> {
    Some(match role {
        "porteur" => "Porteur de projet",
        "contributeur" => "Contributeur de compétences",
        "investisseur" => "Investisseur",
        "expert" => "Expert",
        "coach" => "Coach ou mentor",
        "fournisseur" => "Fournisseur",
        "acheteur" => "Acheteur",
        _ => return None,
    })
}

/// Libellé d'une disponibilité (`Availability`).
pub fn libelle_disponibilite(disponibilite: &str) -> Option<&'static str> {
    Some(match disponibilite {
        "disponible" => "Disponible pour de nouveaux projets",
        "partielle" => "Quelques heures par semaine",
        "indisponible" => "Pas disponible en ce moment",
        _ => return None,
    })
}

/// Décisions de modération qui empêchent d'entrer dans un compte.
pub struct Moderation {
    pub banni_le: Option<DateTime<Utc>>,
    pub suspendu_par_precaution_le: Option<DateTime<Utc>>,
    pub suspendu_jusqu_au: Option<DateTime<Utc>>,
}

impl Moderation {
    /// Message à montrer si le compte est exclu ou suspendu
    /// (`User::restrictionMessage`).
    pub fn restriction(&self, maintenant: DateTime<Utc>) -> Option<String> {
        if self.banni_le.is_some() {
            return Some(
                "Ce compte a été exclu d'IDEE à la suite d'une décision de modération.".to_owned(),
            );
        }
        if self.suspendu_par_precaution_le.is_some() {
            return Some("Ce compte est suspendu par précaution après plusieurs signalements, en attendant l'examen d'un modérateur.".to_owned());
        }
        self.suspendu_jusqu_au
            .filter(|fin| *fin > maintenant)
            .map(|fin| {
                format!(
                    "Ce compte est suspendu jusqu'au {} à la suite d'une décision de modération.",
                    date_en_lettres(fin)
                )
            })
    }
}

/// Date au format « 09 novembre 2026 », comme `translatedFormat('d F Y')`.
pub fn date_en_lettres(date: DateTime<Utc>) -> String {
    const MOIS: [&str; 12] = [
        "janvier",
        "février",
        "mars",
        "avril",
        "mai",
        "juin",
        "juillet",
        "août",
        "septembre",
        "octobre",
        "novembre",
        "décembre",
    ];
    format!(
        "{:02} {} {}",
        date.day(),
        MOIS[date.month0() as usize],
        date.year()
    )
}

/// Colonnes de `users` utiles au profil du membre connecté.
#[derive(sqlx::FromRow)]
pub struct LigneProfil {
    pub id: String,
    pub name: String,
    pub region: Option<String>,
    pub photo_path: Option<String>,
    pub availability: Option<String>,
    pub bio: Option<String>,
    pub expertise: Option<String>,
    pub rate: Option<String>,
    pub languages: Option<Json<Value>>,
    pub skills: Option<Json<Value>>,
    pub sectors: Option<Json<Value>>,
    pub verification_level: u8,
    pub created_at: Option<DateTime<Utc>>,
    pub phone: Option<String>,
    pub email: Option<String>,
    pub contact_preferences: Option<Json<Value>>,
    pub terms_version: Option<String>,
}

pub async fn charger_profil(
    base: &MySqlPool,
    id: &str,
) -> Result<Option<LigneProfil>, sqlx::Error> {
    sqlx::query_as(
        "SELECT id, name, region, photo_path, availability, bio, expertise, rate, languages, skills, \
         sectors, verification_level, created_at, phone, email, contact_preferences, terms_version \
         FROM users WHERE id = ? AND deleted_at IS NULL",
    )
    .bind(id)
    .fetch_optional(base)
    .await
}

/// Rôles du membre, dans l'ordre où il les a pris.
pub async fn roles(base: &MySqlPool, id: &str) -> Result<Vec<String>, sqlx::Error> {
    sqlx::query_scalar("SELECT role FROM user_roles WHERE user_id = ? ORDER BY id")
        .bind(id)
        .fetch_all(base)
        .await
}

/// Demandes de contact envoyées par le membre depuis la date donnée (RG-04).
pub async fn demandes_envoyees_depuis(
    base: &MySqlPool,
    id: &str,
    depuis: DateTime<Utc>,
) -> Result<i64, sqlx::Error> {
    sqlx::query_scalar(
        "SELECT COUNT(*) FROM contact_requests WHERE sender_id = ? AND created_at >= ?",
    )
    .bind(id)
    .bind(depuis)
    .fetch_one(base)
    .await
}

#[cfg(test)]
mod tests {
    use chrono::TimeZone;

    use super::*;

    fn le(annee: i32, mois: u32, jour: u32) -> DateTime<Utc> {
        Utc.with_ymd_and_hms(annee, mois, jour, 12, 0, 0).unwrap()
    }

    #[test]
    fn ecrit_la_date_en_lettres() {
        assert_eq!(date_en_lettres(le(2026, 11, 9)), "09 novembre 2026");
        assert_eq!(date_en_lettres(le(2027, 8, 15)), "15 août 2027");
    }

    #[test]
    fn une_exclusion_l_emporte_sur_une_suspension() {
        let moderation = Moderation {
            banni_le: Some(le(2026, 1, 1)),
            suspendu_par_precaution_le: Some(le(2026, 1, 1)),
            suspendu_jusqu_au: None,
        };

        assert!(
            moderation
                .restriction(le(2026, 11, 9))
                .unwrap()
                .contains("exclu")
        );
    }

    #[test]
    fn une_suspension_terminee_ne_bloque_plus() {
        let moderation = Moderation {
            banni_le: None,
            suspendu_par_precaution_le: None,
            suspendu_jusqu_au: Some(le(2026, 12, 1)),
        };

        assert_eq!(
            moderation.restriction(le(2026, 11, 9)).as_deref(),
            Some(
                "Ce compte est suspendu jusqu'au 01 décembre 2026 à la suite d'une décision de modération."
            )
        );
        assert_eq!(moderation.restriction(le(2026, 12, 2)), None);
    }
}
