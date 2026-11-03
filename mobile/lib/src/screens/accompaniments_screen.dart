import 'package:flutter/material.dart';

import '../session.dart';
import '../widgets.dart';

/// Accompagnements (AC-01 à AC-04) : mentorat et aide bénévole, dans les deux sens.
class AccompanimentsScreen extends StatelessWidget {
  const AccompanimentsScreen({super.key, required this.session});

  final Session session;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Accompagnements')),
      body: Loader<List<Map<String, dynamic>>>(
        load: () async => ((await session.api.get('me/accompaniments'))['data'] as List).cast<Map<String, dynamic>>(),
        builder: (context, items, reload) => items.isEmpty
            ? const EmptyState('Aucun accompagnement. Demandez-en un depuis le profil d\'un coach ou d\'un mentor, sur le site.')
            : ListView(padding: const EdgeInsets.all(16), children: [
                for (final item in items)
                  Card(
                    child: ListTile(
                      title: Text(item['role'] == 'coach' ? (item['requester'] as Map)['name'] as String : (item['coach'] as Map)['name'] as String),
                      subtitle: Text('${(item['kind'] as Map)['label']} · ${item['role'] == 'coach' ? 'je l\'accompagne' : 'on m\'accompagne'}'),
                      trailing: Text((item['status'] as Map)['label'] as String, style: const TextStyle(color: brand, fontWeight: FontWeight.w600)),
                      onTap: () async {
                        await Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => AccompanimentScreen(session: session, id: item['id'] as String)));
                        await reload();
                      },
                    ),
                  ),
              ]),
      ),
    );
  }
}

/// Détail d'un accompagnement et ses actions selon son état.
class AccompanimentScreen extends StatefulWidget {
  const AccompanimentScreen({super.key, required this.session, required this.id});

  final Session session;
  final String id;

  @override
  State<AccompanimentScreen> createState() => _AccompanimentScreenState();
}

class _AccompanimentScreenState extends State<AccompanimentScreen> {
  final _text = TextEditingController();
  int _rating = 5;
  bool _busy = false;
  Key _key = UniqueKey();

  @override
  void dispose() {
    _text.dispose();
    super.dispose();
  }

  Future<void> _act(String path, [Map<String, dynamic>? body]) async {
    setState(() => _busy = true);
    try {
      await widget.session.api.post('accompaniments/${widget.id}/$path', body);
      _text.clear();
      setState(() => _key = UniqueKey());
    } catch (error) {
      if (mounted) showError(context, error);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Accompagnement')),
      body: Loader<Map<String, dynamic>>(
        key: _key,
        load: () async => (await widget.session.api.get('accompaniments/${widget.id}'))['data'] as Map<String, dynamic>,
        builder: (context, item, _) {
          final status = (item['status'] as Map)['value'] as String;
          final isCoach = item['role'] == 'coach';
          final sessions = (item['sessions'] as List).cast<Map<String, dynamic>>();

          return ListView(padding: const EdgeInsets.all(16), children: [
            Text('${(item['kind'] as Map)['label']} · ${(item['status'] as Map)['label']}', style: const TextStyle(color: brand, fontWeight: FontWeight.w600)),
            const SizedBox(height: 8),
            Text(item['objectives'] as String? ?? ''),
            if (item['decline_reason'] != null) Padding(padding: const EdgeInsets.only(top: 8), child: Text('Réponse : ${item['decline_reason']}')),
            const SizedBox(height: 16),
            if (status == 'demandee' && isCoach) ...[
              FilledButton(
                style: FilledButton.styleFrom(backgroundColor: brand, minimumSize: const Size.fromHeight(48)),
                onPressed: _busy ? null : () => _act('respond', {'accepted': true}),
                child: const Text('J\'accepte de l\'accompagner'),
              ),
              const SizedBox(height: 12),
              TextField(controller: _text, decoration: const InputDecoration(labelText: 'Ou déclinez, avec un mot d\'explication', border: OutlineInputBorder())),
              TextButton(onPressed: _busy ? null : () => _act('respond', {'accepted': false, 'reason': _text.text}), child: const Text('Décliner')),
            ],
            if (status == 'demandee' && !isCoach)
              TextButton(onPressed: _busy ? null : () => _act('cancel'), child: const Text('Retirer ma demande')),
            if (sessions.isNotEmpty) ...[
              const Text('Points de suivi', style: TextStyle(fontWeight: FontWeight.w600)),
              for (final session in sessions)
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(session['summary'] as String),
                  subtitle: Text([formatDate(DateTime.parse(session['held_on'] as String)), if (session['next_steps'] != null) 'Ensuite : ${session['next_steps']}'].join(' · ')),
                ),
            ],
            if (status == 'en_cours') ...[
              const SizedBox(height: 8),
              TextField(controller: _text, maxLines: 3, decoration: const InputDecoration(labelText: 'Noter la séance d\'aujourd\'hui', border: OutlineInputBorder())),
              const SizedBox(height: 8),
              FilledButton(
                style: FilledButton.styleFrom(backgroundColor: brand, minimumSize: const Size.fromHeight(48)),
                onPressed: _busy ? null : () => _act('sessions', {'held_on': DateTime.now().toIso8601String().substring(0, 10), 'summary': _text.text}),
                child: const Text('Enregistrer le point de suivi'),
              ),
              TextButton(onPressed: _busy ? null : () => _act('complete'), child: const Text('Mettre fin à l\'accompagnement')),
            ],
            if (status == 'terminee' && item['review'] == null && !isCoach) ...[
              const Text('Votre avis', style: TextStyle(fontWeight: FontWeight.w600)),
              Row(children: [
                for (var star = 1; star <= 5; star++)
                  IconButton(
                    tooltip: '$star sur 5',
                    icon: Icon(star <= _rating ? Icons.star : Icons.star_border, color: brand),
                    onPressed: () => setState(() => _rating = star),
                  ),
              ]),
              TextField(controller: _text, maxLines: 3, decoration: const InputDecoration(labelText: 'Votre avis (facultatif)', border: OutlineInputBorder())),
              const SizedBox(height: 8),
              FilledButton(
                style: FilledButton.styleFrom(backgroundColor: brand, minimumSize: const Size.fromHeight(48)),
                onPressed: _busy ? null : () => _act('review', {'rating': _rating, 'comment': _text.text}),
                child: const Text('Publier mon avis'),
              ),
            ],
            if (item['review'] != null) ...[
              const SizedBox(height: 8),
              Text('Avis : ${'★' * ((item['review'] as Map)['rating'] as int)}', style: const TextStyle(color: brand)),
              if ((item['review'] as Map)['comment'] != null) Text((item['review'] as Map)['comment'] as String),
            ],
          ]);
        },
      ),
    );
  }
}
