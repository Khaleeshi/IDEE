import 'package:flutter/material.dart';

import '../models.dart';
import '../session.dart';
import '../widgets.dart';

/// Fiche d'un projet : résumé, besoins, porteur, demande de contact (MR-01).
class ProjectScreen extends StatelessWidget {
  const ProjectScreen({super.key, required this.session, required this.projectId});

  final Session session;
  final String projectId;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Projet')),
      body: Loader<Project>(
        load: () async => Project.fromJson((await session.api.get('projects/$projectId'))['data'] as Map<String, dynamic>),
        builder: (context, project, _) => ListView(padding: const EdgeInsets.all(16), children: [
          Text(project.title, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
          const SizedBox(height: 4),
          Text([project.sector, project.region, project.stage.label].whereType<String>().where((part) => part.isNotEmpty).join(' · '),
              style: const TextStyle(color: Colors.black54)),
          const SizedBox(height: 12),
          Text(project.summary, style: const TextStyle(fontSize: 16)),
          const SizedBox(height: 12),
          Text('Score de préparation : ${project.readinessScore}/100', style: const TextStyle(fontWeight: FontWeight.w600)),
          if (project.owner != null) ...[
            const SizedBox(height: 16),
            Text('Porté par ${project.owner!.name}', style: const TextStyle(fontWeight: FontWeight.w600)),
          ],
          const SizedBox(height: 20),
          const Text('Ce que le projet cherche', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          if (project.needs.isEmpty) const Padding(padding: EdgeInsets.only(top: 8), child: Text('Aucun besoin ouvert pour le moment.')),
          ...project.needs.map((need) => Card(
                child: ListTile(
                  title: Text(need.title),
                  subtitle: Text([need.type.label, if (need.amountFcfa != null) '${need.amountFcfa} FCFA'].join(' · ')),
                ),
              )),
          if (!project.isOwner && project.owner != null) ...[
            const SizedBox(height: 20),
            FilledButton(
              style: FilledButton.styleFrom(backgroundColor: brand, minimumSize: const Size.fromHeight(52)),
              onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ContactScreen(session: session, project: project))),
              child: const Text('Contacter le porteur'),
            ),
          ],
        ]),
      ),
    );
  }
}

/// Demande de contact motivée, d'au moins 100 caractères (RG-02).
class ContactScreen extends StatefulWidget {
  const ContactScreen({super.key, required this.session, required this.project});

  final Session session;
  final Project project;

  @override
  State<ContactScreen> createState() => _ContactScreenState();
}

class _ContactScreenState extends State<ContactScreen> {
  final _message = TextEditingController();
  bool _busy = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Contacter ${widget.project.owner?.name ?? ''}')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        const Text('Présentez-vous et dites précisément ce que vous proposez.'),
        const SizedBox(height: 12),
        TextField(
          controller: _message,
          minLines: 6,
          maxLines: 12,
          onChanged: (_) => setState(() {}),
          decoration: const InputDecoration(border: OutlineInputBorder(), hintText: 'Qui êtes-vous ? Que proposez-vous ?'),
        ),
        const SizedBox(height: 4),
        Text(_message.text.trim().length < 100 ? 'Encore ${100 - _message.text.trim().length} caractère(s) au minimum' : 'Longueur suffisante',
            style: const TextStyle(fontSize: 12, color: Colors.black54)),
        const SizedBox(height: 16),
        FilledButton(
          style: FilledButton.styleFrom(backgroundColor: brand, minimumSize: const Size.fromHeight(52)),
          onPressed: _busy
              ? null
              : () async {
                  setState(() => _busy = true);
                  try {
                    await widget.session.api.post('contact-requests', {
                      'recipient_id': widget.project.owner!.id,
                      'project_id': widget.project.id,
                      'message': _message.text.trim(),
                    });
                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Demande envoyée. Le porteur a 14 jours pour répondre.')));
                      Navigator.of(context).pop();
                    }
                  } catch (error) {
                    if (context.mounted) showError(context, error);
                  } finally {
                    if (mounted) setState(() => _busy = false);
                  }
                },
          child: const Text('Envoyer la demande'),
        ),
        const SizedBox(height: 12),
        const Text('Un investisseur ou une institution ne vous demandera jamais d\'argent pour étudier votre dossier.', style: TextStyle(fontSize: 12, color: Colors.black54)),
      ]),
    );
  }
}
