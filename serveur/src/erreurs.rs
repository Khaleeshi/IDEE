//! Erreur commune de l'application, transformée en réponse JSON.
//!
//! Le corps suit le format de Laravel (`{"message": "…"}`, et `errors` pour
//! une erreur de validation) pour que l'application Flutter lise les erreurs
//! du serveur Rust sans changement.

use std::time::Duration;

use axum::Json;
use axum::http::StatusCode;
use axum::http::header::RETRY_AFTER;
use axum::response::{IntoResponse, Response};
use serde::Serialize;
use serde_json::json;

use crate::api::validation::ErreursValidation;
use crate::domaine::ErreurDomaine;

#[derive(Debug)]
pub enum ErreurApp {
    /// La ressource demandée n'existe pas (404).
    Introuvable,
    /// Jeton absent, inconnu ou expiré (401).
    NonAuthentifie,
    /// Données refusées, champ par champ (422).
    Validation(ErreursValidation),
    /// Trop de tentatives : réessayer après ce délai (429).
    TropDeTentatives { attente: Duration },
    /// Fonction pas encore prise en charge par ce serveur (503).
    Indisponible(String),
    /// Erreur inattendue : le détail va dans les journaux, jamais dans la réponse (500).
    Interne(String),
}

#[derive(Serialize)]
struct CorpsValidation<'a> {
    message: &'a str,
    errors: &'a ErreursValidation,
}

impl IntoResponse for ErreurApp {
    fn into_response(self) -> Response {
        let message = |statut: StatusCode, texte: &str| {
            (statut, Json(json!({ "message": texte }))).into_response()
        };

        match self {
            ErreurApp::Introuvable => message(StatusCode::NOT_FOUND, "Ressource introuvable."),
            ErreurApp::NonAuthentifie => message(StatusCode::UNAUTHORIZED, "Non authentifié."),
            ErreurApp::Validation(erreurs) => (
                StatusCode::UNPROCESSABLE_ENTITY,
                Json(CorpsValidation {
                    message: erreurs.premier_message(),
                    errors: &erreurs,
                }),
            )
                .into_response(),
            ErreurApp::TropDeTentatives { attente } => {
                let secondes = attente
                    .as_secs()
                    .saturating_add(u64::from(attente.subsec_nanos() > 0))
                    .max(1);
                (
                    StatusCode::TOO_MANY_REQUESTS,
                    [(RETRY_AFTER, secondes.to_string())],
                    Json(json!({
                        "message": format!("Trop de tentatives. Réessayez dans {secondes} seconde(s)."),
                    })),
                )
                    .into_response()
            }
            ErreurApp::Indisponible(texte) => message(StatusCode::SERVICE_UNAVAILABLE, &texte),
            ErreurApp::Interne(detail) => {
                tracing::error!(%detail, "erreur interne");
                message(
                    StatusCode::INTERNAL_SERVER_ERROR,
                    "Une erreur est survenue. Réessayez plus tard.",
                )
            }
        }
    }
}

impl From<ErreurDomaine> for ErreurApp {
    fn from(erreur: ErreurDomaine) -> Self {
        match erreur {
            ErreurDomaine::Refus { champ, message } => {
                ErreurApp::Validation(ErreursValidation::un(champ, message))
            }
            ErreurDomaine::Interne(detail) => ErreurApp::Interne(detail),
        }
    }
}

impl From<sqlx::Error> for ErreurApp {
    fn from(erreur: sqlx::Error) -> Self {
        ErreurApp::Interne(format!("base de données : {erreur}"))
    }
}
