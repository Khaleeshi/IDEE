import 'package:flutter/material.dart';

import '../session.dart';
import '../widgets.dart';

/// Mes métiers (PM-02, PM-03) : un compte, plusieurs profils professionnels.
class ProfessionsScreen extends StatelessWidget {
  const ProfessionsScreen({super.key, required this.session});

  final Session session;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Mes métiers')),
      body: Loader<List<Map<String, dynamic>>>(
        load: () async => ((await session.api.get('me/profiles'))['data'] as List).cast<Map<String, dynamic>>(),
        builder: (context, profiles, reload) => ListView(padding: const EdgeInsets.all(16), children: [
          const Text('Coach, chef de projet, comptable… Ajoutez un profil par métier, jusqu\'à 5.', style: TextStyle(color: muted)),
          const SizedBox(height: 12),
          if (profiles.isEmpty)
            const Padding(padding: EdgeInsets.symmetric(vertical: 24), child: Text('Aucun métier pour l\'instant. Le premier ajouté devient votre profil principal.', textAlign: TextAlign.center)),
          for (final profile in profiles)
            Card(
              child: ListTile(
                title: Text(profile['label'] as String? ?? ''),
                subtitle: Text([
                  offerLabels(profile).join(' · '),
                  if (profile['is_public'] != true && profile['active'] == true) 'visible après vérification de votre identité',
                  if (profile['active'] != true) 'en pause',
                ].where((part) => part.isNotEmpty).join('\n')),
                isThreeLine: profile['is_public'] != true,
                trailing: profile['is_primary'] == true ? const Icon(Icons.star, color: brand, semanticLabel: 'Profil principal') : null,
                onTap: () async {
                  final changed = await Navigator.of(context).push<bool>(MaterialPageRoute(builder: (_) => ProfileForm(session: session, profile: profile)));
                  if (changed == true) await reload();
                },
              ),
            ),
          const SizedBox(height: 12),
          if (profiles.length < 5)
            FilledButton.icon(
              style: FilledButton.styleFrom(backgroundColor: brand, minimumSize: const Size.fromHeight(48)),
              icon: const Icon(Icons.add),
              label: const Text('Ajouter un métier'),
              onPressed: () async {
                final changed = await Navigator.of(context).push<bool>(MaterialPageRoute(builder: (_) => ProfileForm(session: session)));
                if (changed == true) await reload();
              },
            ),
        ]),
      ),
    );
  }
}

/// Formes d'intervention d'un profil, en toutes lettres.
List<String> offerLabels(Map<String, dynamic> profile) => [
      if (profile['offers_paid'] == true) 'Prestations payantes',
      if (profile['offers_volunteer'] == true) 'Bénévolat',
      if (profile['offers_mentoring'] == true) 'Mentorat',
    ];

/// Ajout ou modification d'un profil professionnel.
class ProfileForm extends StatefulWidget {
  const ProfileForm({super.key, required this.session, this.profile});

  final Session session;
  final Map<String, dynamic>? profile;

  @override
  State<ProfileForm> createState() => _ProfileFormState();
}

class _ProfileFormState extends State<ProfileForm> {
  late final _title = TextEditingController(text: widget.profile?['title'] as String?);
  late final _description = TextEditingController(text: widget.profile?['description'] as String?);
  late final _rate = TextEditingController(text: widget.profile?['rate'] as String?);
  late bool _paid = widget.profile?['offers_paid'] == true;
  late bool _volunteer = widget.profile?['offers_volunteer'] == true || widget.profile == null;
  late bool _mentoring = widget.profile?['offers_mentoring'] == true;
  late bool _primary = widget.profile?['is_primary'] == true;
  late bool _active = widget.profile?['active'] != false;
  int? _professionId;
  List<Map<String, dynamic>> _categories = const [];
  bool _busy = false;

  bool get _editing => widget.profile != null;

  @override
  void initState() {
    super.initState();
    if (!_editing) {
      widget.session.api.get('professions').then((response) {
        if (mounted) setState(() => _categories = (response['data'] as List).cast<Map<String, dynamic>>());
      }, onError: (Object error) {
        if (mounted) showError(context, error);
      });
    }
  }

