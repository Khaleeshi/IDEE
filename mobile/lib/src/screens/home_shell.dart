import 'package:flutter/material.dart';

import '../session.dart';
import '../widgets.dart';
import 'feed_screen.dart';
import 'journeys_screen.dart';
import 'notifications_screen.dart';
import 'profile_screen.dart';
import 'requests_screen.dart';

/// Navigation principale, comme sur le site : accueil, parcours, demandes, notifications, profil.
class HomeShell extends StatefulWidget {
  const HomeShell({super.key, required this.session});

  final Session session;

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final pages = [
      FeedScreen(session: widget.session),
      JourneysScreen(session: widget.session),
      RequestsScreen(session: widget.session),
      NotificationsScreen(session: widget.session),
      ProfileScreen(session: widget.session),
    ];

    return Scaffold(
      body: pages[_index],
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        indicatorColor: brand.withValues(alpha: 0.12),
        onDestinationSelected: (index) => setState(() => _index = index),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.home_outlined), label: 'Accueil'),
          NavigationDestination(icon: Icon(Icons.route_outlined), label: 'Parcours'),
          NavigationDestination(icon: Icon(Icons.inbox_outlined), label: 'Demandes'),
          NavigationDestination(icon: Icon(Icons.notifications_outlined), label: 'Alertes'),
          NavigationDestination(icon: Icon(Icons.person_outline), label: 'Profil'),
        ],
      ),
    );
  }
}
