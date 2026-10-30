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
        ]),
      ),
    );
  }
}
