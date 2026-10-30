import 'package:flutter/material.dart';

import '../models.dart';
import '../session.dart';
import '../widgets.dart';

/// Demandes de contact reçues et envoyées (MR-02), puis la messagerie (MR-04).
class RequestsScreen extends StatelessWidget {
  const RequestsScreen({super.key, required this.session});

  final Session session;

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Demandes'),
          bottom: const TabBar(tabs: [Tab(text: 'Reçues'), Tab(text: 'Envoyées')]),
        ),
        body: TabBarView(children: [
          _RequestList(session: session, sent: false),
          _RequestList(session: session, sent: true),
        ]),
      ),
    );
  }
}

class _RequestList extends StatelessWidget {
  const _RequestList({required this.session, required this.sent});

  final Session session;
  final bool sent;

  @override
  Widget build(BuildContext context) {
    return Loader<List<ContactRequest>>(
      load: () async {
        final response = await session.api.get('contact-requests', query: {if (sent) 'box': 'sent'});
        return (response['data'] as List).map((item) => ContactRequest.fromJson(item as Map<String, dynamic>)).toList();
      },
      builder: (context, requests, reload) => requests.isEmpty
          ? EmptyState(sent ? 'Vous n\'avez pas encore envoyé de demande.' : 'Aucune demande reçue pour le moment.')
          : ListView.separated(
              itemCount: requests.length,
              separatorBuilder: (_, _) => const Divider(height: 1),
              itemBuilder: (context, index) {
                final request = requests[index];
                final other = sent ? request.recipient : request.sender;
                return ListTile(
                  title: Text(other?.name ?? ''),
                  subtitle: Text(request.projectTitle ?? request.message, maxLines: 1, overflow: TextOverflow.ellipsis),
                  trailing: StatusChip(request.status),
                  onTap: () async {
                    await Navigator.of(context).push(MaterialPageRoute(builder: (_) => RequestScreen(session: session, requestId: request.id)));
                    await reload();
                  },
                );
              },
            ),
    );
  }
}

class RequestScreen extends StatelessWidget {
  const RequestScreen({super.key, required this.session, required this.requestId});

  final Session session;
  final String requestId;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Demande de contact')),
      body: Loader<ContactRequest>(
        load: () async => ContactRequest.fromJson((await session.api.get('contact-requests/$requestId'))['data'] as Map<String, dynamic>),
        builder: (context, request, reload) {
          final isRecipient = request.recipient?.id == session.user?.id;
          return ListView(padding: const EdgeInsets.all(16), children: [
            Row(children: [
              Expanded(child: Text('De ${request.sender?.name ?? ''} à ${request.recipient?.name ?? ''}', style: const TextStyle(fontWeight: FontWeight.w600))),
              StatusChip(request.status),
            ]),
            if (request.projectTitle != null) Padding(padding: const EdgeInsets.only(top: 4), child: Text('Projet : ${request.projectTitle}')),
            const SizedBox(height: 12),
            Text(request.message, style: const TextStyle(fontSize: 16)),
            const SizedBox(height: 8),
            Text('Envoyée le ${formatDate(request.createdAt)}', style: const TextStyle(fontSize: 12, color: Colors.black54)),
            const SizedBox(height: 20),
            if (isRecipient && request.isPending) ...[
              const Text('Votre réponse', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              _answerButton(context, request, reload, 'interesse', 'Intéressé'),
              _answerButton(context, request, reload, 'pas_maintenant', 'Pas maintenant'),
              _answerButton(context, request, reload, 'refusee', 'Refuser'),
            ],
            if (request.allowsMessages)
              FilledButton(
                style: FilledButton.styleFrom(backgroundColor: brand, minimumSize: const Size.fromHeight(52)),
                onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ConversationScreen(session: session, request: request))),
                child: const Text('Ouvrir la messagerie'),
              ),
          ]);
        },
      ),
    );
  }

  Widget _answerButton(BuildContext context, ContactRequest request, Future<void> Function() reload, String status, String label) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: OutlinedButton(
        style: OutlinedButton.styleFrom(minimumSize: const Size.fromHeight(48)),
        onPressed: () async {
          String? reason;
          if (status == 'refusee') {
            reason = await _askReason(context);
            if (reason == null) return;
          }
          try {
            await session.api.post('contact-requests/${request.id}/respond', {'status': status, 'refusal_reason': ?reason});
            await reload();
          } catch (error) {
            if (context.mounted) showError(context, error);
          }
        },
        child: Text(label),
      ),
    );
  }

  Future<String?> _askReason(BuildContext context) {
    final controller = TextEditingController();
    return showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Motif du refus'),
        content: TextField(controller: controller, maxLines: 3, decoration: const InputDecoration(hintText: 'Un motif clair aide l\'expéditeur.')),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('Annuler')),
          TextButton(onPressed: () => Navigator.pop(context, controller.text.trim()), child: const Text('Refuser')),
        ],
      ),
    );
  }
}

class ConversationScreen extends StatefulWidget {
  const ConversationScreen({super.key, required this.session, required this.request});

  final Session session;
  final ContactRequest request;

  @override
  State<ConversationScreen> createState() => _ConversationScreenState();
}

class _ConversationScreenState extends State<ConversationScreen> {
  final _body = TextEditingController();
  Key _listKey = UniqueKey();

  @override
  Widget build(BuildContext context) {
    final me = widget.session.user?.id;
    return Scaffold(
      appBar: AppBar(title: const Text('Messages')),
      body: Column(children: [
        Expanded(
          child: Loader<List<Message>>(
            key: _listKey,
            load: () async {
              final response = await widget.session.api.get('contact-requests/${widget.request.id}/messages');
              return (response['data'] as List).map((item) => Message.fromJson(item as Map<String, dynamic>)).toList();
            },
            builder: (context, messages, _) => messages.isEmpty
                ? const EmptyState('Écrivez le premier message pour convenir d\'un échange.')
                : ListView(
                    padding: const EdgeInsets.all(12),
                    children: messages.map((message) {
                      final mine = message.senderId == me;
                      return Align(
                        alignment: mine ? Alignment.centerRight : Alignment.centerLeft,
                        child: Container(
                          margin: const EdgeInsets.only(bottom: 8),
                          padding: const EdgeInsets.all(10),
                          constraints: const BoxConstraints(maxWidth: 300),
                          decoration: BoxDecoration(color: mine ? brand : Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: Colors.black12)),
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Text(message.body ?? (message.attachmentName != null ? '📎 ${message.attachmentName}' : ''),
                                style: TextStyle(color: mine ? Colors.white : Colors.black87)),
                            if (!mine && message.scamAlert) const ScamAlert(),
                          ]),
                        ),
                      );
                    }).toList(),
                  ),
          ),
        ),
        SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(8),
            child: Row(children: [
              Expanded(child: TextField(controller: _body, minLines: 1, maxLines: 4, decoration: const InputDecoration(hintText: 'Votre message…', border: OutlineInputBorder()))),
              IconButton(
                icon: const Icon(Icons.send, color: brand),
                tooltip: 'Envoyer',
                onPressed: () async {
                  if (_body.text.trim().isEmpty) return;
                  try {
                    await widget.session.api.post('contact-requests/${widget.request.id}/messages', {'body': _body.text.trim()});
                    _body.clear();
                    setState(() => _listKey = UniqueKey());
                  } catch (error) {
                    if (context.mounted) showError(context, error);
                  }
                },
              ),
            ]),
          ),
        ),
      ]),
    );
  }
}
