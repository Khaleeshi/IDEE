import 'dart:convert';

import 'package:http/http.dart' as http;

/// Adresse de l'API d'IDEE, fixée à la compilation :
/// `flutter run --dart-define=API_URL=https://votre-domaine`.
const String apiUrl = String.fromEnvironment('API_URL', defaultValue: 'http://10.0.2.2:8000');

/// Erreur renvoyée par l'API, avec un message en français prêt à afficher.
class ApiException implements Exception {
  ApiException(this.message, {this.statusCode, this.errors = const {}});

  final String message;
  final int? statusCode;
  final Map<String, List<String>> errors;

  @override
  String toString() => message;
}

/// Client de l'API REST d'IDEE (Laravel Sanctum, jeton Bearer).
class ApiClient {
  ApiClient({http.Client? client, this.baseUrl = apiUrl}) : _http = client ?? http.Client();

  final http.Client _http;
  final String baseUrl;
  String? token;

  Future<Map<String, dynamic>> get(String path, {Map<String, String>? query}) =>
      _send(() => _http.get(_uri(path, query), headers: _headers()));

  Future<Map<String, dynamic>> post(String path, [Map<String, dynamic>? body]) =>
      _send(() => _http.post(_uri(path), headers: _headers(json: true), body: jsonEncode(body ?? const {})));

  Future<Map<String, dynamic>> put(String path, [Map<String, dynamic>? body]) =>
      _send(() => _http.put(_uri(path), headers: _headers(json: true), body: jsonEncode(body ?? const {})));

  Future<Map<String, dynamic>> patch(String path, [Map<String, dynamic>? body]) =>
      _send(() => _http.patch(_uri(path), headers: _headers(json: true), body: jsonEncode(body ?? const {})));

  Future<Map<String, dynamic>> delete(String path, [Map<String, dynamic>? body]) =>
      _send(() => _http.delete(_uri(path), headers: _headers(json: true), body: jsonEncode(body ?? const {})));

  Uri _uri(String path, [Map<String, String>? query]) {
    final uri = Uri.parse('$baseUrl/api/$path');
    return query == null || query.isEmpty ? uri : uri.replace(queryParameters: query);
  }

  Map<String, String> _headers({bool json = false}) => {
        'Accept': 'application/json',
        if (json) 'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  Future<Map<String, dynamic>> _send(Future<http.Response> Function() request) async {
    final http.Response response;
    try {
      response = await request().timeout(const Duration(seconds: 20));
    } on Exception {
      throw ApiException('Connexion impossible. Vérifiez votre réseau et réessayez.');
    }

    final body = response.body.isEmpty ? <String, dynamic>{} : jsonDecode(utf8.decode(response.bodyBytes));
    final data = body is Map<String, dynamic> ? body : <String, dynamic>{'data': body};

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return data;
    }

    final errors = <String, List<String>>{};
    if (data['errors'] is Map) {
      (data['errors'] as Map).forEach((key, value) {
        errors['$key'] = (value as List).map((message) => '$message').toList();
      });
    }

    final message = switch (response.statusCode) {
      401 => 'Votre session a expiré. Reconnectez-vous.',
      403 => errors.isNotEmpty ? errors.values.first.first : 'Action non autorisée.',
      404 => 'Cette page n\'existe plus.',
      429 => 'Trop de tentatives. Patientez une minute.',
      _ => errors.isNotEmpty ? errors.values.first.first : (data['message'] as String? ?? 'Une erreur est survenue. Réessayez.'),
    };

    throw ApiException(message, statusCode: response.statusCode, errors: errors);
  }
}

/// Numéro sénégalais au format international : « 77 123 45 67 » devient « +221771234567 ».
String? normalizePhone(String input) {
  var number = input.replaceAll(RegExp(r'[\s.\-()]'), '');
  if (number.startsWith('00')) {
    number = '+${number.substring(2)}';
  }
  if (RegExp(r'^[37]\d{8}$').hasMatch(number)) {
    number = '+221$number';
  }
  return RegExp(r'^\+[1-9]\d{7,14}$').hasMatch(number) ? number : null;
}
