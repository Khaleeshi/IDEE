//! Numéros de téléphone au format international (E.164), repris de
//! `App\Support\PhoneNumber`. Un numéro sénégalais saisi sans indicatif
//! reçoit le préfixe +221 ; les autres se saisissent avec leur indicatif, ou
//! avec le pays choisi à l'inscription.

use super::pays;

pub const INDICATIF_SENEGAL: &str = "+221";

/// Normalise un numéro saisi ; `None` s'il reste invalide.
pub fn normaliser(saisie: &str, pays_choisi: Option<&str>) -> Option<String> {
    let mut numero: String = saisie
        .trim()
        .chars()
        .filter(|c| !c.is_whitespace() && !matches!(c, '.' | '-' | '(' | ')'))
        .collect();

    if let Some(reste) = numero.strip_prefix("00") {
        numero = format!("+{reste}");
    }

    if let Some(indicatif) = pays_choisi.and_then(pays::indicatif)
        && indicatif != INDICATIF_SENEGAL
        && !numero.starts_with('+')
        && !numero.is_empty()
    {
        // 06 12 34 56 78 depuis la France devient +33612345678 : le 0 national tombe.
        numero = format!("{indicatif}{}", numero.trim_start_matches('0'));
    }

    if est_senegalais_sans_indicatif(&numero) {
        numero = format!("{INDICATIF_SENEGAL}{numero}");
    }

    est_valide(&numero).then_some(numero)
}

/// Format attendu : `+`, un premier chiffre de 1 à 9, puis 7 à 14 chiffres.
pub fn est_valide(numero: &str) -> bool {
    let Some(chiffres) = numero.strip_prefix('+') else {
        return false;
    };
    (8..=15).contains(&chiffres.len())
        && chiffres.bytes().all(|c| c.is_ascii_digit())
        && !chiffres.starts_with('0')
}

/// Neuf chiffres commençant par 3 ou 7 : 77 123 45 67, 33 820 00 00.
fn est_senegalais_sans_indicatif(numero: &str) -> bool {
    numero.len() == 9
        && numero.bytes().all(|c| c.is_ascii_digit())
        && matches!(numero.as_bytes()[0], b'3' | b'7')
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn ajoute_l_indicatif_du_senegal() {
        assert_eq!(
            normaliser("77 123 45 67", None).as_deref(),
            Some("+221771234567")
        );
        assert_eq!(
            normaliser("33.820.00.00", None).as_deref(),
            Some("+221338200000")
        );
    }

    #[test]
    fn garde_un_numero_deja_international() {
        assert_eq!(
            normaliser("+33 6 12 34 56 78", None).as_deref(),
            Some("+33612345678")
        );
        assert_eq!(
            normaliser("00221 77 123 45 67", None).as_deref(),
            Some("+221771234567")
        );
    }

    #[test]
    fn utilise_le_pays_choisi_sans_le_zero_national() {
        assert_eq!(
            normaliser("06 12 34 56 78", Some("FR")).as_deref(),
            Some("+33612345678")
        );
        // Le Sénégal garde sa propre règle.
        assert_eq!(
            normaliser("77 123 45 67", Some("SN")).as_deref(),
            Some("+221771234567")
        );
    }

    #[test]
    fn refuse_un_numero_trop_court_ou_mal_forme() {
        assert_eq!(normaliser("1234", None), None);
        assert_eq!(normaliser("+0123456789", None), None);
        assert_eq!(normaliser("77 12 AB 45 67", None), None);
    }
}
