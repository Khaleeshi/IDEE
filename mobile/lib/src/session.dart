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

  /// Envoie un code de connexion : par WhatsApp (gratuit), par e-mail, ou par
  /// SMS une fois le SMS payé (CP-07, `smsPurchaseId`).
  Future<String> requestCode(String identifier, {String dialCode = '+221', String? channel, String? smsPurchaseId}) async {
    final payload = _identifierPayload(identifier, dialCode: dialCode);
    final response = await api.post('auth/code', {
      ...payload,
      if (payload.containsKey('phone')) 'channel': channel ?? 'whatsapp',
      if (smsPurchaseId != null) 'sms_purchase_id': smsPurchaseId,
    });
    return response['message'] as String? ?? 'Code envoyé.';
  }

  /// Ouvre le paiement d'un SMS de connexion (CP-07) : renvoie l'achat, avec le
  /// lien de paiement ou le code USSD à composer.
  Future<Map<String, dynamic>> startSmsPurchase(String identifier, String method, {String dialCode = '+221'}) async {
    final payload = _identifierPayload(identifier, dialCode: dialCode);
    if (!payload.containsKey('phone')) {
      throw ApiException('Le SMS ne sert qu\'aux numéros de téléphone. Avec un e-mail, le code est gratuit.');
    }
    final response = await api.post('auth/sms-purchases', {...payload, 'method': method});
    return response['data'] as Map<String, dynamic>;
  }

  /// Relit un achat de SMS : `status.value` vaut `payee` une fois le paiement confirmé.
  Future<Map<String, dynamic>> smsPurchase(String id) async => (await api.get('auth/sms-purchases/$id'))['data'] as Map<String, dynamic>;

  /// Canaux disponibles (le SMS payant n'est proposé que s'il est activé sur le serveur).
  Future<Map<String, dynamic>> loginOptions() async => (await api.get('auth/options'))['data'] as Map<String, dynamic>;

  /// Vérifie le code ; un nouveau membre donne aussi son nom.
  Future<void> verify(String identifier, String code, {String? name, String dialCode = '+221'}) async {
    final response = await api.post('auth/verify', {
      ..._identifierPayload(identifier, dialCode: dialCode),
      'code': code.trim(),
      if (name != null && name.trim().isNotEmpty) 'name': name.trim(),
      // CP-06 : nom de l'appareil, affiché dans « Mes appareils ».
      'device_name': 'Application IDEE ($_platformName)',
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

  static String get _platformName => switch (defaultTargetPlatform) {
        TargetPlatform.android => 'Android',
        TargetPlatform.iOS => 'iPhone',
        _ => 'autre appareil',
      };

  Map<String, dynamic> _identifierPayload(String identifier, {String dialCode = '+221'}) {
    final value = identifier.trim();
    if (value.contains('@')) {
      return {'email': value.toLowerCase()};
    }
    final phone = normalizePhone(value, dialCode: dialCode);
    if (phone == null) {
      throw ApiException('Indiquez un numéro de téléphone valide, par exemple 77 123 45 67, ou une adresse e-mail.');
    }
    return {'phone': phone};
  }
}
