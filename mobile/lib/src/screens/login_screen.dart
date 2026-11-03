import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../countries.dart';
import '../session.dart';
import '../widgets.dart';

/// Ouvre un lien de paiement dans Wave, Orange Money ou le navigateur.
Future<bool> openExternally(Uri uri) => launchUrl(uri, mode: LaunchMode.externalApplication);

/// Connexion par code à usage unique (CP-01, CP-07) : par WhatsApp ou par
/// e-mail, gratuitement, ou par SMS une fois le SMS payé.
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.session, this.openUrl = openExternally});

  final Session session;

  /// Remplaçable dans les tests.
  final Future<bool> Function(Uri uri) openUrl;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _identifier = TextEditingController();
  final _code = TextEditingController();
  final _name = TextEditingController();
  Country _country = countries.first;
  String _channel = 'whatsapp';
  bool _codeSent = false;
  bool _busy = false;
  String? _info;

  // SMS payant (CP-07), proposé seulement si le serveur l'a activé.
  bool _smsAvailable = false;
  int _smsPrice = 500;
  List<Map<String, dynamic>> _methods = const [];
  Map<String, dynamic>? _purchase;

  bool get _isEmail => _identifier.text.contains('@');

  @override
  void initState() {
    super.initState();
    _loadOptions();
  }

  @override
  void dispose() {
    _identifier.dispose();
    _code.dispose();
    _name.dispose();
    super.dispose();
  }

  Future<void> _loadOptions() async {
    try {
      final options = await widget.session.loginOptions();
      final sms = (options['channels'] as List? ?? const []).cast<Map<String, dynamic>>().where((channel) => channel['value'] == 'sms').firstOrNull;
      if (!mounted || sms == null) return;
      setState(() {
        _smsAvailable = sms['available'] == true;
        _smsPrice = (sms['price_fcfa'] as num?)?.toInt() ?? 500;
        _methods = (options['payment_methods'] as List? ?? const []).cast<Map<String, dynamic>>();
      });
    } catch (_) {
      // Sans réponse exploitable du serveur, WhatsApp et l'e-mail restent proposés.
    }
  }

  Future<void> _run(Future<void> Function() action) async {
    setState(() => _busy = true);
    try {
      await action();
    } catch (error) {
      if (mounted) showError(context, error);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _sendCode() async {
    if (!_isEmail && _channel == 'sms') {
      return _payForSms();
    }
    final message = await widget.session.requestCode(_identifier.text, dialCode: _country.dialCode, channel: _channel);
    setState(() {
      _codeSent = true;
      _info = message;
    });
  }

  /// Choisit le moyen de paiement, ouvre le paiement, puis attend la confirmation.
  Future<void> _payForSms() async {
    final method = await showModalBottomSheet<String>(
      context: context,
      builder: (context) => SafeArea(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          ListTile(title: Text('Payer $_smsPrice FCFA avec', style: const TextStyle(fontWeight: FontWeight.w600))),
          for (final method in _methods)
            ListTile(title: Text(method['label'] as String), onTap: () => Navigator.of(context).pop(method['value'] as String)),
        ]),
      ),
    );
    if (method == null) return;

    final purchase = await widget.session.startSmsPurchase(_identifier.text, method, dialCode: _country.dialCode);
    setState(() => _purchase = purchase);
    final url = purchase['payment_url'] as String?;
    if (url != null) {
      await widget.openUrl(Uri.parse(url));
    }
  }

  Future<void> _checkSmsPayment() async {
    final purchase = await widget.session.smsPurchase(_purchase!['id'] as String);
    setState(() => _purchase = purchase);
    if ((purchase['status'] as Map?)?['value'] != 'payee') {
      setState(() => _info = 'Paiement pas encore confirmé. Terminez-le, puis réessayez dans un instant.');
      return;
    }
    final message = await widget.session.requestCode(_identifier.text, dialCode: _country.dialCode, channel: 'sms', smsPurchaseId: purchase['id'] as String);
    setState(() {
      _codeSent = true;
      _info = message;
    });
  }

  @override
  Widget build(BuildContext context) {
    final waitingForPayment = _purchase != null && !_codeSent;

    return Scaffold(
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            const SizedBox(height: 32),
            // Logo en dégradé, du violet foncé au violet clair.
            ShaderMask(
              shaderCallback: (bounds) => const LinearGradient(colors: [brandDark, brandLight]).createShader(bounds),
              child: const Text('IDEE', style: TextStyle(fontSize: 36, fontWeight: FontWeight.bold, color: Colors.white)),
            ),
            const SizedBox(height: 8),
            const Text('Le réseau où les projets trouvent les personnes qui les font avancer.', style: TextStyle(fontSize: 16, color: Colors.black54)),
            const SizedBox(height: 32),
            DropdownButtonFormField<Country>(
              initialValue: _country,
              isExpanded: true,
              decoration: const InputDecoration(labelText: 'Pays', border: OutlineInputBorder()),
              items: [for (final country in countries) DropdownMenuItem(value: country, child: Text(country.label))],
              onChanged: _codeSent || waitingForPayment ? null : (country) => setState(() => _country = country ?? countries.first),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _identifier,
              enabled: !_codeSent && !waitingForPayment,
              keyboardType: TextInputType.emailAddress,
              onChanged: (_) => setState(() {}),
              decoration: const InputDecoration(labelText: 'Numéro de téléphone ou e-mail', hintText: '77 123 45 67', border: OutlineInputBorder()),
            ),
            if (!_codeSent && !waitingForPayment && !_isEmail) ...[
              const SizedBox(height: 12),
              const Text('Recevoir le code par', style: TextStyle(fontWeight: FontWeight.w600)),
              RadioGroup<String>(
                groupValue: _channel,
                onChanged: (value) => setState(() => _channel = value ?? 'whatsapp'),
                child: Column(children: [
                  const RadioListTile<String>(contentPadding: EdgeInsets.zero, value: 'whatsapp', title: Text('WhatsApp · gratuit')),
                  if (_smsAvailable)
                    RadioListTile<String>(contentPadding: EdgeInsets.zero, value: 'sms', title: Text('SMS · $_smsPrice FCFA, payés avant l\'envoi')),
                ]),
              ),
            ],
            if (waitingForPayment) ...[
              const SizedBox(height: 16),
              if (_purchase!['instructions'] != null) Text(_purchase!['instructions'] as String),
              if (_purchase!['payment_url'] != null)
                TextButton(onPressed: () => widget.openUrl(Uri.parse(_purchase!['payment_url'] as String)), child: const Text('Ouvrir à nouveau le paiement')),
              if (_info != null) Text(_info!, style: const TextStyle(color: brand)),
            ],
            if (_codeSent) ...[
              const SizedBox(height: 16),
              if (_info != null) Text(_info!, style: const TextStyle(color: brand)),
              const SizedBox(height: 8),
              TextField(
                controller: _code,
                keyboardType: TextInputType.number,
                maxLength: 6,
                decoration: const InputDecoration(labelText: 'Code reçu', border: OutlineInputBorder()),
              ),
              TextField(
                controller: _name,
                decoration: const InputDecoration(labelText: 'Votre nom (première connexion seulement)', border: OutlineInputBorder()),
              ),
            ],
            const SizedBox(height: 24),
            FilledButton(
              style: FilledButton.styleFrom(backgroundColor: brand, minimumSize: const Size.fromHeight(52)),
              onPressed: _busy
                  ? null
                  : () => _run(() async {
                        if (_codeSent) {
                          await widget.session.verify(_identifier.text, _code.text, name: _name.text, dialCode: _country.dialCode);
                        } else if (waitingForPayment) {
                          await _checkSmsPayment();
                        } else {
                          await _sendCode();
                        }
                      }),
              child: Text(_codeSent
                  ? 'Me connecter'
                  : waitingForPayment
                      ? 'J\'ai payé : recevoir mon code'
                      : !_isEmail && _channel == 'sms'
                          ? 'Payer $_smsPrice FCFA et recevoir le code'
                          : 'Recevoir mon code'),
            ),
            if (_codeSent || waitingForPayment)
              TextButton(
                onPressed: _busy
                    ? null
                    : () => setState(() {
                          _codeSent = false;
                          _purchase = null;
                          _info = null;
                        }),
                child: const Text('Changer de numéro'),
              ),
          ],
        ),
      ),
    );
  }
}
