//! Lecture et validation des données envoyées par l'application, avec les
//! mêmes habitudes que Laravel : un corps vide ou illisible vaut un objet
//! vide, les textes sont rognés, un texte vide vaut `null`, et les erreurs
//! gardent l'ordre des champs.

use axum::body::Bytes;
use axum::extract::{FromRequest, Request};
use serde::ser::{Serialize, SerializeMap, Serializer};
use serde_json::{Map, Value};

use crate::erreurs::ErreurApp;

/// Erreurs de validation, champ par champ, au format de Laravel
/// (`{"phone": ["…"], "code": ["…"]}`).
#[derive(Debug, Default, PartialEq)]
pub struct ErreursValidation(Vec<(String, Vec<String>)>);

impl ErreursValidation {
    pub fn un(champ: &str, message: impl Into<String>) -> Self {
        let mut erreurs = Self::default();
        erreurs.ajouter(champ, message);
        erreurs
    }

    pub fn ajouter(&mut self, champ: &str, message: impl Into<String>) {
        match self.0.iter_mut().find(|(nom, _)| nom == champ) {
            Some((_, messages)) => messages.push(message.into()),
            None => self.0.push((champ.to_owned(), vec![message.into()])),
        }
    }

    pub fn est_vide(&self) -> bool {
        self.0.is_empty()
    }

    pub fn premier_message(&self) -> &str {
        self.0
            .first()
            .and_then(|(_, messages)| messages.first())
            .map_or("Les données envoyées sont invalides.", String::as_str)
    }

    pub fn messages(&self, champ: &str) -> Option<&[String]> {
        self.0
            .iter()
            .find(|(nom, _)| nom == champ)
            .map(|(_, messages)| messages.as_slice())
    }

    /// Rend les données validées, ou les erreurs s'il y en a.
    pub fn ou<T>(self, valeur: T) -> Result<T, ErreurApp> {
        if self.est_vide() {
            Ok(valeur)
        } else {
            Err(ErreurApp::Validation(self))
        }
    }
}

impl Serialize for ErreursValidation {
    fn serialize<S: Serializer>(&self, serializer: S) -> Result<S::Ok, S::Error> {
        let mut carte = serializer.serialize_map(Some(self.0.len()))?;
        for (champ, messages) in &self.0 {
            carte.serialize_entry(champ, messages)?;
        }
        carte.end()
    }
}

/// Valeur d'un champ, après le nettoyage fait par Laravel.
#[derive(Debug, Clone, PartialEq)]
pub enum Champ {
    /// Absent, `null`, ou texte vide.
    Absent,
    Texte(String),
    /// Nombre, booléen, liste ou objet.
    AutreType,
}

impl Champ {
    pub fn est_absent(&self) -> bool {
        matches!(self, Champ::Absent)
    }

    pub fn texte(&self) -> Option<&str> {
        match self {
            Champ::Texte(texte) => Some(texte),
            _ => None,
        }
    }
}

/// Corps JSON d'une requête.
#[derive(Debug, Default)]
pub struct Corps(Map<String, Value>);

impl Corps {
    pub fn depuis_octets(octets: &[u8]) -> Self {
        match serde_json::from_slice(octets) {
            Ok(Value::Object(carte)) => Self(carte),
            _ => Self::default(),
        }
    }

    pub fn champ(&self, nom: &str) -> Champ {
        match self.0.get(nom) {
            None | Some(Value::Null) => Champ::Absent,
            Some(Value::String(texte)) => match texte.trim() {
                "" => Champ::Absent,
                rogne => Champ::Texte(rogne.to_owned()),
            },
            Some(_) => Champ::AutreType,
        }
    }
}

impl<S: Send + Sync> FromRequest<S> for Corps {
    type Rejection = ErreurApp;

    async fn from_request(requete: Request, etat: &S) -> Result<Self, Self::Rejection> {
        let octets = Bytes::from_request(requete, etat)
            .await
            .map_err(|erreur| ErreurApp::Interne(format!("corps illisible : {erreur}")))?;
        Ok(Self::depuis_octets(&octets))
    }
}

/// Règles « nullable|string|max:N » : rend le texte, ou note l'erreur.
pub fn texte_facultatif(
    corps: &Corps,
    erreurs: &mut ErreursValidation,
    champ: &str,
    libelle: &str,
    longueur_max: usize,
) -> Option<String> {
    match corps.champ(champ) {
        Champ::Absent => None,
        Champ::AutreType => {
            erreurs.ajouter(champ, format!("{libelle} doit être un texte."));
            None
        }
        Champ::Texte(texte) if texte.chars().count() > longueur_max => {
            erreurs.ajouter(
                champ,
                format!("{libelle} ne doit pas dépasser {longueur_max} caractères."),
            );
            None
        }
        Champ::Texte(texte) => Some(texte),
    }
}

/// Règle « email » de Laravel, simplifiée : une seule arobase, une partie
/// locale, un domaine aux étiquettes non vides, aucun espace.
pub fn est_email(adresse: &str) -> bool {
    let Some((locale, domaine)) = adresse.split_once('@') else {
        return false;
    };
    !locale.is_empty()
        && !domaine.contains('@')
        && !adresse.chars().any(char::is_whitespace)
        && domaine.split('.').all(|etiquette| !etiquette.is_empty())
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn nettoie_les_champs_comme_laravel() {
        let corps = Corps::depuis_octets(
            br#"{"phone": "  77 123 45 67 ", "email": "   ", "code": 123456, "name": null}"#,
        );

        assert_eq!(
            corps.champ("phone"),
            Champ::Texte("77 123 45 67".to_owned())
        );
        assert_eq!(corps.champ("email"), Champ::Absent);
        assert_eq!(corps.champ("code"), Champ::AutreType);
        assert_eq!(corps.champ("name"), Champ::Absent);
        assert_eq!(corps.champ("absent"), Champ::Absent);
    }

    #[test]
    fn un_corps_illisible_vaut_un_objet_vide() {
        assert_eq!(
            Corps::depuis_octets(b"pas du json").champ("phone"),
            Champ::Absent
        );
        assert_eq!(
            Corps::depuis_octets(b"[1, 2]").champ("phone"),
            Champ::Absent
        );
    }

    #[test]
    fn les_erreurs_gardent_l_ordre_des_champs() {
        let mut erreurs = ErreursValidation::default();
        erreurs.ajouter("phone", "premier");
        erreurs.ajouter("email", "deuxième");
        erreurs.ajouter("phone", "troisième");

        assert_eq!(erreurs.premier_message(), "premier");
        assert_eq!(
            serde_json::to_string(&erreurs).unwrap(),
            r#"{"phone":["premier","troisième"],"email":["deuxième"]}"#
        );
    }

    #[test]
    fn reconnait_une_adresse_e_mail() {
        assert!(est_email("awa@exemple.sn"));
        assert!(est_email("awa@localhost"));
        assert!(!est_email("awa"));
        assert!(!est_email("@exemple.sn"));
        assert!(!est_email("awa@exemple..sn"));
        assert!(!est_email("awa diop@exemple.sn"));
    }
}
