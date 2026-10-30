import 'package:flutter/material.dart';

import '../session.dart';
import '../widgets.dart';

/// Connexion par code à usage unique, reçu par SMS, WhatsApp ou e-mail.
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.session});

  final Session session;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _identifier = TextEditingController();
  final _code = TextEditingController();
  final _name = TextEditingController();
  bool _codeSent = false;
  bool _busy = false;
  String? _info;

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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            const SizedBox(height: 32),
            const Text('IDEE', style: TextStyle(fontSize: 36, fontWeight: FontWeight.bold, color: brand)),
            const SizedBox(height: 8),
            const Text('Le réseau où les projets trouvent les personnes qui les font avancer.', style: TextStyle(fontSize: 16, color: Colors.black54)),
            const SizedBox(height: 32),
            TextField(
              controller: _identifier,
              enabled: !_codeSent,
              keyboardType: TextInputType.emailAddress,
              decoration: const InputDecoration(labelText: 'Numéro de téléphone ou e-mail', hintText: '77 123 45 67', border: OutlineInputBorder()),
            ),
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
                        if (!_codeSent) {
                          final message = await widget.session.requestCode(_identifier.text);
                          setState(() {
                            _codeSent = true;
                            _info = message;
                          });
                        } else {
                          await widget.session.verify(_identifier.text, _code.text, name: _name.text);
                        }
                      }),
              child: Text(_codeSent ? 'Me connecter' : 'Recevoir mon code'),
            ),
            if (_codeSent)
              TextButton(onPressed: _busy ? null : () => setState(() => _codeSent = false), child: const Text('Changer de numéro')),
          ],
        ),
      ),
    );
  }
}
