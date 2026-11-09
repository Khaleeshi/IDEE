use std::net::SocketAddr;
use std::process::ExitCode;

use idee_serveur::application;
use idee_serveur::config::Config;
use idee_serveur::domaine::envoi::Expediteur;
use idee_serveur::etat::EtatApp;
use sqlx::mysql::MySqlPoolOptions;
use tracing_subscriber::EnvFilter;

#[tokio::main]
async fn main() -> ExitCode {
    // Le fichier .env est facultatif : en production, les variables viennent
    // du service systemd.
    dotenvy::dotenv().ok();
    tracing_subscriber::fmt()
        .with_env_filter(
            EnvFilter::try_from_default_env().unwrap_or_else(|_| EnvFilter::new("info")),
        )
        .init();

    let config = match Config::depuis_env() {
        Ok(config) => config,
        Err(erreur) => {
            tracing::error!("configuration invalide : {erreur}");
            return ExitCode::FAILURE;
        }
    };

    let base = match MySqlPoolOptions::new()
        .max_connections(10)
        .connect_with(config.base.clone())
        .await
    {
        Ok(base) => base,
        Err(erreur) => {
            tracing::error!("base MySQL injoignable : {erreur}");
            return ExitCode::FAILURE;
        }
    };

    for (variable, canal) in [
        ("IDEE_OTP_DRIVER", &config.codes.canal_telephone),
        ("MAIL_MAILER", &config.codes.canal_email),
    ] {
        if canal != "log" {
            tracing::warn!(
                "{variable}={canal} : canal pas encore pris en charge, les demandes de code concernées répondront 503"
            );
        }
    }
    if let Some(adresse) = &config.relais_laravel {
        tracing::info!("routes inconnues relayées vers Laravel : {adresse}");
    }

    let adresse = config.adresse;
    let ecoute = match tokio::net::TcpListener::bind(adresse).await {
        Ok(ecoute) => ecoute,
        Err(erreur) => {
            tracing::error!("impossible d'écouter sur {adresse} : {erreur}");
            return ExitCode::FAILURE;
        }
    };

    tracing::info!("serveur IDEE à l'écoute sur http://{adresse}");
    let app = application(EtatApp::new(config, base, Expediteur::Journal));
    if let Err(erreur) = axum::serve(
        ecoute,
        app.into_make_service_with_connect_info::<SocketAddr>(),
    )
    .with_graceful_shutdown(signal_d_arret())
    .await
    {
        tracing::error!("le serveur s'est arrêté sur une erreur : {erreur}");
        return ExitCode::FAILURE;
    }

    ExitCode::SUCCESS
}

/// Attend Ctrl+C, ou le signal d'arrêt envoyé par systemd sous Linux.
async fn signal_d_arret() {
    let ctrl_c = async {
        let _ = tokio::signal::ctrl_c().await;
    };

    #[cfg(unix)]
    let terminer = async {
        use tokio::signal::unix::{SignalKind, signal};
        if let Ok(mut arret) = signal(SignalKind::terminate()) {
            arret.recv().await;
        }
    };
    #[cfg(not(unix))]
    let terminer = std::future::pending::<()>();

    tokio::select! {
        () = ctrl_c => {}
        () = terminer => {}
    }
    tracing::info!("arrêt du serveur demandé");
}
