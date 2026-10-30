import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'api.dart';
import 'models.dart';

/// Session du membre : jeton de l'API, gardé dans le stockage sécurisé du téléphone.
class Session extends ChangeNotifier {
  Session({ApiClient? api, FlutterSecureStorage? storage})
      : api = api ?? ApiClient(),
        _storage = storage ?? const FlutterSecureStorage();

  static const _tokenKey = 'idee_token';

  final ApiClient api;
  final FlutterSecureStorage _storage;

  Member? user;
  bool ready = false;

  /// Conditions d'utilisation et charte acceptées dans leur version en vigueur.
  bool termsAccepted = true;

  bool get isSignedIn => api.token != null;

  /// Reprend la session enregistrée, si le jeton est encore valable.
  Future<void> restore() async {
    api.token = await _storage.read(key: _tokenKey);
    if (api.token != null) {
      try {
        _signedIn((await api.get('me'))['data'] as Map<String, dynamic>);
      } on ApiException catch (error) {
        if (error.statusCode == 401) {
          await _forget();
        }
      }
    }
    ready = true;
    notifyListeners();
  }

  /// Envoie un code de connexion par SMS, WhatsApp ou e-mail.
  Future<String> requestCode(String identifier) async {
    final response = await api.post('auth/code', _identifierPayload(identifier));
    return response['message'] as String? ?? 'Code envoyé.';
  }

  /// Vérifie le code ; un nouveau membre donne aussi son nom.
  Future<void> verify(String identifier, String code, {String? name}) async {
    final response = await api.post('auth/verify', {
      ..._identifierPayload(identifier),
      'code': code.trim(),
      if (name != null && name.trim().isNotEmpty) 'name': name.trim(),
      'device_name': 'Application IDEE',
    });
    api.token = response['token'] as String;
    _signedIn(response['user'] as Map<String, dynamic>);
    await _storage.write(key: _tokenKey, value: api.token);
    notifyListeners();
  }

  Future<void> logout() async {
    try {
      await api.post('auth/logout');
    } on ApiException {
      // Le jeton est oublié même si le serveur est injoignable.
    }
    await _forget();
    notifyListeners();
  }

  /// Accepte les conditions d'utilisation et la charte communautaire.
  Future<void> acceptTerms() async {
    await api.post('me/terms', {'accept': true});
    termsAccepted = true;
    notifyListeners();
  }

  /// Supprime le compte sur le serveur, puis oublie la session sur le téléphone.
  Future<void> deleteAccount(String confirmation) async {
    await api.delete('me', {'confirmation': confirmation});
    await _forget();
    notifyListeners();
  }

  void _signedIn(Map<String, dynamic> profile) {
    user = Member.fromJson(profile);
    termsAccepted = profile['terms_accepted'] != false;
  }

  Future<void> _forget() async {
    api.token = null;
    user = null;
    await _storage.delete(key: _tokenKey);
  }

  Map<String, dynamic> _identifierPayload(String identifier) {
    final value = identifier.trim();
    if (value.contains('@')) {
      return {'email': value.toLowerCase()};
    }
    final phone = normalizePhone(value);
    if (phone == null) {
      throw ApiException('Indiquez un numéro de téléphone valide, par exemple 77 123 45 67, ou une adresse e-mail.');
    }
    return {'phone': phone};
  }
}
