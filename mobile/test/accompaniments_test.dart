import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:idee/src/api.dart';
import 'package:idee/src/screens/accompaniments_screen.dart';
import 'package:idee/src/session.dart';

http.Response reply(Object body, [int status = 200]) =>
    http.Response(jsonEncode(body), status, headers: {'content-type': 'application/json; charset=utf-8'});

void main() {
  testWidgets('un coach accepte une demande d\'accompagnement (AC-01)', (tester) async {
    FlutterSecureStorage.setMockInitialValues({});
    var status = {'value': 'demandee', 'label': 'Demande envoyée'};
    final posted = <String>[];
    Map<String, dynamic> item() => {
          'id': '01a',
          'kind': {'value': 'mentorat', 'label': 'Mentorat'},
          'status': status,
          'role': 'coach',
          'coach': {'id': '01c', 'name': 'Aïssatou Diallo'},
          'requester': {'id': '01r', 'name': 'Awa Ndiaye'},
          'objectives': 'Fixer mes prix et trouver mes premiers revendeurs.',
          'sessions': <Object>[],
          'review': null,
        };
    final session = Session(api: ApiClient(
      baseUrl: 'https://idee.test',
      client: MockClient((request) async {
        if (request.method == 'POST') {
          posted.add(request.url.path);
          status = {'value': 'en_cours', 'label': 'En cours'};
          return reply({'data': item()});
        }
        return switch (request.url.path) {
          '/api/me/accompaniments' => reply({'data': [item()]}),
          '/api/accompaniments/01a' => reply({'data': item()}),
          _ => http.Response('{}', 404),
        };
      }),
    ));

    await tester.pumpWidget(MaterialApp(home: AccompanimentsScreen(session: session)));
    await tester.pumpAndSettle();
    expect(find.text('Awa Ndiaye'), findsOneWidget);

    await tester.tap(find.text('Awa Ndiaye'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('J\'accepte de l\'accompagner'));
    await tester.pumpAndSettle();

    expect(posted, ['/api/accompaniments/01a/respond']);
    expect(find.text('Enregistrer le point de suivi'), findsOneWidget);
  });
}
