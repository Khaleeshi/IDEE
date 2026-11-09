//! État partagé entre toutes les requêtes : la configuration, la base, l'envoi
//! des codes, les limites de tentatives et, en local, le relais vers Laravel.

use std::sync::Arc;

use sqlx::MySqlPool;

use crate::config::Config;
use crate::domaine::envoi::Expediteur;
use crate::limites::Limiteur;
use crate::relais::Relais;

#[derive(Clone)]
pub struct EtatApp {
    pub config: Arc<Config>,
    pub base: MySqlPool,
    pub expediteur: Expediteur,
    pub limites: Arc<Limiteur>,
    pub relais: Option<Relais>,
}

impl EtatApp {
    pub fn new(config: Config, base: MySqlPool, expediteur: Expediteur) -> Self {
        let relais = config.relais_laravel.clone().map(Relais::new);
        Self {
            config: Arc::new(config),
            base,
            expediteur,
            limites: Arc::new(Limiteur::default()),
            relais,
        }
    }
}
