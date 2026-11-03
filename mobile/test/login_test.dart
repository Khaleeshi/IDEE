import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:idee/main.dart';
import 'package:idee/src/api.dart';
import 'package:idee/src/screens/login_screen.dart';
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

    await tester.enterText(find.widgetWithText(TextField, 'Numéro de téléphone ou e-mail'), '77 123 45 67');
    await tester.tap(find.text('Recevoir mon code'));
    await tester.pumpAndSettle();

    expect(find.text('Un code de connexion vous a été envoyé.'), findsOneWidget);
    final codeRequest = requests.firstWhere((request) => request.url.path == '/api/auth/code');
    expect(jsonDecode(codeRequest.body), {'phone': '+221771234567', 'channel': 'whatsapp'});

    await tester.enterText(find.widgetWithText(TextField, 'Code reçu'), '123456');
    await tester.tap(find.text('Me connecter'));
    await tester.pumpAndSettle();

    expect(session.isSignedIn, isTrue);
    expect(session.user?.name, 'Awa Diop');
    expect(find.text('Projets'), findsWidgets);
    expect(find.text('Aucun projet ne correspond à cette recherche.'), findsOneWidget);
  });

  testWidgets('un nouveau membre accepte la charte avant d\'entrer', (tester) async {
    FlutterSecureStorage.setMockInitialValues({'idee_token': 'jeton'});
    final requests = <http.Request>[];
    final session = Session(api: ApiClient(
      baseUrl: 'https://idee.test',
      client: MockClient((request) async {
        requests.add(request);
        return switch (request.url.path) {
          '/api/me' => reply({'data': {'id': '01u', 'name': 'Awa Diop', 'terms_accepted': false}}),
          '/api/me/terms' => reply({'data': {'id': '01u', 'name': 'Awa Diop', 'terms_accepted': true}}),
          '/api/projects' => reply({'data': []}),
          _ => http.Response('{}', 404),
        };
      }),
    ));
    await session.restore();

    await tester.pumpWidget(IdeeApp(session: session));
    expect(find.text('Bienvenue sur IDEE'), findsOneWidget);
    expect(tester.widget<FilledButton>(find.widgetWithText(FilledButton, 'Continuer')).onPressed, isNull);

    await tester.tap(find.byType(Checkbox));
    await tester.pump();
    await tester.tap(find.text('Continuer'));
    await tester.pumpAndSettle();

    expect(session.termsAccepted, isTrue);
    expect(jsonDecode(requests.firstWhere((request) => request.url.path == '/api/me/terms').body), {'accept': true});
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

  testWidgets('le SMS se paie avant l\'envoi du code (CP-07)', (tester) async {
    FlutterSecureStorage.setMockInitialValues({});
    final requests = <http.Request>[];
    final opened = <Uri>[];
    var status = 'en_attente';
    final api = ApiClient(
      baseUrl: 'https://idee.test',
      client: MockClient((request) async {
        requests.add(request);
        return switch (request.url.path) {
          '/api/auth/options' => reply({'data': {
                'channels': [
                  {'value': 'whatsapp', 'price_fcfa': 0, 'available': true},
                  {'value': 'sms', 'price_fcfa': 500, 'available': true},
                ],
                'payment_methods': [{'value': 'wave', 'label': 'Wave'}, {'value': 'orange_money', 'label': 'Orange Money'}],
              }}),
          '/api/auth/sms-purchases' => reply({'data': {'id': '01s', 'status': {'value': 'en_attente'}, 'payment_url': 'https://pay.bictorys.test/x'}}, 201),
          '/api/auth/sms-purchases/01s' => reply({'data': {'id': '01s', 'status': {'value': status}}}),
          '/api/auth/code' => reply({'message': 'Un code de connexion vous a été envoyé.'}, 202),
          _ => http.Response('{}', 404),
        };
      }),
    );
    final session = Session(api: api);
    await session.restore();

    await tester.pumpWidget(MaterialApp(home: LoginScreen(session: session, openUrl: (uri) async {
      opened.add(uri);
      return true;
    })));
    await tester.pumpAndSettle();

    await tester.enterText(find.widgetWithText(TextField, 'Numéro de téléphone ou e-mail'), '77 123 45 67');
    await tester.tap(find.text('SMS · 500 FCFA, payés avant l\'envoi'));
    await tester.pump();
    await tester.tap(find.text('Payer 500 FCFA et recevoir le code'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Wave'));
    await tester.pumpAndSettle();

    expect(opened.single.toString(), 'https://pay.bictorys.test/x');
    expect(jsonDecode(requests.firstWhere((request) => request.url.path == '/api/auth/sms-purchases').body), {'phone': '+221771234567', 'method': 'wave'});

    // Paiement pas encore confirmé : aucun code demandé.
    await tester.tap(find.text('J\'ai payé : recevoir mon code'));
    await tester.pumpAndSettle();
    expect(requests.where((request) => request.url.path == '/api/auth/code'), isEmpty);

    status = 'payee';
    await tester.tap(find.text('J\'ai payé : recevoir mon code'));
    await tester.pumpAndSettle();
    final codeRequest = requests.firstWhere((request) => request.url.path == '/api/auth/code');
    expect(jsonDecode(codeRequest.body), {'phone': '+221771234567', 'channel': 'sms', 'sms_purchase_id': '01s'});
    expect(find.text('Code reçu'), findsOneWidget);
  });
}
