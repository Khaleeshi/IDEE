/// Pays proposés à la connexion (CP-07), comme sur le site : le Sénégal d'abord,
/// puis la sous-région et la diaspora.
class Country {
  const Country(this.code, this.name, this.dialCode);

  final String code;
  final String name;
  final String dialCode;

  String get label => '$name ($dialCode)';
}

const countries = <Country>[
  Country('SN', 'Sénégal', '+221'),
  Country('ML', 'Mali', '+223'),
  Country('GM', 'Gambie', '+220'),
  Country('GN', 'Guinée', '+224'),
  Country('GW', 'Guinée-Bissau', '+245'),
  Country('MR', 'Mauritanie', '+222'),
  Country('CI', 'Côte d\'Ivoire', '+225'),
  Country('BF', 'Burkina Faso', '+226'),
  Country('NE', 'Niger', '+227'),
  Country('TG', 'Togo', '+228'),
  Country('BJ', 'Bénin', '+229'),
  Country('CM', 'Cameroun', '+237'),
  Country('MA', 'Maroc', '+212'),
  Country('FR', 'France', '+33'),
  Country('BE', 'Belgique', '+32'),
  Country('CH', 'Suisse', '+41'),
  Country('IT', 'Italie', '+39'),
  Country('ES', 'Espagne', '+34'),
  Country('DE', 'Allemagne', '+49'),
  Country('GB', 'Royaume-Uni', '+44'),
  Country('US', 'États-Unis', '+1'),
  Country('CA', 'Canada', '+1'),
];
