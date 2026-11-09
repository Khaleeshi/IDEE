import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';

import 'src/screens/home_shell.dart';
import 'src/screens/login_screen.dart';
import 'src/screens/terms_screen.dart';
import 'src/session.dart';
import 'src/theme.dart';

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
      theme: ideeTheme(),
      home: ListenableBuilder(
        listenable: session,
        builder: (context, _) {
          if (!session.ready) {
            return const Scaffold(body: Center(child: CircularProgressIndicator()));
          }
          if (!session.isSignedIn) {
            return LoginScreen(session: session);
          }
          return session.termsAccepted ? HomeShell(session: session) : TermsScreen(session: session);
        },
      ),
    );
  }
}
