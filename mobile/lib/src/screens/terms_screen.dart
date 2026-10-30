import 'package:flutter/material.dart';

import '../api.dart';
import '../session.dart';
import '../widgets.dart';

/// Acceptation des conditions d'utilisation et de la charte communautaire, avant le reste.
class TermsScreen extends StatefulWidget {
  const TermsScreen({super.key, required this.session});

  final Session session;

  @override
  State<TermsScreen> createState() => _TermsScreenState();
}

class _TermsScreenState extends State<TermsScreen> {
  bool _accepted = false;
  bool _busy = false;

  static const _rules = [
    'Un investisseur ou une institution ne demande jamais d\'argent à un porteur de projet pour étudier son dossier.',
    'Aucun argent entre membres ne passe par IDEE, et IDEE ne propose jamais de parts ou d\'actions au public.',
    'Chaque demande de contact reçoit une réponse, même un refus.',
    'Votre téléphone et votre e-mail ne sont jamais affichés aux autres membres.',
    'Les arnaques, faux profils et envois en masse sont sanctionnés, après décision d\'un modérateur.',
  ];

  Future<void> _accept() async {
    setState(() => _busy = true);
    try {
      await widget.session.acceptTerms();
    } on ApiException catch (error) {
      if (mounted) {
        setState(() => _busy = false);
        showError(context, error);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Bienvenue sur IDEE')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        const Text('Avant de commencer, voici les règles qui protègent chaque membre.', style: TextStyle(fontSize: 16)),
        const SizedBox(height: 12),
        for (final rule in _rules)
          Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
              const Text('•  '),
              Expanded(child: Text(rule)),
            ]),
          ),
        const SizedBox(height: 8),
        Text(
          'Textes complets : $apiUrl/conditions-d-utilisation, $apiUrl/charte et $apiUrl/confidentialite.',
          style: const TextStyle(fontSize: 13, color: Colors.black54),
        ),
        const SizedBox(height: 12),
        CheckboxListTile(
          contentPadding: EdgeInsets.zero,
          controlAffinity: ListTileControlAffinity.leading,
          value: _accepted,
          onChanged: (value) => setState(() => _accepted = value ?? false),
          title: const Text('J\'ai lu et j\'accepte les conditions d\'utilisation et la charte communautaire.'),
        ),
        const SizedBox(height: 8),
        FilledButton(
          style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(48)),
          onPressed: _accepted && !_busy ? _accept : null,
          child: const Text('Continuer'),
        ),
        TextButton(onPressed: widget.session.logout, child: const Text('Se déconnecter')),
      ]),
    );
  }
}
