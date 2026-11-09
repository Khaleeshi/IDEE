//! En développement seulement : relaie vers Laravel les routes que le serveur
//! Rust ne connaît pas encore, comme le fera Nginx en production. Avec
//! `IDEE_LARAVEL_URL`, l'application Flutter peut ne parler qu'au serveur
//! Rust. Le relais ne sait joindre qu'une adresse en `http://`.

use axum::Json;
use axum::body::{Body, to_bytes};
use axum::extract::Request;
use axum::http::{HeaderName, StatusCode, header};
use axum::response::{IntoResponse, Response};
use serde_json::json;

/// Taille maximale d'une requête relayée : les pièces jointes font au plus 10 Mo.
const TAILLE_MAX: usize = 20 * 1024 * 1024;

#[derive(Clone)]
pub struct Relais {
    client: reqwest::Client,
    adresse: String,
}

impl Relais {
    pub fn new(adresse: String) -> Self {
        Self {
            client: reqwest::Client::new(),
            adresse,
        }
    }

    pub async fn transmettre(&self, requete: Request) -> Response {
        let (parts, corps) = requete.into_parts();
        let Ok(octets) = to_bytes(corps, TAILLE_MAX).await else {
            return erreur(StatusCode::PAYLOAD_TOO_LARGE, "Requête trop volumineuse.");
        };

        let chemin = parts
            .uri
            .path_and_query()
            .map_or("/", |chemin| chemin.as_str());
        let mut envoi = self
            .client
            .request(parts.method, format!("{}{chemin}", self.adresse))
            .body(octets);
        for (nom, valeur) in &parts.headers {
            if a_transmettre(nom) {
                envoi = envoi.header(nom, valeur);
            }
        }

        let reponse = match envoi.send().await {
            Ok(reponse) => reponse,
            Err(cause) => {
                tracing::warn!(%cause, "Laravel injoignable");
                return erreur(
                    StatusCode::BAD_GATEWAY,
                    "Laravel ne répond pas (relais de développement).",
                );
            }
        };

        let statut = reponse.status();
        let entetes = reponse.headers().clone();
        let corps = match reponse.bytes().await {
            Ok(corps) => corps,
            Err(cause) => {
                tracing::warn!(%cause, "réponse de Laravel interrompue");
                return erreur(
                    StatusCode::BAD_GATEWAY,
                    "La réponse de Laravel est incomplète.",
                );
            }
        };

        let mut sortie = Response::new(Body::from(corps));
        *sortie.status_mut() = statut;
        for (nom, valeur) in &entetes {
            if a_transmettre(nom) {
                sortie.headers_mut().append(nom, valeur.clone());
            }
        }
        sortie
    }
}

/// Les en-têtes propres à une connexion ne passent pas d'un serveur à l'autre.
fn a_transmettre(nom: &HeaderName) -> bool {
    ![
        header::HOST,
        header::CONNECTION,
        header::CONTENT_LENGTH,
        header::TRANSFER_ENCODING,
        header::TE,
        header::TRAILER,
        header::UPGRADE,
        header::PROXY_AUTHORIZATION,
        header::PROXY_AUTHENTICATE,
    ]
    .contains(nom)
        && nom.as_str() != "keep-alive"
}

fn erreur(statut: StatusCode, message: &str) -> Response {
    (statut, Json(json!({ "message": message }))).into_response()
}
