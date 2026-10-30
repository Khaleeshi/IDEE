import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:idee/src/api.dart';
import 'package:idee/src/models.dart';

/// Réponse JSON en UTF-8, comme celles de l'API.
http.Response reply(Object body, [int status = 200]) =>
    http.Response(jsonEncode(body), status, headers: {'content-type': 'application/json; charset=utf-8'});

void main() {
  test('les numéros sénégalais sont mis au format international', () {
    expect(normalizePhone('77 123 45 67'), '+221771234567');
    expect(normalizePhone('00221 77 123 45 67'), '+221771234567');
    expect(normalizePhone('12'), isNull);
  });

  test('le client envoie le jeton et lit les données', () async {
    late http.Request sent;
    final api = ApiClient(
      baseUrl: 'https://idee.test',
      client: MockClient((request) async {
        sent = request;
        return reply({'data': [{'id': '01p', 'title': 'Savons au karité', 'summary': 'Fabriqués à Thiès', 'stage': {'value': 'idee', 'label': 'Idée'}}]});
      }),
    )..token = 'jeton';

    final response = await api.get('projects', query: {'q': 'karité'});
    final project = Project.fromJson((response['data'] as List).first as Map<String, dynamic>);

    expect(sent.url.toString(), 'https://idee.test/api/projects?q=karit%C3%A9');
    expect(sent.headers['Authorization'], 'Bearer jeton');
    expect(project.title, 'Savons au karité');
    expect(project.stage.label, 'Idée');
  });

  test('les erreurs de validation remontent en français', () async {
    final api = ApiClient(
      baseUrl: 'https://idee.test',
      client: MockClient((_) async => reply({'message': 'Invalide', 'errors': {'message': ['Présentez votre demande en au moins 100 caractères.']}}, 422)),
    );

    expect(
      () => api.post('contact-requests', {'message': 'Bonjour'}),
      throwsA(isA<ApiException>().having((error) => error.message, 'message', 'Présentez votre demande en au moins 100 caractères.')),
    );
  });

  test('une recherche de financement porte la mention légale', () {
    final need = Need.fromJson({'id': '01n', 'type': {'value': 'financement', 'label': 'Financement'}, 'title': 'Séchoir', 'notice': 'Ceci n\'est pas une offre au public.'});

    expect(need.notice, 'Ceci n\'est pas une offre au public.');
  });

  test('un message suspect porte l\'alerte anti-arnaque', () {
    final message = Message.fromJson({'id': '01m', 'sender_id': '01u', 'body': 'Réglez les frais de dossier', 'scam_alert': true, 'created_at': '2026-10-30T10:00:00Z'});

    expect(message.scamAlert, isTrue);
    expect(Message.fromJson({'id': '01n', 'sender_id': '01u', 'body': 'Bonjour'}).scamAlert, isFalse);
  });

  test('la suppression du compte envoie la confirmation', () async {
    late http.Request sent;
    final api = ApiClient(
      baseUrl: 'https://idee.test',
      client: MockClient((request) async {
        sent = request;
        return http.Response('', 204);
      }),
    )..token = 'jeton';

    await api.delete('me', {'confirmation': 'SUPPRIMER'});

    expect(sent.method, 'DELETE');
    expect(sent.url.toString(), 'https://idee.test/api/me');
    expect(jsonDecode(sent.body), {'confirmation': 'SUPPRIMER'});
  });

  test('une session expirée est signalée', () async {
    final api = ApiClient(baseUrl: 'https://idee.test', client: MockClient((_) async => http.Response('{"message":"Unauthenticated."}', 401)));

    expect(() => api.get('me'), throwsA(isA<ApiException>().having((error) => error.statusCode, 'statusCode', 401)));
  });
}
