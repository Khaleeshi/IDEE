//! Pays proposés à l'inscription (CP-07), repris de `App\Support\Countries` :
//! le Sénégal d'abord, puis la sous-région et les pays de la diaspora.

/// Code ISO, nom et indicatif téléphonique.
pub const PAYS: [(&str, &str, &str); 22] = [
    ("SN", "Sénégal", "+221"),
    ("ML", "Mali", "+223"),
    ("GM", "Gambie", "+220"),
    ("GN", "Guinée", "+224"),
    ("GW", "Guinée-Bissau", "+245"),
    ("MR", "Mauritanie", "+222"),
    ("CI", "Côte d'Ivoire", "+225"),
    ("BF", "Burkina Faso", "+226"),
    ("NE", "Niger", "+227"),
    ("TG", "Togo", "+228"),
    ("BJ", "Bénin", "+229"),
    ("CM", "Cameroun", "+237"),
    ("MA", "Maroc", "+212"),
    ("FR", "France", "+33"),
    ("BE", "Belgique", "+32"),
    ("CH", "Suisse", "+41"),
    ("IT", "Italie", "+39"),
    ("ES", "Espagne", "+34"),
    ("DE", "Allemagne", "+49"),
    ("GB", "Royaume-Uni", "+44"),
    ("US", "États-Unis", "+1"),
    ("CA", "Canada", "+1"),
];

/// Indicatif d'un pays d'après son code ISO, en majuscules ou en minuscules.
pub fn indicatif(code: &str) -> Option<&'static str> {
    PAYS.iter()
        .find(|(iso, _, _)| iso.eq_ignore_ascii_case(code))
        .map(|(_, _, indicatif)| *indicatif)
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn trouve_l_indicatif_d_un_pays() {
        assert_eq!(indicatif("FR"), Some("+33"));
        assert_eq!(indicatif("sn"), Some("+221"));
        assert_eq!(indicatif("XX"), None);
    }
}
