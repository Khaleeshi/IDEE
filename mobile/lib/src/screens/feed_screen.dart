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
    return Scaffold(
      appBar: AppBar(title: const Text('Projets')),
      body: Column(children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
          child: TextField(
            textInputAction: TextInputAction.search,
            decoration: const InputDecoration(hintText: 'Rechercher un projet…', prefixIcon: Icon(Icons.search), border: OutlineInputBorder()),
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
                ? const EmptyState('Aucun projet ne correspond à cette recherche.')
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
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
        borderRadius: BorderRadius.circular(12),
        onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ProjectScreen(session: session, projectId: project.id))),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text([project.sector, project.region, project.stage.label].whereType<String>().where((part) => part.isNotEmpty).join(' · '),
                style: const TextStyle(fontSize: 12, color: Colors.black54)),
            const SizedBox(height: 4),
            Text(project.title, style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w600)),
            const SizedBox(height: 4),
            Text(project.summary, maxLines: 3, overflow: TextOverflow.ellipsis),
            if (project.needs.isNotEmpty) ...[
              const SizedBox(height: 8),
              Wrap(spacing: 6, runSpacing: 6, children: project.needs.map((need) => Chip(label: Text('Cherche : ${need.type.label.toLowerCase()}'), visualDensity: VisualDensity.compact)).toList()),
            ],
          ]),
        ),
      ),
    );
  }
}
