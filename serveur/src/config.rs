//! Configuration lue au démarrage dans les variables d'environnement.
//!
//! Les valeurs réglables des règles de gestion (section 11 du cahier des
//! charges) gardent les noms de `backend/config/idee.php`, et la base reprend
//! les variables `DB_*` de Laravel : le même fichier `.env` sert aux deux
//! serveurs. Aucune valeur ne doit être écrite en dur ailleurs.

use std::fmt;
use std::net::SocketAddr;
use std::str::FromStr;
use std::time::Duration;

use sqlx::mysql::MySqlConnectOptions;

/// Adresse d'écoute par défaut, pour le développement local.
const ADRESSE_PAR_DEFAUT: &str = "127.0.0.1:8080";

/// Version des conditions d'utilisation et de la charte (`idee.legal.version`).
pub const VERSION_TEXTES_LEGAUX: &str = "2026-10";

/// Longueur du code de connexion (`idee.otp.length`, fixe dans Laravel).
pub const LONGUEUR_CODE: usize = 6;

/// Type enregistré pour les jetons des membres dans `personal_access_tokens`.
pub const TYPE_MEMBRE: &str = "App\\Models\\User";

/// Limites de tentatives, reprises de `AppServiceProvider` (section 4 du cahier).
pub mod limites {
    use std::time::Duration;

    pub const MINUTE: Duration = Duration::from_secs(60);
    pub const HEURE: Duration = Duration::from_secs(3600);

    /// Un code par minute pour un même numéro ou une même adresse.
    pub const CODES_PAR_DESTINATAIRE: u32 = 1;
    /// Dix codes par heure depuis une même adresse IP.
    pub const CODES_PAR_IP: u32 = 10;
    /// Dix vérifications de code par minute depuis une même adresse IP.
    pub const VERIFICATIONS_PAR_IP: u32 = 10;
}

#[derive(Debug, Clone)]
pub struct Config {
    /// Adresse d'écoute du serveur (`IDEE_SERVEUR_ADRESSE`).
    pub adresse: SocketAddr,
    /// Nom de l'environnement : local, staging ou production (`APP_ENV`).
    pub environnement: String,
    /// Adresse publique du site (`APP_URL`), pour les liens vers les photos.
    pub url_publique: String,
    /// Connexion à la base MySQL (`DATABASE_URL`, sinon `DB_HOST`, `DB_PORT`…).
    pub base: MySqlConnectOptions,
    pub codes: ReglesCodes,
    pub sessions: ReglesSessions,
    /// Coût du hachage bcrypt des codes (`BCRYPT_ROUNDS`).
    pub cout_bcrypt: u32,
    /// Préfixe des jetons de connexion (`SANCTUM_TOKEN_PREFIX`).
    pub prefixe_jetons: String,
    /// En local seulement : adresse de Laravel, vers qui relayer les routes
    /// pas encore réécrites (`IDEE_LARAVEL_URL`). En production, c'est Nginx
    /// qui répartit les routes.
    pub relais_laravel: Option<String>,
}

/// Codes de connexion (CP-01, `idee.otp`).
#[derive(Debug, Clone)]
pub struct ReglesCodes {
    /// Durée de validité d'un code (`IDEE_OTP_TTL`).
    pub validite: Duration,
    /// Essais permis sur un même code (`IDEE_OTP_MAX_ATTEMPTS`).
    pub essais_max: u32,
    /// Canal d'envoi vers un numéro (`IDEE_OTP_DRIVER`).
    pub canal_telephone: String,
    /// Canal d'envoi vers une adresse e-mail (`MAIL_MAILER`).
    pub canal_email: String,
}

impl ReglesCodes {
    /// Canal qui sert à joindre ce numéro ou cette adresse.
    pub fn canal_pour(&self, identifiant: &str) -> &str {
        if identifiant.contains('@') {
            &self.canal_email
        } else {
            &self.canal_telephone
        }
    }
}

/// Sessions de l'application mobile (CP-06, `idee.sessions`).
#[derive(Debug, Clone)]
pub struct ReglesSessions {
    /// Un appareil inutilisé depuis ce nombre de jours est déconnecté (`IDEE_SESSION_IDLE_DAYS`).
    pub inactivite_jours: i64,
    /// Durée maximale d'un jeton, même utilisé tous les jours (`IDEE_SESSION_MAX_DAYS`).
    pub duree_max_jours: i64,
}

