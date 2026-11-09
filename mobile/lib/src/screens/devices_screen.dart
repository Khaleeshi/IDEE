import 'package:flutter/material.dart';

import '../models.dart';
import '../session.dart';
import '../widgets.dart';

/// Mes appareils connectés (CP-06) : déconnecter un téléphone perdu ou inconnu.
class DevicesScreen extends StatelessWidget {
  const DevicesScreen({super.key, required this.session});

  final Session session;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Mes appareils')),
      body: Loader<List<Map<String, dynamic>>>(
        load: () async => ((await session.api.get('me/devices'))['data'] as List).cast<Map<String, dynamic>>(),
        builder: (context, devices, reload) => ListView(padding: const EdgeInsets.all(16), children: [
          const Text('Où votre compte est ouvert. Un appareil inutilisé pendant 90 jours est déconnecté automatiquement.', style: TextStyle(color: muted)),
          const SizedBox(height: 12),
          for (final device in devices)
            Card(
              child: ListTile(
                leading: Icon(device['kind'] == 'application' ? Icons.phone_android : Icons.language),
                title: Text(device['name'] as String? ?? 'Appareil'),
                subtitle: Text([
                  device['kind'] == 'application' ? 'Application' : 'Site web',
                  if (device['last_active_at'] != null) 'actif le ${formatDate(DateTime.parse(device['last_active_at'] as String).toLocal())}',
                ].join(' · ')),
                trailing: device['current'] == true
                    ? const StatusChip(Label('courant', 'Cet appareil'))
                    : TextButton(
                        style: TextButton.styleFrom(foregroundColor: Colors.red.shade700),
                        onPressed: () async {
                          try {
                            await session.api.delete('me/devices/${device['id']}');
                            await reload();
                          } catch (error) {
                            if (context.mounted) showError(context, error);
                          }
                        },
                        child: const Text('Déconnecter'),
                      ),
              ),
            ),
          if (devices.any((device) => device['current'] != true)) ...[
            const SizedBox(height: 12),
            OutlinedButton(
              style: OutlinedButton.styleFrom(minimumSize: const Size.fromHeight(48), foregroundColor: Colors.red.shade700),
              onPressed: () async {
                try {
                  final response = await session.api.post('me/devices/logout-others');
                  await reload();
                  if (context.mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(response['message'] as String? ?? 'Fait.')));
                  }
                } catch (error) {
                  if (context.mounted) showError(context, error);
                }
              },
              child: const Text('Déconnecter tous les autres appareils'),
            ),
          ],
        ]),
      ),
    );
  }
}
