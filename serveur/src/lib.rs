//! Serveur IDEE en Rust : l'API de l'application mobile et, plus tard, les
//! pages du site.
//!
//! Organisation (section 13 du cahier des charges) :
//! - `domaine` : les règles de gestion, appelées par l'API comme par le site ;
//! - `api` : les routes JSON de l'application mobile ;
//! - `site` : les pages HTML ;
//! - `taches` : les tâches planifiées et la file des envois.

pub mod api;
pub mod config;
pub mod domaine;
pub mod erreurs;
pub mod etat;
pub mod limites;
pub mod relais;
pub mod site;
pub mod taches;

use axum::Router;
use axum::extract::{Request, State};
use axum::response::{IntoResponse, Response};

use crate::erreurs::ErreurApp;
use crate::etat::EtatApp;

/// Construit l'application avec toutes ses routes.
pub fn application(etat: EtatApp) -> Router {
    Router::new()
        .merge(api::routes())
        .fallback(route_inconnue)
        .with_state(etat)
}

/// Une route pas encore réécrite part vers Laravel si le relais est actif.
async fn route_inconnue(State(etat): State<EtatApp>, requete: Request) -> Response {
    match &etat.relais {
        Some(relais) => relais.transmettre(requete).await,
        None => ErreurApp::Introuvable.into_response(),
    }
}
