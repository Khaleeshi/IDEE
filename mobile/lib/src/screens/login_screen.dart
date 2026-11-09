import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
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

  /// Où le code est parti : l'e-mail, ou le numéro avec son indicatif.
  String get _destination => _isEmail ? _identifier.text.trim() : '${_country.dialCode} ${_identifier.text.trim()}';

  Widget _channelTile(String value, String title, IconData icon) {
    final selected = _channel == value;
    return Padding(
      padding: const EdgeInsets.only(top: 8),
      child: RadioListTile<String>(
        value: value,
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w500)),
        secondary: Icon(icon, color: selected ? brand : muted),
        selected: selected,
        controlAffinity: ListTileControlAffinity.leading,
        contentPadding: const EdgeInsets.symmetric(horizontal: 8),
        tileColor: Colors.white,
        selectedTileColor: brandTint.withValues(alpha: 0.6),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14), side: BorderSide(color: selected ? brandLight : line)),
      ),
    );
  }

  /// Bandeau d'information, en violet clair.
  Widget _notice(String text, IconData icon) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: brandTint.withValues(alpha: 0.6), borderRadius: BorderRadius.circular(14)),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Icon(icon, size: 20, color: brand),
        const SizedBox(width: 10),
        Expanded(child: Text(text, style: const TextStyle(color: brandDark, height: 1.35))),
      ]),
    );
  }

  @override
  Widget build(BuildContext context) {
    final waitingForPayment = _purchase != null && !_codeSent;
    final editing = !_codeSent && !waitingForPayment;

    return Scaffold(
      body: AnnotatedRegion<SystemUiOverlayStyle>(
        value: SystemUiOverlayStyle.light,
        child: ListView(
          padding: EdgeInsets.zero,
          children: [
            // En-tête violet : le nom et la promesse d'IDEE.
            Container(
              decoration: const BoxDecoration(gradient: brandGradient),
              child: SafeArea(
                bottom: false,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(28, 28, 28, 60),
                  child: Readable(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      const IdeeLogo(size: 34, onDark: true),
                      const SizedBox(height: 8),
                      Text(
                        'Le réseau où les projets trouvent les personnes qui les font avancer.',
                        style: TextStyle(fontSize: 16, height: 1.35, color: Colors.white.withValues(alpha: 0.85)),
                      ),
                    ]),
                  ),
                ),
              ),
            ),
            // Formulaire dans une carte posée à cheval sur l'en-tête.
            Transform.translate(
              offset: const Offset(0, -36),
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Readable(
                  child: Card(
                    margin: EdgeInsets.zero,
                    child: Padding(
                      padding: const EdgeInsets.all(20),
                      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                        Text(_codeSent ? 'Entrez votre code' : 'Se connecter', style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w700, color: ink)),
                        const SizedBox(height: 4),
                        Text(
                          editing ? 'Sans mot de passe : un code vous est envoyé.' : 'Pour $_destination',
                          style: const TextStyle(color: muted),
                        ),
                        const SizedBox(height: 20),
                        if (editing) ...[
                          DropdownButtonFormField<Country>(
                            initialValue: _country,
                            isExpanded: true,
                            decoration: const InputDecoration(labelText: 'Pays'),
                            items: [for (final country in countries) DropdownMenuItem(value: country, child: Text(country.label))],
                            onChanged: (country) => setState(() => _country = country ?? countries.first),
                          ),
                          const SizedBox(height: 12),
                          TextField(
                            controller: _identifier,
                            keyboardType: TextInputType.emailAddress,
                            onChanged: (_) => setState(() {}),
                            decoration: const InputDecoration(labelText: 'Numéro de téléphone ou e-mail', hintText: '77 123 45 67'),
                          ),
                          if (!_isEmail) ...[
                            const SizedBox(height: 16),
                            const Text('Recevoir le code par', style: TextStyle(fontWeight: FontWeight.w600, color: ink)),
                            RadioGroup<String>(
                              groupValue: _channel,
                              onChanged: (value) => setState(() => _channel = value ?? 'whatsapp'),
                              child: Column(children: [
                                _channelTile('whatsapp', 'WhatsApp · gratuit', Icons.chat_outlined),
                                if (_smsAvailable) _channelTile('sms', 'SMS · $_smsPrice FCFA, payés avant l\'envoi', Icons.sms_outlined),
                              ]),
                            ),
                          ],
                        ],
                        if (waitingForPayment) ...[
                          if (_purchase!['instructions'] != null) Text(_purchase!['instructions'] as String),
                          if (_purchase!['payment_url'] != null)
                            TextButton(onPressed: () => widget.openUrl(Uri.parse(_purchase!['payment_url'] as String)), child: const Text('Ouvrir à nouveau le paiement')),
                          if (_info != null) _notice(_info!, Icons.info_outline),
                        ],
                        if (_codeSent) ...[
                          if (_info != null) ...[_notice(_info!, Icons.mark_chat_read_outlined), const SizedBox(height: 16)],
                          TextField(
                            controller: _code,
                            keyboardType: TextInputType.number,
                            maxLength: 6,
                            style: const TextStyle(fontSize: 22, letterSpacing: 6, fontWeight: FontWeight.w600),
                            decoration: const InputDecoration(labelText: 'Code reçu'),
                          ),
                          const SizedBox(height: 4),
                          TextField(
                            controller: _name,
                            textCapitalization: TextCapitalization.words,
                            decoration: const InputDecoration(labelText: 'Votre nom (première connexion seulement)'),
                          ),
                        ],
                        const SizedBox(height: 20),
                        FilledButton(
                          style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(52)),
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
                        if (!editing)
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
                      ]),
                    ),
                  ),
                ),
              ),
            ),
            // Ce que l'on trouve sur IDEE, sous le formulaire.
            Padding(
              padding: const EdgeInsets.fromLTRB(28, 0, 28, 32),
              child: Readable(
                child: Column(children: const [
                  _Benefit(Icons.handshake_outlined, 'Trouvez des associés, des mentors, des compétences ou un financement pour votre projet.'),
                  _Benefit(Icons.route_outlined, 'Suivez des parcours guidés pour créer et faire grandir votre activité.'),
                  _Benefit(Icons.lock_outline, 'Votre téléphone et votre e-mail ne sont jamais montrés aux autres membres.'),
                ]),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Benefit extends StatelessWidget {
  const _Benefit(this.icon, this.text);

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Container(
          width: 36,
          height: 36,
          decoration: const BoxDecoration(color: brandTint, shape: BoxShape.circle),
          child: Icon(icon, size: 18, color: brand),
        ),
        const SizedBox(width: 12),
        Expanded(child: Padding(padding: const EdgeInsets.only(top: 8), child: Text(text, style: const TextStyle(color: inkSoft, height: 1.35)))),
      ]),
    );
  }
}
