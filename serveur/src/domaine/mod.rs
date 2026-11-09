//! Règles de gestion d'IDEE, l'équivalent de `backend/app/Services`.
//!
//! L'API et le site appellent les mêmes fonctions d'ici ; les contrôleurs
//! restent minces. Chaque règle reprise de Laravel arrive avec ses tests.

pub mod codes;
pub mod envoi;
pub mod jetons;
pub mod membres;
pub mod pays;
pub mod telephone;

use chrono::{DateTime, SubsecRound, Utc};

/// Une règle de gestion refuse l'action, ou une erreur technique survient.
#[derive(Debug)]
pub enum ErreurDomaine {
    /// Refus à montrer au membre, rattaché au champ du formulaire concerné.
    Refus {
        champ: &'static str,
        message: String,
    },
    /// Erreur technique : le détail va dans les journaux.
    Interne(String),
}

impl ErreurDomaine {
    pub fn refus(champ: &'static str, message: impl Into<String>) -> Self {
        Self::Refus {
            champ,
            message: message.into(),
        }
    }
}

impl From<sqlx::Error> for ErreurDomaine {
    fn from(erreur: sqlx::Error) -> Self {
        Self::Interne(format!("base de données : {erreur}"))
    }
}

/// Heure courante en UTC, à la seconde, comme les colonnes `timestamp` de
/// Laravel (MySQL arrondirait les fractions de seconde).
pub fn maintenant() -> DateTime<Utc> {
    Utc::now().trunc_subsecs(0)
}
