//! Limites de tentatives (section 4 du cahier des charges), comme le
//! `RateLimiter` de Laravel : chaque clé compte ses essais dans une fenêtre
//! qui commence au premier essai.
//!
//! Les compteurs vivent en mémoire : le serveur est un seul programme, et un
//! redémarrage les remet simplement à zéro.

use std::collections::HashMap;
use std::sync::Mutex;
use std::time::{Duration, Instant};

/// Au-delà, les compteurs dont la fenêtre est finie sont oubliés.
const COMPTEURS_AVANT_NETTOYAGE: usize = 10_000;

#[derive(Debug, Clone)]
pub struct Limite {
    pub cle: String,
    pub maximum: u32,
    pub fenetre: Duration,
}

impl Limite {
    pub fn new(cle: String, maximum: u32, fenetre: Duration) -> Self {
        Self {
            cle,
            maximum,
            fenetre,
        }
    }
}

struct Compteur {
    debut: Instant,
    fenetre: Duration,
    essais: u32,
}

impl Compteur {
    fn en_cours(&self, maintenant: Instant) -> bool {
        maintenant.duration_since(self.debut) < self.fenetre
    }
}

#[derive(Default)]
pub struct Limiteur {
    compteurs: Mutex<HashMap<String, Compteur>>,
}

impl Limiteur {
    /// Vérifie toutes les limites ; si aucune n'est atteinte, compte l'essai
    /// dans chacune. Sinon, rend le délai avant de pouvoir réessayer.
    pub fn essayer(&self, limites: &[Limite]) -> Result<(), Duration> {
        self.essayer_a(limites, Instant::now())
    }

    pub fn essayer_a(&self, limites: &[Limite], maintenant: Instant) -> Result<(), Duration> {
        let mut compteurs = self
            .compteurs
            .lock()
            .unwrap_or_else(|erreur| erreur.into_inner());

        if compteurs.len() > COMPTEURS_AVANT_NETTOYAGE {
            compteurs.retain(|_, compteur| compteur.en_cours(maintenant));
        }

        for limite in limites {
            if let Some(compteur) = compteurs.get(&limite.cle)
                && compteur.en_cours(maintenant)
                && compteur.essais >= limite.maximum
            {
                return Err(compteur.fenetre - maintenant.duration_since(compteur.debut));
            }
        }

        for limite in limites {
            let compteur = compteurs
                .entry(limite.cle.clone())
                .or_insert_with(|| Compteur {
                    debut: maintenant,
                    fenetre: limite.fenetre,
                    essais: 0,
                });
            if !compteur.en_cours(maintenant) {
                *compteur = Compteur {
                    debut: maintenant,
                    fenetre: limite.fenetre,
                    essais: 0,
                };
            }
            compteur.essais += 1;
        }

        Ok(())
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    const MINUTE: Duration = Duration::from_secs(60);

    fn une(cle: &str, maximum: u32) -> Vec<Limite> {
        vec![Limite::new(cle.to_owned(), maximum, MINUTE)]
    }

    #[test]
    fn refuse_au_dela_du_maximum_puis_rouvre_la_fenetre() {
        let limiteur = Limiteur::default();
        let debut = Instant::now();

        assert!(limiteur.essayer_a(&une("code", 1), debut).is_ok());
        assert_eq!(
            limiteur.essayer_a(&une("code", 1), debut + Duration::from_secs(20)),
            Err(Duration::from_secs(40))
        );
        assert!(limiteur.essayer_a(&une("code", 1), debut + MINUTE).is_ok());
    }

    #[test]
    fn les_cles_sont_comptees_a_part() {
        let limiteur = Limiteur::default();
        let maintenant = Instant::now();

        assert!(limiteur.essayer_a(&une("a", 1), maintenant).is_ok());
        assert!(limiteur.essayer_a(&une("b", 1), maintenant).is_ok());
        assert!(limiteur.essayer_a(&une("a", 1), maintenant).is_err());
    }

    #[test]
    fn un_refus_ne_compte_dans_aucune_limite() {
        let limiteur = Limiteur::default();
        let maintenant = Instant::now();
        let deux = |cle_ip: &str| {
            vec![
                Limite::new("code:+221770000001".to_owned(), 1, MINUTE),
                Limite::new(cle_ip.to_owned(), 2, MINUTE),
            ]
        };

        assert!(limiteur.essayer_a(&deux("ip"), maintenant).is_ok());
        // Refusé par la limite du numéro : l'IP n'est pas comptée.
        assert!(limiteur.essayer_a(&deux("ip"), maintenant).is_err());
        assert!(limiteur.essayer_a(&une("ip", 2), maintenant).is_ok());
    }
}
