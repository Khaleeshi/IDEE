//! Routes JSON utilisées par l'application mobile.
//!
//! Les routes reprises de Laravel gardent exactement les mêmes chemins
//! (`/api/auth/code`, `/api/projects`…) pour que l'application Flutter
//! fonctionne sans modification.

pub mod authentification;
pub mod connexion;
pub mod ressources;
pub mod validation;

use axum::extract::State;
use axum::routing::{get, post};
use axum::{Json, Router};
use serde::Serialize;

use crate::etat::EtatApp;

pub fn routes() -> Router<EtatApp> {
    Router::new()
        .route("/sante", get(sante))
        .route("/api/auth/code", post(connexion::demander_code))
        .route("/api/auth/verify", post(connexion::verifier_code))
        .route("/api/auth/logout", post(connexion::deconnecter))
}

#[derive(Serialize)]
struct Sante {
    statut: &'static str,
    version: &'static str,
    environnement: String,
}

/// État du serveur Rust, pour la surveillance. Laravel garde sa propre route
/// `/up` : pendant la migration, Nginx envoie `/sante` au serveur Rust.
async fn sante(State(etat): State<EtatApp>) -> Json<Sante> {
    Json(Sante {
        statut: "ok",
        version: env!("CARGO_PKG_VERSION"),
        environnement: etat.config.environnement.clone(),
    })
}
