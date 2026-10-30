import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';

import 'src/screens/home_shell.dart';
import 'src/screens/login_screen.dart';
import 'src/session.dart';
import 'src/widgets.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  final session = Session()..restore();
  runApp(IdeeApp(session: session));
}

class IdeeApp extends StatelessWidget {
  const IdeeApp({super.key, required this.session});

  final Session session;

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'IDEE',
      debugShowCheckedModeBanner: false,
      locale: const Locale('fr'),
      supportedLocales: const [Locale('fr')],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      theme: ThemeData(colorSchemeSeed: brand, useMaterial3: true, scaffoldBackgroundColor: const Color(0xFFFAFAF9)),
      home: ListenableBuilder(
        listenable: session,
        builder: (context, _) {
          if (!session.ready) {
            return const Scaffold(body: Center(child: CircularProgressIndicator()));
          }
          return session.isSignedIn ? HomeShell(session: session) : LoginScreen(session: session);
        },
      ),
    );
  }
}
