//! Outils partagés par les tests de bout en bout.

#![allow(dead_code)]

use std::collections::HashMap;
use std::sync::{Arc, Mutex};

use axum::Router;
use axum::body::Body;
use axum::http::{Request, StatusCode, header};
use http_body_util::BodyExt;
use idee_serveur::application;
use idee_serveur::config::Config;
use idee_serveur::domaine::envoi::Expediteur;
use idee_serveur::etat::EtatApp;
use serde_json::Value;
use sqlx::MySqlPool;
use tower::ServiceExt;

/// Configuration par défaut, avec un hachage bcrypt rapide pour les tests.
pub fn config() -> Config {
    Config::depuis(|nom| (nom == "BCRYPT_ROUNDS").then(|| "4".to_owned()))
        .expect("configuration de test")
}

/// Application branchée sur une base de test, avec les codes gardés en mémoire.
pub struct Banc {
    pub app: Router,
    pub base: MySqlPool,
    codes: Arc<Mutex<HashMap<String, String>>>,
}

impl Banc {
    pub fn new(base: MySqlPool) -> Self {
        let codes = Arc::new(Mutex::new(HashMap::new()));
        let etat = EtatApp::new(config(), base.clone(), Expediteur::Memoire(codes.clone()));
        Self {
            app: application(etat),
            base,
            codes,
        }
    }

    /// Dernier code envoyé à ce numéro ou à cette adresse.
    pub fn code(&self, destinataire: &str) -> String {
        self.codes
            .lock()
            .unwrap()
            .get(destinataire)
            .cloned()
            .unwrap_or_else(|| panic!("aucun code envoyé à {destinataire}"))
    }

    /// Un code faux, différent du vrai.
    pub fn mauvais_code(&self, destinataire: &str) -> &'static str {
        if self.code(destinataire) == "000000" {
            "111111"
        } else {
            "000000"
        }
    }

    pub async fn post(&self, chemin: &str, corps: Value) -> (StatusCode, Value) {
        self.envoyer(chemin, Some(corps), None).await
    }

    pub async fn post_avec_jeton(&self, chemin: &str, jeton: &str) -> (StatusCode, Value) {
        self.envoyer(chemin, None, Some(jeton)).await
    }

    async fn envoyer(
        &self,
        chemin: &str,
        corps: Option<Value>,
        jeton: Option<&str>,
    ) -> (StatusCode, Value) {
        let mut requete = Request::post(chemin)
            .header(header::ACCEPT, "application/json")
            .header(header::CONTENT_TYPE, "application/json");
        if let Some(jeton) = jeton {
            requete = requete.header(header::AUTHORIZATION, format!("Bearer {jeton}"));
        }
        let corps = corps.map_or_else(Body::empty, |valeur| Body::from(valeur.to_string()));
        let reponse = self
            .app
            .clone()
            .oneshot(requete.body(corps).unwrap())
            .await
            .unwrap();

        let statut = reponse.status();
        let octets = reponse.into_body().collect().await.unwrap().to_bytes();
        let valeur = if octets.is_empty() {
            Value::Null
        } else {
            serde_json::from_slice(&octets).unwrap()
        };
        (statut, valeur)
    }

    /// Demande un code puis le vérifie ; rend la réponse de la vérification.
    pub async fn connecter(&self, telephone: &str, nom: Option<&str>) -> (StatusCode, Value) {
        let (statut, _) = self
            .post("/api/auth/code", serde_json::json!({ "phone": telephone }))
            .await;
        assert_eq!(statut, StatusCode::ACCEPTED);
        let code = self.code(telephone);
        self.post(
            "/api/auth/verify",
            serde_json::json!({ "phone": telephone, "code": code, "name": nom }),
        )
        .await
    }
}
