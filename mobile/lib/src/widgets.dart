import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'api.dart';
import 'models.dart';

/// Violet IDEE : le foncé pour les boutons et le texte, le clair pour les décors.
const brand = Color(0xFF5B21B6);
const brandDark = Color(0xFF3B0764);
const brandLight = Color(0xFF8B5CF6);
const brandTint = Color(0xFFEDE9FE);

/// Dégradé du violet foncé au violet clair, pour les en-têtes.
const brandGradient = LinearGradient(
  begin: Alignment.topLeft,
  end: Alignment.bottomRight,
  colors: [brandDark, brand, Color(0xFF6D28D9)],
);

/// Tons pierre du site : fond, traits, textes.
const canvas = Color(0xFFFAFAF9);
const canvasDeep = Color(0xFFF5F5F4);
const line = Color(0xFFE7E5E4);
const lineStrong = Color(0xFFD6D3D1);
const ink = Color(0xFF1C1917);
const inkSoft = Color(0xFF57534E);
const muted = Color(0xFF78716C);

/// Ambre des besoins (« Cherche : … »), comme sur le site.
const needTint = Color(0xFFFFFBEB);
const needInk = Color(0xFF78350F);

/// Largeur de lecture : au-delà, sur tablette, le contenu reste centré.
const readableWidth = 720.0;

/// Marges d'une page : 16 sur téléphone, davantage sur tablette pour centrer le contenu.
EdgeInsets pagePadding(BuildContext context, {double top = 16, double bottom = 24}) {
  final width = MediaQuery.sizeOf(context).width;
  final side = width > readableWidth + 32 ? (width - readableWidth) / 2 : 16.0;
  return EdgeInsets.fromLTRB(side, top, side, bottom);
}

/// Limite la largeur d'un bloc et le centre.
class Readable extends StatelessWidget {
  const Readable({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Center(child: ConstrainedBox(constraints: const BoxConstraints(maxWidth: readableWidth), child: child));
  }
}

/// Le nom IDEE, en blanc sur fond violet ou en dégradé sur fond clair.
class IdeeLogo extends StatelessWidget {
  const IdeeLogo({super.key, this.size = 22, this.onDark = false});

  final double size;
  final bool onDark;

  @override
  Widget build(BuildContext context) {
    final text = Text('IDEE', style: TextStyle(fontSize: size, fontWeight: FontWeight.w800, letterSpacing: -0.5, color: Colors.white));
    if (onDark) return text;
    return ShaderMask(shaderCallback: (bounds) => const LinearGradient(colors: [brandDark, brandLight]).createShader(bounds), child: text);
  }
}

/// En-tête violet des pages principales : logo, titre, phrase d'accroche et,
/// au besoin, un champ (la recherche par exemple).
class BrandHeader extends StatelessWidget {
  const BrandHeader({super.key, required this.title, this.subtitle, this.child});

  final String title;
  final String? subtitle;
  final Widget? child;

  @override
  Widget build(BuildContext context) {
    final side = pagePadding(context).left + 4;
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Container(
        decoration: const BoxDecoration(gradient: brandGradient, borderRadius: BorderRadius.vertical(bottom: Radius.circular(28))),
        child: SafeArea(
          bottom: false,
          child: Padding(
            padding: EdgeInsets.fromLTRB(side, 12, side, 20),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              const IdeeLogo(size: 18, onDark: true),
              const SizedBox(height: 14),
              Text(title, style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w800, color: Colors.white, letterSpacing: -0.5)),
              if (subtitle != null) ...[
                const SizedBox(height: 4),
                Text(subtitle!, style: TextStyle(fontSize: 15, height: 1.35, color: Colors.white.withValues(alpha: 0.82))),
              ],
              if (child != null) ...[const SizedBox(height: 16), child!],
            ]),
          ),
        ),
      ),
    );
  }
}

/// Pastille de texte : secteur, étape, région…
class Tag extends StatelessWidget {
  const Tag(this.text, {super.key, this.icon, this.background = canvasDeep, this.foreground = inkSoft});

  /// Besoin d'un projet, en ambre comme sur le site.
  const Tag.need(this.text, {super.key, this.icon}) : background = needTint, foreground = needInk;

  /// Secteur ou statut, en violet clair.
  const Tag.brand(this.text, {super.key, this.icon}) : background = brandTint, foreground = brand;

