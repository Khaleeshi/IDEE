//! Jetons de l'application mobile, compatibles avec Laravel Sanctum : le
//! jeton remis vaut « id|secret » et la base garde l'empreinte SHA-256 du
//! secret. Un jeton créé par l'un des serveurs marche donc sur l'autre, et
//! les membres restent connectés quand une route change de serveur.

use chrono::{DateTime, TimeDelta, Utc};
use rand::RngExt;
use rand::distr::Alphanumeric;
use sha2::{Digest, Sha256};
use sqlx::MySqlPool;
use subtle::ConstantTimeEq;

use super::{ErreurDomaine, maintenant};
use crate::config::TYPE_MEMBRE;

/// Jeton reconnu : son numéro et le membre à qui il appartient.
#[derive(Debug, Clone, PartialEq)]
pub struct JetonValide {
    pub id: u64,
    pub membre_id: String,
}

/// Crée un jeton pour cet appareil et rend sa forme « id|secret », à remettre
/// à l'application. Il expire après la durée maximale d'une session (CP-06).
pub async fn creer(
    base: &MySqlPool,
    membre_id: &str,
    nom_appareil: &str,
    prefixe: &str,
    duree_max_jours: i64,
) -> Result<String, ErreurDomaine> {
    let secret = generer_secret(prefixe);
    let instant = maintenant();
    let resultat = sqlx::query(
        "INSERT INTO personal_access_tokens \
         (tokenable_type, tokenable_id, name, token, abilities, expires_at, created_at, updated_at) \
         VALUES (?, ?, ?, ?, '[\"*\"]', ?, ?, ?)",
    )
    .bind(TYPE_MEMBRE)
    .bind(membre_id)
    .bind(nom_appareil)
    .bind(empreinte(&secret))
    .bind(instant + TimeDelta::days(duree_max_jours))
    .bind(instant)
    .bind(instant)
    .execute(base)
    .await?;

    Ok(format!("{}|{secret}", resultat.last_insert_id()))
}

#[derive(sqlx::FromRow)]
struct LigneJeton {
    id: u64,
    tokenable_type: String,
    tokenable_id: String,
    token: String,
    last_used_at: Option<DateTime<Utc>>,
    expires_at: Option<DateTime<Utc>>,
    created_at: Option<DateTime<Utc>>,
}

/// Reconnaît un jeton présenté par l'application. Il est refusé s'il a
/// expiré, si l'appareil n'a pas servi depuis `inactivite_jours` (CP-06), ou
/// si le compte a été supprimé. Un jeton accepté note sa dernière utilisation.
pub async fn authentifier(
    base: &MySqlPool,
    jeton: &str,
    inactivite_jours: i64,
) -> Result<Option<JetonValide>, ErreurDomaine> {
    let ligne: Option<LigneJeton> = match jeton.split_once('|') {
        Some((id, secret)) => {
            let Ok(id) = id.parse::<u64>() else {
                return Ok(None);
            };
            let ligne: Option<LigneJeton> = sqlx::query_as(
                "SELECT id, tokenable_type, tokenable_id, token, last_used_at, expires_at, created_at \
                 FROM personal_access_tokens WHERE id = ?",
            )
            .bind(id)
            .fetch_optional(base)
            .await?;
            // Comparaison en temps constant, comme hash_equals dans Sanctum.
            ligne.filter(|l| bool::from(l.token.as_bytes().ct_eq(empreinte(secret).as_bytes())))
        }
        None => sqlx::query_as(
            "SELECT id, tokenable_type, tokenable_id, token, last_used_at, expires_at, created_at \
                 FROM personal_access_tokens WHERE token = ?",
        )
        .bind(empreinte(jeton))
        .fetch_optional(base)
        .await?,
    };
    let Some(ligne) = ligne else {
        return Ok(None);
    };

    let instant = maintenant();
    let pas_expire = ligne.expires_at.is_none_or(|fin| fin > instant);
    let utilise_recemment = ligne
        .last_used_at
        .or(ligne.created_at)
        .is_some_and(|derniere| derniere > instant - TimeDelta::days(inactivite_jours));
    if ligne.tokenable_type != TYPE_MEMBRE || !pas_expire || !utilise_recemment {
        return Ok(None);
    }

    let compte_actif: Option<i64> =
        sqlx::query_scalar("SELECT 1 FROM users WHERE id = ? AND deleted_at IS NULL")
            .bind(&ligne.tokenable_id)
            .fetch_optional(base)
            .await?;
    if compte_actif.is_none() {
        return Ok(None);
    }

    sqlx::query("UPDATE personal_access_tokens SET last_used_at = ?, updated_at = ? WHERE id = ?")
        .bind(instant)
        .bind(instant)
        .bind(ligne.id)
        .execute(base)
        .await?;

    Ok(Some(JetonValide {
        id: ligne.id,
        membre_id: ligne.tokenable_id,
    }))
}

/// Déconnecte l'appareil de ce jeton.
pub async fn supprimer(base: &MySqlPool, id: u64) -> Result<(), ErreurDomaine> {
    sqlx::query("DELETE FROM personal_access_tokens WHERE id = ?")
        .bind(id)
        .execute(base)
        .await?;
    Ok(())
}

/// Secret d'un jeton, comme dans Sanctum : le préfixe, 40 lettres et chiffres
/// au hasard, puis leur somme de contrôle CRC32 en hexadécimal.
pub fn generer_secret(prefixe: &str) -> String {
    let entropie: String = rand::rng()
        .sample_iter(Alphanumeric)
        .take(40)
        .map(char::from)
        .collect();
    format!(
        "{prefixe}{entropie}{:08x}",
        crc32fast::hash(entropie.as_bytes())
    )
}

/// Empreinte SHA-256 en hexadécimal, comme `hash('sha256', …)` en PHP.
pub fn empreinte(texte: &str) -> String {
    Sha256::digest(texte.as_bytes())
        .iter()
        .map(|octet| format!("{octet:02x}"))
        .collect()
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn l_empreinte_est_celle_de_php() {
        assert_eq!(
            empreinte("abc"),
            "ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad"
        );
    }

    #[test]
    fn le_secret_suit_le_format_de_sanctum() {
        let secret = generer_secret("idee_");
        let (entropie, controle) = secret["idee_".len()..].split_at(40);

        assert!(secret.starts_with("idee_"));
        assert!(entropie.chars().all(|c| c.is_ascii_alphanumeric()));
        assert_eq!(
            controle,
            format!("{:08x}", crc32fast::hash(entropie.as_bytes()))
        );
    }

    #[test]
    fn deux_secrets_different() {
        assert_ne!(generer_secret(""), generer_secret(""));
    }
}
