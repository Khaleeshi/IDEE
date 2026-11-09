//! Extracteurs : le membre connecté (jeton Bearer) et l'adresse IP du client.

use std::convert::Infallible;
use std::net::{IpAddr, Ipv4Addr, SocketAddr};

use axum::extract::{ConnectInfo, FromRequestParts};
use axum::http::header::AUTHORIZATION;
use axum::http::request::Parts;

use crate::domaine::jetons;
use crate::erreurs::ErreurApp;
use crate::etat::EtatApp;

/// Membre connecté par son jeton, comme le garde `auth:sanctum`.
#[derive(Debug, Clone)]
pub struct MembreConnecte {
    pub id: String,
    pub jeton_id: u64,
}

impl FromRequestParts<EtatApp> for MembreConnecte {
    type Rejection = ErreurApp;

    async fn from_request_parts(
        parts: &mut Parts,
        etat: &EtatApp,
    ) -> Result<Self, Self::Rejection> {
        let jeton = parts
            .headers
            .get(AUTHORIZATION)
            .and_then(|valeur| valeur.to_str().ok())
            .and_then(|valeur| valeur.strip_prefix("Bearer "))
            .map(str::trim)
            .filter(|jeton| !jeton.is_empty())
            .ok_or(ErreurApp::NonAuthentifie)?;

        let valide = jetons::authentifier(&etat.base, jeton, etat.config.sessions.inactivite_jours)
            .await?
            .ok_or(ErreurApp::NonAuthentifie)?;

        Ok(Self {
            id: valide.membre_id,
            jeton_id: valide.id,
        })
    }
}

/// Adresse IP du client, pour les limites de tentatives. Derrière Nginx, sur
/// la même machine, la vraie adresse est la dernière de `X-Forwarded-For`.
#[derive(Debug, Clone, Copy, PartialEq)]
pub struct AdresseClient(pub IpAddr);

impl<S: Send + Sync> FromRequestParts<S> for AdresseClient {
    type Rejection = Infallible;

    async fn from_request_parts(parts: &mut Parts, _etat: &S) -> Result<Self, Self::Rejection> {
        let pair = parts
            .extensions
            .get::<ConnectInfo<SocketAddr>>()
            .map_or(IpAddr::V4(Ipv4Addr::LOCALHOST), |info| info.0.ip());

        if pair.is_loopback()
            && let Some(transmise) = parts
                .headers
                .get("x-forwarded-for")
                .and_then(|valeur| valeur.to_str().ok())
                .and_then(|liste| liste.rsplit(',').next())
                .and_then(|derniere| derniere.trim().parse().ok())
        {
            return Ok(Self(transmise));
        }

        Ok(Self(pair))
    }
}