  final String text;
  final IconData? icon;
  final Color background;
  final Color foreground;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: background, borderRadius: BorderRadius.circular(20)),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        if (icon != null) ...[Icon(icon, size: 14, color: foreground), const SizedBox(width: 4)],
        Flexible(child: Text(text, overflow: TextOverflow.ellipsis, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: foreground))),
      ]),
    );
  }
}

/// Initiales d'un membre dans un rond violet clair.
class Avatar extends StatelessWidget {
  const Avatar(this.name, {super.key, this.size = 40});

  final String name;
  final double size;

  @override
  Widget build(BuildContext context) {
    final initials = name.trim().split(RegExp(r'\s+')).where((part) => part.isNotEmpty && RegExp(r'[A-Za-zÀ-ÿ]').hasMatch(part[0])).take(2).map((part) => part[0].toUpperCase()).join();
    return CircleAvatar(
      radius: size / 2,
      backgroundColor: brandTint,
      child: Text(initials.isEmpty ? '?' : initials, style: TextStyle(fontSize: size * 0.38, fontWeight: FontWeight.w700, color: brand)),
    );
  }
}

/// Titre d'une partie de page.
class SectionTitle extends StatelessWidget {
  const SectionTitle(this.text, {super.key});

  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 24, bottom: 8),
      child: Text(text, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700, color: ink)),
    );
  }
}

/// Montant en FCFA avec les milliers séparés : 1 500 000 FCFA.
String formatFcfa(int amount) {
  final digits = amount.toString();
  final grouped = digits.replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (_) => ' ');
  return '$grouped FCFA';
}

/// Charge des données et affiche le chargement, l'erreur ou le résultat.
class Loader<T> extends StatefulWidget {
  const Loader({super.key, required this.load, required this.builder});

  final Future<T> Function() load;
  final Widget Function(BuildContext context, T data, Future<void> Function() reload) builder;

  @override
  State<Loader<T>> createState() => _LoaderState<T>();
}

class _LoaderState<T> extends State<Loader<T>> {
  late Future<T> _future = widget.load();

  Future<void> _reload() async {
    final next = widget.load();
    setState(() => _future = next);
    await next;
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<T>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.hasError) {
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                Text(snapshot.error is ApiException ? '${snapshot.error}' : 'Une erreur est survenue.', textAlign: TextAlign.center),
                const SizedBox(height: 12),
                OutlinedButton(onPressed: _reload, child: const Text('Réessayer')),
              ]),
            ),
          );
        }
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        return RefreshIndicator(onRefresh: _reload, child: widget.builder(context, snapshot.data as T, _reload));
      },
    );
  }
}

class StatusChip extends StatelessWidget {
  const StatusChip(this.status, {super.key});

  final Label status;

  @override
  Widget build(BuildContext context) {
    return Tag.brand(status.label);
  }
}

class EmptyState extends StatelessWidget {
  const EmptyState(this.message, {super.key, this.icon = Icons.inbox_outlined});

  final String message;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return ListView(padding: const EdgeInsets.fromLTRB(32, 56, 32, 32), children: [
      Center(
        child: Container(
          width: 72,
          height: 72,
          decoration: const BoxDecoration(color: brandTint, shape: BoxShape.circle),
          child: Icon(icon, size: 32, color: brand),
        ),
      ),
      const SizedBox(height: 16),
      Text(message, textAlign: TextAlign.center, style: const TextStyle(fontSize: 15, height: 1.4, color: inkSoft)),
    ]);
  }
}

/// Montre l'erreur de l'API dans un bandeau.
void showError(BuildContext context, Object error) {
  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error is ApiException ? error.message : 'Une erreur est survenue.')));
}

String formatDate(DateTime date) {
  const months = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
  return '${date.day} ${months[date.month - 1]} ${date.year}';
}

/// Avertissement sous un message qui parle d'argent, d'un code ou de coordonnées bancaires.
class ScamAlert extends StatelessWidget {
  const ScamAlert({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(top: 8),
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(color: Colors.amber.shade50, borderRadius: BorderRadius.circular(10), border: Border.all(color: Colors.amber.shade300)),
      child: Text(
        'Prudence : un investisseur ou une institution ne demande jamais d\'argent pour étudier un dossier. '
        'Ne transmettez jamais un code reçu par SMS. En cas de doute, signalez ce message sur le site.',
        style: TextStyle(fontSize: 12, color: Colors.brown.shade900),
      ),
    );
  }
}