  @override
  void dispose() {
    _title.dispose();
    _description.dispose();
    _rate.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    setState(() => _busy = true);
    final body = {
      'title': _title.text.trim().isEmpty ? null : _title.text.trim(),
      'description': _description.text.trim().isEmpty ? null : _description.text.trim(),
      'rate': _rate.text.trim().isEmpty ? null : _rate.text.trim(),
      'offers_paid': _paid,
      'offers_volunteer': _volunteer,
      'offers_mentoring': _mentoring,
    };
    try {
      if (_editing) {
        await widget.session.api.patch('member-profiles/${widget.profile!['id']}', {...body, 'is_primary': _primary, 'active': _active});
      } else {
        await widget.session.api.post('me/profiles', {...body, 'profession_id': _professionId});
      }
      if (mounted) Navigator.of(context).pop(true);
    } catch (error) {
      if (mounted) showError(context, error);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _delete() async {
    setState(() => _busy = true);
    try {
      await widget.session.api.delete('member-profiles/${widget.profile!['id']}');
      if (mounted) Navigator.of(context).pop(true);
    } catch (error) {
      if (mounted) showError(context, error);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final profession = widget.profile?['profession'] as Map?;

    return Scaffold(
      appBar: AppBar(title: Text(_editing ? '${profession?['name'] ?? 'Profil'}' : 'Ajouter un métier')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        if (!_editing)
          DropdownButtonFormField<int>(
            initialValue: _professionId,
            isExpanded: true,
            decoration: const InputDecoration(labelText: 'Métier'),
            items: [
              for (final category in _categories)
                for (final item in (category['professions'] as List).cast<Map<String, dynamic>>())
                  DropdownMenuItem(value: item['id'] as int, child: Text('${item['name']} · ${category['name']}', overflow: TextOverflow.ellipsis)),
            ],
            onChanged: (value) => setState(() => _professionId = value),
          ),
        const SizedBox(height: 12),
        TextField(controller: _title, decoration: const InputDecoration(labelText: 'Intitulé affiché (facultatif)')),
        const SizedBox(height: 12),
        TextField(controller: _description, maxLines: 3, decoration: const InputDecoration(labelText: 'Ce que vous apportez')),
        const SizedBox(height: 12),
        const Text('Comment intervenez-vous ?', style: TextStyle(fontWeight: FontWeight.w600)),
        CheckboxListTile(contentPadding: EdgeInsets.zero, value: _paid, title: const Text('Prestations payantes'), onChanged: (value) => setState(() => _paid = value ?? false)),
        CheckboxListTile(contentPadding: EdgeInsets.zero, value: _volunteer, title: const Text('Bénévolat'), onChanged: (value) => setState(() => _volunteer = value ?? false)),
        CheckboxListTile(contentPadding: EdgeInsets.zero, value: _mentoring, title: const Text('Mentorat'), onChanged: (value) => setState(() => _mentoring = value ?? false)),
        const Text('Un profil payant ou de mentorat est visible des autres après la vérification de votre identité.', style: TextStyle(fontSize: 12, color: muted)),
        const SizedBox(height: 12),
        TextField(controller: _rate, decoration: const InputDecoration(labelText: 'Tarif indicatif (si payant)', hintText: 'Ex. : 25 000 FCFA la séance')),
        if (_editing) ...[
          SwitchListTile(contentPadding: EdgeInsets.zero, value: _primary, title: const Text('Profil principal'), onChanged: (value) => setState(() => _primary = value)),
          SwitchListTile(contentPadding: EdgeInsets.zero, value: _active, title: const Text('Profil actif'), onChanged: (value) => setState(() => _active = value)),
        ],
        const SizedBox(height: 16),
        FilledButton(
          style: FilledButton.styleFrom(backgroundColor: brand, minimumSize: const Size.fromHeight(48)),
          onPressed: _busy || (!_editing && _professionId == null) ? null : _save,
          child: Text(_editing ? 'Enregistrer' : 'Ajouter ce métier'),
        ),
        if (_editing)
          TextButton(
            style: TextButton.styleFrom(foregroundColor: Colors.red.shade700, minimumSize: const Size.fromHeight(48)),
            onPressed: _busy ? null : _delete,
            child: const Text('Supprimer ce profil'),
          ),
      ]),
    );
  }
}
