import 'package:flutter/material.dart';

import '../models.dart';
import '../session.dart';
import '../widgets.dart';

/// Centre de notifications (NO-01).
class NotificationsScreen extends StatelessWidget {
  const NotificationsScreen({super.key, required this.session});

  final Session session;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Notifications')),
      body: Loader<List<AppNotification>>(
        load: () async {
          final response = await session.api.get('me/notifications');
          await session.api.post('me/notifications/read');
          return (response['data'] as List).map((item) => AppNotification.fromJson(item as Map<String, dynamic>)).toList();
        },
        builder: (context, notifications, _) => notifications.isEmpty
            ? const EmptyState('Aucune notification pour le moment.')
            : ListView.separated(
                itemCount: notifications.length,
                separatorBuilder: (_, _) => const Divider(height: 1),
                itemBuilder: (context, index) {
                  final notification = notifications[index];
                  return ListTile(
                    leading: Icon(Icons.circle, size: 10, color: notification.read ? Colors.transparent : brand),
                    title: Text(notification.message),
                    subtitle: Text(formatDate(notification.createdAt)),
                  );
                },
              ),
      ),
    );
  }
}