#[derive(Debug, PartialEq)]
pub enum ErreurConfig {
    /// La valeur de `IDEE_SERVEUR_ADRESSE` n'est pas une adresse valide.
    AdresseInvalide(String),
    /// `DATABASE_URL` n'est pas une adresse MySQL valide.
    BaseInvalide(String),
    /// Une variable attendue comme nombre n'en est pas un.
    NombreInvalide {
        variable: &'static str,
        valeur: String,
    },
}

impl fmt::Display for ErreurConfig {
    fn fmt(&self, f: &mut fmt::Formatter<'_>) -> fmt::Result {
        match self {
            ErreurConfig::AdresseInvalide(valeur) => write!(
                f,
                "IDEE_SERVEUR_ADRESSE vaut « {valeur} » : attendu une adresse comme {ADRESSE_PAR_DEFAUT}"
            ),
            ErreurConfig::BaseInvalide(raison) => write!(f, "DATABASE_URL invalide : {raison}"),
            ErreurConfig::NombreInvalide { variable, valeur } => {
                write!(f, "{variable} vaut « {valeur} » : attendu un nombre entier")
            }
        }
    }
}

impl std::error::Error for ErreurConfig {}

impl Config {
    /// Lit la configuration dans les variables d'environnement du processus.
    pub fn depuis_env() -> Result<Self, ErreurConfig> {
        Self::depuis(|nom| std::env::var(nom).ok())
    }

