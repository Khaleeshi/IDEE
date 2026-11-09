//! Envoi des codes de connexion. Le canal se choisit comme dans Laravel :
//! `IDEE_OTP_DRIVER` pour un numéro, `MAIL_MAILER` pour une adresse e-mail.
//!
//! Le serveur Rust ne connaît pour l'instant que le canal `log` : le code est
//! écrit dans le journal, comme `LogCodeSender`. WhatsApp, le SMS Orange,
//! Twilio et l'e-mail arriveront avant que la route d'envoi bascule en
//! production.

use std::collections::HashMap;
use std::sync::{Arc, Mutex};

use crate::config::ReglesCodes;

#[derive(Clone)]
pub enum Expediteur {
    /// Canal `log` : le code est écrit dans le journal du serveur.
    Journal,
    /// Pour les tests : les codes envoyés sont gardés en mémoire.
    Memoire(Arc<Mutex<HashMap<String, String>>>),
}

#[derive(Debug, PartialEq)]
pub enum ErreurEnvoi {
    /// Ce canal n'est pas encore pris en charge par le serveur Rust.
    CanalNonPrisEnCharge(String),
}

impl Expediteur {
    /// Vérifie, avant de créer le code, que ce destinataire peut être joint.
    pub fn verifier_canal(
        &self,
        regles: &ReglesCodes,
        destinataire: &str,
    ) -> Result<(), ErreurEnvoi> {
        match self {
            Expediteur::Memoire(_) => Ok(()),
            Expediteur::Journal => match regles.canal_pour(destinataire) {
                "log" => Ok(()),
                autre => Err(ErreurEnvoi::CanalNonPrisEnCharge(autre.to_owned())),
            },
        }
    }

    pub async fn envoyer(&self, destinataire: &str, code: &str) -> Result<(), ErreurEnvoi> {
        match self {
            Expediteur::Journal => {
                tracing::info!(destinataire, code, "Code de connexion IDEE");
            }
            Expediteur::Memoire(codes) => {
                codes
                    .lock()
                    .unwrap_or_else(|erreur| erreur.into_inner())
                    .insert(destinataire.to_owned(), code.to_owned());
            }
        }
        Ok(())
    }
}
