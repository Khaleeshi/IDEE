import 'package:flutter/material.dart';

import '../models.dart';
import '../session.dart';
import '../widgets.dart';
import 'project_screen.dart';

/// Projets qui cherchent du soutien, avec la recherche par mot-clé (RR-01).
class FeedScreen extends StatefulWidget {
  const FeedScreen({super.key, required this.session});

  final Session session;

  @override
  State<FeedScreen> createState() => _FeedScreenState();
}

class _FeedScreenState extends State<FeedScreen> {
  String _query = '';
  Key _listKey = UniqueKey();

  Future<List<Project>> _load() async {
    final response = await widget.session.api.get('projects', query: {if (_query.isNotEmpty) 'q': _query});
    return (response['data'] as List).map((project) => Project.fromJson(project as Map<String, dynamic>)).toList();
  }

  @override
  Widget build(BuildContext context) {
    const searchBorder = OutlineInputBorder(borderRadius: BorderRadius.all(Radius.circular(14)), borderSide: BorderSide.none);

    return Scaffold(
      body: Column(children: [
        BrandHeader(
          title: 'Projets',
          subtitle: 'Des projets qui cherchent des associés, des compétences ou un financement.',
          child: TextField(
            textInputAction: TextInputAction.search,
            decoration: const InputDecoration(
              hintText: 'Rechercher un projet…',
              prefixIcon: Icon(Icons.search, color: brand),
              border: searchBorder,
              enabledBorder: searchBorder,
              focusedBorder: searchBorder,
            ),
            onSubmitted: (value) => setState(() {
              _query = value.trim();
              _listKey = UniqueKey();
            }),
          ),
        ),
        Expanded(
          child: Loader<List<Project>>(
            key: _listKey,
            load: _load,
            builder: (context, projects, reload) => projects.isEmpty
                ? const EmptyState('Aucun projet ne correspond à cette recherche.', icon: Icons.search_off)
                : ListView.separated(
                    padding: pagePadding(context),
                    itemCount: projects.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 12),
                    itemBuilder: (context, index) => ProjectCard(project: projects[index], session: widget.session),
                  ),
          ),
        ),
      ]),
    );
  }
}

class ProjectCard extends StatelessWidget {
  const ProjectCard({super.key, required this.project, required this.session});

  final Project project;
  final Session session;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.zero,
      child: InkWell(
        onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ProjectScreen(session: session, projectId: project.id))),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            ProjectTags(project: project),
            const SizedBox(height: 10),
            Text(project.title, style: const TextStyle(fontSize: 18, height: 1.25, fontWeight: FontWeight.w700, color: ink)),
            const SizedBox(height: 6),
            Text(project.summary, maxLines: 3, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 14.5, height: 1.4, color: inkSoft)),
            if (project.needs.isNotEmpty) ...[
              const SizedBox(height: 12),
              Wrap(spacing: 6, runSpacing: 6, children: [
                for (final need in project.needs.take(4)) Tag.need('Cherche : ${need.type.label.toLowerCase()}'),
              ]),
            ],
            if (project.owner != null) ...[
              const SizedBox(height: 14),
              const Divider(),
              const SizedBox(height: 12),
              Row(children: [
                Avatar(project.owner!.name, size: 28),
                const SizedBox(width: 8),
                Expanded(child: Text('Porté par ${project.owner!.name}', overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 13, color: muted))),
                const Icon(Icons.arrow_forward, size: 18, color: brand),
              ]),
            ],
          ]),
        ),
      ),
    );
  }
}

/// Secteur, étape et région d'un projet.
class ProjectTags extends StatelessWidget {
  const ProjectTags({super.key, required this.project});

  final Project project;

  @override
  Widget build(BuildContext context) {
    return Wrap(spacing: 6, runSpacing: 6, children: [
      if (project.sector?.isNotEmpty ?? false) Tag.brand(project.sector!),
      if (project.stage.label.isNotEmpty) Tag(project.stage.label),
      if (project.region?.isNotEmpty ?? false) Tag(project.region!, icon: Icons.place_outlined),
    ]);
  }
}
