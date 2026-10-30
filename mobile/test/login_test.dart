import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:idee/main.dart';
import 'package:idee/src/api.dart';
import 'package:idee/src/session.dart';

/// Réponse JSON en UTF-8, comme celles de l'API.
http.Response reply(Object body, [int status = 200]) =>
    http.Response(jsonEncode(body), status, headers: {'content-type': 'application/json; charset=utf-8'});

void main() {
  testWidgets('un membre se connecte avec le code reçu', (tester) async {
    FlutterSecureStorage.setMockInitialValues({});
    final requests = <http.Request>[];
    final api = ApiClient(
      baseUrl: 'https://idee.test',
      client: MockClient((request) async {
        requests.add(request);
        return switch (request.url.path) {
          '/api/auth/code' => reply({'message': 'Un code de connexion vous a été envoyé.'}, 202),
          '/api/auth/verify' => reply({'token': 'jeton', 'user': {'id': '01u', 'name': 'Awa Diop'}}),
          '/api/projects' => reply({'data': []}),
          _ => http.Response('{}', 404),
        };
      }),
    );
    final session = Session(api: api);
    await session.restore();

    await tester.pumpWidget(IdeeApp(session: session));
    expect(find.text('Recevoir mon code'), findsOneWidget);

    await tester.enterText(find.byType(TextField).first, '77 123 45 67');
    await tester.tap(find.text('Recevoir mon code'));
    await tester.pumpAndSettle();

    expect(find.text('Un code de connexion vous a été envoyé.'), findsOneWidget);
    expect(jsonDecode(requests.first.body), {'phone': '+221771234567'});

    await tester.enterText(find.widgetWithText(TextField, 'Code reçu'), '123456');
    await tester.tap(find.text('Me connecter'));
    await tester.pumpAndSettle();

    expect(session.isSignedIn, isTrue);
    expect(session.user?.name, 'Awa Diop');
    expect(find.text('Projets'), findsWidgets);
    expect(find.text('Aucun projet ne correspond à cette recherche.'), findsOneWidget);
  });

  testWidgets('un identifiant invalide est refusé sans appel au serveur', (tester) async {
    FlutterSecureStorage.setMockInitialValues({});
    var calls = 0;
    final session = Session(api: ApiClient(baseUrl: 'https://idee.test', client: MockClient((_) async {
      calls++;
      return http.Response('{}', 200);
    })));
    await session.restore();

    await tester.pumpWidget(IdeeApp(session: session));
    await tester.enterText(find.byType(TextField).first, '12');
    await tester.tap(find.text('Recevoir mon code'));
    await tester.pump();

    expect(find.textContaining('numéro de téléphone valide'), findsOneWidget);
    expect(calls, 0);
    await tester.pumpWidget(const MaterialApp(home: SizedBox()));
  });
}
