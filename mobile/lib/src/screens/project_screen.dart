import 'package:flutter/material.dart';

import '../models.dart';
import '../session.dart';
import '../widgets.dart';
import 'feed_screen.dart' show ProjectTags;

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
        builder: (context, project, _) => Column(children: [
          Expanded(
            child: ListView(padding: pagePadding(context, top: 20), children: [
              ProjectTags(project: project),
              const SizedBox(height: 12),
              Text(project.title, style: const TextStyle(fontSize: 26, height: 1.2, fontWeight: FontWeight.w800, color: ink, letterSpacing: -0.3)),
              const SizedBox(height: 10),
              Text(project.summary, style: const TextStyle(fontSize: 16, height: 1.5, color: inkSoft)),
              const SizedBox(height: 20),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Row(children: [
                      const Expanded(child: Text('Score de préparation', style: TextStyle(fontWeight: FontWeight.w600, color: ink))),
                      Text('${project.readinessScore}/100', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16, color: brand)),
                    ]),
                    const SizedBox(height: 10),
                    LinearProgressIndicator(value: project.readinessScore.clamp(0, 100) / 100, minHeight: 8, borderRadius: BorderRadius.circular(8)),
                  ]),
                ),
              ),
              if (project.owner != null)
                Card(
                  child: ListTile(
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                    leading: Avatar(project.owner!.name, size: 44),
                    title: Text(project.owner!.name, style: const TextStyle(fontWeight: FontWeight.w700, color: ink)),
                    subtitle: Text(['Porteur du projet', if (project.owner!.region != null) project.owner!.region!].join(' · ')),
                  ),
                ),
              const SectionTitle('Ce que le projet cherche'),
              if (project.needs.isEmpty) const Text('Aucun besoin ouvert pour le moment.', style: TextStyle(color: muted)),
              ...project.needs.map((need) => Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Container(
                          width: 40,
                          height: 40,
                          decoration: const BoxDecoration(color: needTint, shape: BoxShape.circle),
                          child: Icon(_needIcon(need.type.value), size: 20, color: needInk),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Text(need.title, style: const TextStyle(fontWeight: FontWeight.w700, color: ink, height: 1.3)),
                            const SizedBox(height: 4),
                            Text([need.type.label, if (need.amountFcfa != null) '${formatFcfa(need.amountFcfa!)} (indicatif)'].join(' · '),
                                style: const TextStyle(color: inkSoft)),
                            if (need.notice != null)
                              Padding(padding: const EdgeInsets.only(top: 6), child: Text(need.notice!, style: const TextStyle(fontSize: 12, color: muted))),
                          ]),
                        ),
                      ]),
                    ),
                  )),
            ]),
          ),
          // Bouton toujours visible en bas de la fiche.
          if (!project.isOwner && project.owner != null)
            Container(
              decoration: const BoxDecoration(color: Colors.white, border: Border(top: BorderSide(color: line))),
              child: SafeArea(
                top: false,
                child: Padding(
                  padding: EdgeInsets.fromLTRB(pagePadding(context).left, 12, pagePadding(context).right, 12),
                  child: FilledButton.icon(
                    style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(52)),
                    icon: const Icon(Icons.mail_outline),
                    onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ContactScreen(session: session, project: project))),
                    label: const Text('Contacter le porteur'),
                  ),
                ),
              ),
            ),
        ]),
      ),
    );
  }
}

/// Icône d'un type de besoin (valeurs de NeedType côté serveur).
IconData _needIcon(String type) => switch (type) {
      'financement' => Icons.savings_outlined,
      'associe' => Icons.group_add_outlined,
      'competence' => Icons.construction_outlined,
      'clients' => Icons.storefront_outlined,
      'fournisseur' => Icons.local_shipping_outlined,
      'local' => Icons.home_work_outlined,
      'materiel' => Icons.handyman_outlined,
      'mentor' => Icons.school_outlined,
      _ => Icons.help_outline,
    };

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
          decoration: const InputDecoration(hintText: 'Qui êtes-vous ? Que proposez-vous ?'),
        ),
        const SizedBox(height: 4),
        Text(_message.text.trim().length < 100 ? 'Encore ${100 - _message.text.trim().length} caractère(s) au minimum' : 'Longueur suffisante',
            style: const TextStyle(fontSize: 12, color: muted)),
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
        const Text('Un investisseur ou une institution ne vous demandera jamais d\'argent pour étudier votre dossier.', style: TextStyle(fontSize: 12, color: muted)),
      ]),
    );
  }
}
