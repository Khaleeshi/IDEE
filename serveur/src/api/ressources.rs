//! Représentations JSON renvoyées à l'application, dans l'ordre des champs
//! des ressources Laravel.

use chrono::TimeDelta;
use serde::Serialize;
use serde_json::{Value, json};
use sqlx::MySqlPool;

use crate::config::VERSION_TEXTES_LEGAUX;
use crate::domaine::{ErreurDomaine, maintenant, membres};

/// Valeur d'énumération avec son libellé (`EnumResource`).
#[derive(Debug, Serialize)]
pub struct Libelle {
    pub value: Value,
    pub label: &'static str,
}

/// Profil du membre connecté, renvoyé à la connexion (`ProfileResource`).
///
/// Il manque encore deux champs de Laravel, qui arriveront avec la route
/// `/api/me` : `profiles` (profils professionnels) et `weekly_contact_quota`
/// (quota de demandes, qui dépend des offres payées). L'application Flutter
/// n'en a pas besoin à la connexion.
#[derive(Debug, Serialize)]
pub struct Profil {
    pub id: String,
    pub name: String,
    pub region: Option<String>,
    pub photo_url: Option<String>,
    pub availability: Option<Libelle>,
    pub bio: Option<String>,
    pub expertise: Option<String>,
    pub rate: Option<String>,
    pub languages: Value,
    pub skills: Value,
    pub sectors: Value,
    pub roles: Vec<Libelle>,
    pub verification: Libelle,
    pub member_since: Option<String>,
    pub phone: Option<String>,
    pub email: Option<String>,
    pub contact_preferences: Value,
    pub contact_requests_sent_this_week: i64,
    pub terms_accepted: bool,
}

impl Profil {
    pub async fn charger(
        base: &MySqlPool,
        url_publique: &str,
        id: &str,
    ) -> Result<Self, ErreurDomaine> {
        let ligne = membres::charger_profil(base, id)
            .await?
            .ok_or_else(|| ErreurDomaine::Interne(format!("membre {id} introuvable")))?;
        let roles = membres::roles(base, id).await?;
        let demandes =
            membres::demandes_envoyees_depuis(base, id, maintenant() - TimeDelta::weeks(1)).await?;
        let liste = |valeur: Option<sqlx::types::Json<Value>>| valeur.map_or(json!([]), |v| v.0);

        Ok(Self {
            id: ligne.id,
            name: ligne.name,
            region: ligne.region,
            photo_url: ligne
                .photo_path
                .map(|chemin| format!("{url_publique}/storage/{chemin}")),
            availability: ligne.availability.and_then(|valeur| {
                membres::libelle_disponibilite(&valeur).map(|label| Libelle {
                    value: Value::String(valeur),
                    label,
                })
            }),
            bio: ligne.bio,
            expertise: ligne.expertise,
            rate: ligne.rate,
            languages: liste(ligne.languages),
            skills: liste(ligne.skills),
            sectors: liste(ligne.sectors),
            roles: roles
                .into_iter()
                .filter_map(|role| {
                    membres::libelle_role(&role).map(|label| Libelle {
                        value: Value::String(role),
                        label,
                    })
                })
                .collect(),
            verification: Libelle {
                value: json!(ligne.verification_level),
                label: membres::libelle_verification(ligne.verification_level),
            },
            member_since: ligne
                .created_at
                .map(|date| date.format("%Y-%m-%d").to_string()),
            phone: ligne.phone,
            email: ligne.email,
            contact_preferences: ligne
                .contact_preferences
                .map_or(json!({ "accepts_requests": true }), |v| v.0),
            contact_requests_sent_this_week: demandes,
            terms_accepted: ligne.terms_version.as_deref() == Some(VERSION_TEXTES_LEGAUX),
        })
    }
}
