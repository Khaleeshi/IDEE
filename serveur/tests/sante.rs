//! Tests de bout en bout du socle : la route de santé et les routes inconnues.
//! Ils n'ont pas besoin de base : la connexion n'est ouverte qu'à l'usage.

mod commun;

use axum::Router;
use axum::body::Body;
use axum::http::{Request, StatusCode};
use axum::response::Response;
use http_body_util::BodyExt;
use idee_serveur::application;
use idee_serveur::domaine::envoi::Expediteur;
use idee_serveur::etat::EtatApp;
use serde_json::Value;
use sqlx::MySqlPool;
use tower::ServiceExt;

fn app() -> Router {
    let config = commun::config();
    let base = MySqlPool::connect_lazy_with(config.base.clone());
    application(EtatApp::new(config, base, Expediteur::Journal))
}

async fn appeler(chemin: &str) -> Response {
    let requete = Request::get(chemin).body(Body::empty()).unwrap();
    app().oneshot(requete).await.unwrap()
}

async fn corps_json(reponse: Response) -> Value {
    let octets = reponse.into_body().collect().await.unwrap().to_bytes();
    serde_json::from_slice(&octets).unwrap()
}

#[tokio::test]
async fn la_route_de_sante_repond_ok() {
    let reponse = appeler("/sante").await;

    assert_eq!(reponse.status(), StatusCode::OK);
    let corps = corps_json(reponse).await;
    assert_eq!(corps["statut"], "ok");
    assert_eq!(corps["environnement"], "local");
    assert_eq!(corps["version"], env!("CARGO_PKG_VERSION"));
}

#[tokio::test]
async fn une_route_inconnue_renvoie_404_au_format_de_laravel() {
    let reponse = appeler("/api/inconnue").await;

    assert_eq!(reponse.status(), StatusCode::NOT_FOUND);
    assert_eq!(
        corps_json(reponse).await["message"],
        "Ressource introuvable."
    );
}
