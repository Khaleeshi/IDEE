import 'package:flutter/material.dart';

import '../models.dart';
import '../session.dart';
import '../widgets.dart';

/// Parcours guidés étape par étape (PG-01 à PG-05).
class JourneysScreen extends StatelessWidget {
  const JourneysScreen({super.key, required this.session});

  final Session session;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Parcours')),
      body: Loader<List<Journey>>(
        load: () async => ((await session.api.get('journeys'))['data'] as List).map((item) => Journey.fromJson(item as Map<String, dynamic>)).toList(),
        builder: (context, journeys, _) => ListView.separated(
          padding: const EdgeInsets.all(16),
          itemCount: journeys.length,
          separatorBuilder: (_, _) => const SizedBox(height: 12),
          itemBuilder: (context, index) {
            final journey = journeys[index];
            return Card(
              margin: EdgeInsets.zero,
              child: ListTile(
                contentPadding: const EdgeInsets.all(16),
                title: Text(journey.title, style: const TextStyle(fontWeight: FontWeight.w600)),
                subtitle: Text('${journey.situation ?? ''}\n${journey.stepsCount} étapes'),
                isThreeLine: true,
                onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => JourneyScreen(session: session, journeyId: journey.id))),
              ),
            );
          },
        ),
      ),
    );
  }
}

class JourneyScreen extends StatelessWidget {
  const JourneyScreen({super.key, required this.session, required this.journeyId});

  final Session session;
  final String journeyId;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Parcours')),
      body: Loader<Journey>(
        load: () async => Journey.fromJson((await session.api.get('journeys/$journeyId'))['data'] as Map<String, dynamic>),
        builder: (context, journey, reload) {
          final following = journey.steps.any((step) => step.status != null);
          return ListView(padding: const EdgeInsets.all(16), children: [
            Text(journey.title, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            Text(journey.summary),
            const SizedBox(height: 12),
            if (!following)
              FilledButton(
                style: FilledButton.styleFrom(backgroundColor: brand, minimumSize: const Size.fromHeight(48)),
                onPressed: () async {
                  try {
                    await session.api.post('journeys/${journey.id}/start');
                    await reload();
                  } catch (error) {
                    if (context.mounted) showError(context, error);
                  }
                },
                child: const Text('Commencer ce parcours'),
              )
            else
              LinearProgressIndicator(value: journey.steps.isEmpty ? 0 : journey.steps.where((step) => step.isDone).length / journey.steps.length, color: brand),
            const SizedBox(height: 12),
            ...journey.steps.asMap().entries.map((entry) {
              final step = entry.value;
              return Card(
                child: ExpansionTile(
                  leading: CircleAvatar(
                    backgroundColor: step.isDone ? brand : Colors.black12,
                    child: Text(step.isDone ? '✓' : '${entry.key + 1}', style: TextStyle(color: step.isDone ? Colors.white : Colors.black87)),
                  ),
                  title: Text(step.title),
                  subtitle: step.status != null ? Text(step.status!.label) : null,
                  childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                  expandedCrossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(step.body),
                    if (step.organisation != null) Padding(padding: const EdgeInsets.only(top: 8), child: Text('Organisme : ${step.organisation}')),
                    if (step.cost != null) Text('Coût indicatif : ${step.cost}'),
                    if (step.delay != null) Text('Délai indicatif : ${step.delay}'),
                    if (following && !step.isDone)
                      Padding(
                        padding: const EdgeInsets.only(top: 8),
                        child: OutlinedButton(
                          onPressed: () async {
                            try {
                              await session.api.patch('journey-steps/${step.id}/progress', {'status': 'terminee'});
                              await reload();
                            } catch (error) {
                              if (context.mounted) showError(context, error);
                            }
                          },
                          child: const Text('Marquer comme terminée'),
                        ),
                      ),
                  ],
                ),
              );
            }),
          ]);
        },
      ),
    );
  }
}
