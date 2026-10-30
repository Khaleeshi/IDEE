import 'package:flutter/material.dart';

import '../api.dart';
import '../session.dart';
import '../widgets.dart';

/// Profil du membre et déconnexion. La modification complète se fait sur le site.
class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key, required this.session});

  final Session session;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Profil')),
      body: Loader<Map<String, dynamic>>(
        load: () async => (await session.api.get('me'))['data'] as Map<String, dynamic>,
        builder: (context, me, _) => ListView(padding: const EdgeInsets.all(16), children: [
          Text(me['name'] as String? ?? '', style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
          const SizedBox(height: 4),
          Text([me['phone'], me['email'], me['region']].whereType<String>().join(' · '), style: const TextStyle(color: Colors.black54)),
          const SizedBox(height: 16),
          ListTile(
            contentPadding: EdgeInsets.zero,
            title: const Text('Demandes de contact cette semaine'),
            trailing: Text('${me['contact_requests_sent_this_week']} / ${me['weekly_contact_quota']}'),
          ),
          ListTile(
            contentPadding: EdgeInsets.zero,
            title: const Text('Vérification'),
            trailing: Text((me['verification'] as Map?)?['label'] as String? ?? ''),
          ),
          const SizedBox(height: 8),
          Text('Pour modifier votre profil, vos rôles ou vos préférences, ouvrez $apiUrl/profil.', style: const TextStyle(fontSize: 13, color: Colors.black54)),
          const SizedBox(height: 24),
          OutlinedButton(
            style: OutlinedButton.styleFrom(minimumSize: const Size.fromHeight(48), foregroundColor: Colors.red.shade700),
            onPressed: session.logout,
            child: const Text('Se déconnecter'),
          ),
          const SizedBox(height: 8),
          TextButton(
            style: TextButton.styleFrom(minimumSize: const Size.fromHeight(48), foregroundColor: Colors.red.shade700),
            onPressed: () => showDialog<void>(context: context, builder: (_) => DeleteAccountDialog(session: session)),
            child: const Text('Supprimer mon compte'),
          ),
        ]),
      ),
    );
  }
}

/// Suppression du compte depuis l'application, exigée par Apple (règle 5.1.1).
class DeleteAccountDialog extends StatefulWidget {
  const DeleteAccountDialog({super.key, required this.session});

  final Session session;

  @override
  State<DeleteAccountDialog> createState() => _DeleteAccountDialogState();
}

class _DeleteAccountDialogState extends State<DeleteAccountDialog> {
  final _confirmation = TextEditingController();
  bool _busy = false;
  String? _error;

  @override
  void dispose() {
    _confirmation.dispose();
    super.dispose();
  }

  Future<void> _delete() async {
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await widget.session.deleteAccount(_confirmation.text);
      if (mounted) Navigator.of(context).pop();
    } on ApiException catch (error) {
      setState(() {
        _busy = false;
        _error = error.message;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Supprimer mon compte'),
      content: SingleChildScrollView(
        child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
          const Text(
            'Votre profil, vos projets avec leurs documents, vos alertes et vos parcours seront effacés pour de bon. '
            'Les messages déjà envoyés restent chez vos interlocuteurs, sous le nom « Membre supprimé ».',
          ),
          const SizedBox(height: 8),
          Text('Pour garder une copie de vos données, téléchargez-les d\'abord depuis $apiUrl/profil.', style: const TextStyle(fontSize: 13, color: Colors.black54)),
          const SizedBox(height: 12),
          TextField(
            controller: _confirmation,
            autocorrect: false,
            textCapitalization: TextCapitalization.characters,
            decoration: InputDecoration(labelText: 'Écrivez SUPPRIMER pour confirmer', errorText: _error),
          ),
        ]),
      ),
      actions: [
        TextButton(onPressed: _busy ? null : () => Navigator.of(context).pop(), child: const Text('Annuler')),
        FilledButton(
          style: FilledButton.styleFrom(backgroundColor: Colors.red.shade700),
          onPressed: _busy ? null : _delete,
          child: const Text('Supprimer'),
        ),
      ],
    );
  }
}