    /// Lit la configuration avec la fonction de lecture donnée, ce qui permet
    /// aux tests de fournir leurs propres valeurs.
    pub fn depuis(lire: impl Fn(&str) -> Option<String>) -> Result<Self, ErreurConfig> {
        // Comme dans Laravel, une variable vide compte comme absente.
        let valeur = |nom: &str| lire(nom).filter(|v| !v.trim().is_empty());
        let texte = |nom: &str, defaut: &str| valeur(nom).unwrap_or_else(|| defaut.to_owned());
        let entier = |nom: &'static str, defaut: i64| match valeur(nom) {
            None => Ok(defaut),
            Some(brut) => brut
                .trim()
                .parse::<i64>()
                .map_err(|_| ErreurConfig::NombreInvalide {
                    variable: nom,
                    valeur: brut,
                }),
        };
        let positif = |nom: &'static str, defaut: i64| {
            let nombre = entier(nom, defaut)?;
            u32::try_from(nombre).map_err(|_| ErreurConfig::NombreInvalide {
                variable: nom,
                valeur: nombre.to_string(),
            })
        };

        let adresse_brute = texte("IDEE_SERVEUR_ADRESSE", ADRESSE_PAR_DEFAUT);
        let adresse = adresse_brute
            .parse()
            .map_err(|_| ErreurConfig::AdresseInvalide(adresse_brute.clone()))?;

        let base = match valeur("DATABASE_URL") {
            // sqlx lirait une adresse postgres:// comme une adresse MySQL : on le refuse ici.
            Some(url) if !url.starts_with("mysql://") && !url.starts_with("mariadb://") => {
                return Err(ErreurConfig::BaseInvalide(
                    "attendu une adresse mysql://utilisateur:mot-de-passe@hôte:port/base"
                        .to_owned(),
                ));
            }
            Some(url) => MySqlConnectOptions::from_str(&url)
                .map_err(|erreur| ErreurConfig::BaseInvalide(erreur.to_string()))?,
            None => {
                let port = positif("DB_PORT", 3306)?;
                MySqlConnectOptions::new()
                    .host(&texte("DB_HOST", "127.0.0.1"))
                    .port(
                        u16::try_from(port).map_err(|_| ErreurConfig::NombreInvalide {
                            variable: "DB_PORT",
                            valeur: port.to_string(),
                        })?,
                    )
                    .username(&texte("DB_USERNAME", "root"))
                    .password(&lire("DB_PASSWORD").unwrap_or_default())
                    .database(&texte("DB_DATABASE", "idee"))
            }
        }
        .charset("utf8mb4")
        .collation("utf8mb4_unicode_ci");

        Ok(Self {
            adresse,
            environnement: texte("APP_ENV", "local"),
            url_publique: texte("APP_URL", "http://localhost")
                .trim_end_matches('/')
                .to_owned(),
            base,
            codes: ReglesCodes {
                validite: Duration::from_secs(u64::from(positif("IDEE_OTP_TTL", 10)?) * 60),
                essais_max: positif("IDEE_OTP_MAX_ATTEMPTS", 5)?,
                canal_telephone: texte("IDEE_OTP_DRIVER", "log"),
                canal_email: texte("MAIL_MAILER", "log"),
            },
            sessions: ReglesSessions {
                inactivite_jours: entier("IDEE_SESSION_IDLE_DAYS", 90)?,
                duree_max_jours: entier("IDEE_SESSION_MAX_DAYS", 365)?,
            },
            cout_bcrypt: positif("BCRYPT_ROUNDS", 12)?,
            prefixe_jetons: lire("SANCTUM_TOKEN_PREFIX").unwrap_or_default(),
            relais_laravel: valeur("IDEE_LARAVEL_URL")
                .map(|url| url.trim_end_matches('/').to_owned()),
        })
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    fn avec(variables: &[(&str, &str)]) -> Result<Config, ErreurConfig> {
        Config::depuis(|nom| {
            variables
                .iter()
                .find(|(cle, _)| *cle == nom)
                .map(|(_, valeur)| (*valeur).to_owned())
        })
    }

    #[test]
    fn valeurs_par_defaut_sans_variable() {
        let config = avec(&[]).unwrap();

        assert_eq!(config.adresse.to_string(), ADRESSE_PAR_DEFAUT);
        assert_eq!(config.environnement, "local");
        assert_eq!(config.codes.validite, Duration::from_secs(600));
        assert_eq!(config.codes.essais_max, 5);
        assert_eq!(config.sessions.inactivite_jours, 90);
        assert_eq!(config.sessions.duree_max_jours, 365);
        assert_eq!(config.cout_bcrypt, 12);
        assert_eq!(config.relais_laravel, None);
    }

    #[test]
    fn lit_les_variables_de_laravel() {
        let config = avec(&[
            ("IDEE_SERVEUR_ADRESSE", "0.0.0.0:9000"),
            ("APP_ENV", "production"),
            ("APP_URL", "https://idee.sn/"),
            ("IDEE_OTP_TTL", "15"),
            ("IDEE_SESSION_IDLE_DAYS", "30"),
            ("IDEE_LARAVEL_URL", "http://127.0.0.1:8000/"),
        ])
        .unwrap();

        assert_eq!(config.adresse.port(), 9000);
        assert_eq!(config.environnement, "production");
        assert_eq!(config.url_publique, "https://idee.sn");
        assert_eq!(config.codes.validite, Duration::from_secs(900));
        assert_eq!(config.sessions.inactivite_jours, 30);
        assert_eq!(
            config.relais_laravel.as_deref(),
            Some("http://127.0.0.1:8000")
        );
    }

    #[test]
    fn choisit_le_canal_selon_l_identifiant() {
        let config = avec(&[("IDEE_OTP_DRIVER", "whatsapp")]).unwrap();

        assert_eq!(config.codes.canal_pour("+221771234567"), "whatsapp");
        assert_eq!(config.codes.canal_pour("awa@exemple.sn"), "log");
    }

    #[test]
    fn refuse_une_adresse_invalide() {
        let erreur = avec(&[("IDEE_SERVEUR_ADRESSE", "pas une adresse")]).unwrap_err();

        assert_eq!(
            erreur,
            ErreurConfig::AdresseInvalide("pas une adresse".to_owned())
        );
    }

    #[test]
    fn refuse_un_nombre_invalide() {
        let erreur = avec(&[("IDEE_OTP_TTL", "dix")]).unwrap_err();

        assert_eq!(
            erreur,
            ErreurConfig::NombreInvalide {
                variable: "IDEE_OTP_TTL",
                valeur: "dix".to_owned()
            }
        );
    }

    #[test]
    fn refuse_une_adresse_de_base_invalide() {
        let erreur = avec(&[("DATABASE_URL", "postgres://ailleurs")]).unwrap_err();

        assert!(matches!(erreur, ErreurConfig::BaseInvalide(_)));
    }
}
