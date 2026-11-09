import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'widgets.dart';

/// Thème de l'application, aligné sur le site : fond pierre, cartes blanches
/// arrondies et bordées, boutons violets, barres blanches sans ombre.
ThemeData ideeTheme() {
  final scheme = ColorScheme.fromSeed(
    seedColor: brand,
    primary: brand,
    onPrimary: Colors.white,
    secondary: brandLight,
    primaryContainer: brandTint,
    onPrimaryContainer: brandDark,
    surface: Colors.white,
    onSurface: ink,
    onSurfaceVariant: inkSoft,
    outline: lineStrong,
    outlineVariant: line,
    surfaceTint: Colors.transparent,
  );
  const radius = BorderRadius.all(Radius.circular(14));
  const semiBold = TextStyle(fontSize: 15, fontWeight: FontWeight.w600);
  OutlineInputBorder outline(Color color, [double width = 1]) =>
      OutlineInputBorder(borderRadius: radius, borderSide: BorderSide(color: color, width: width));

  final base = ThemeData(colorScheme: scheme, useMaterial3: true);
  return base.copyWith(
    scaffoldBackgroundColor: canvas,
    textTheme: base.textTheme.apply(bodyColor: ink, displayColor: ink),
    appBarTheme: const AppBarTheme(
      backgroundColor: Colors.white,
      foregroundColor: ink,
      elevation: 0,
      scrolledUnderElevation: 0,
      surfaceTintColor: Colors.transparent,
      centerTitle: false,
      systemOverlayStyle: SystemUiOverlayStyle.dark,
      titleTextStyle: TextStyle(fontSize: 20, fontWeight: FontWeight.w700, color: ink, letterSpacing: -0.2),
      shape: Border(bottom: BorderSide(color: line)),
    ),
    cardTheme: const CardThemeData(
      color: Colors.white,
      elevation: 0,
      margin: EdgeInsets.symmetric(vertical: 6),
      clipBehavior: Clip.antiAlias,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.all(Radius.circular(18)), side: BorderSide(color: line)),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      border: outline(lineStrong),
      enabledBorder: outline(lineStrong),
      disabledBorder: outline(line),
      focusedBorder: outline(brand, 2),
      errorBorder: outline(Colors.red.shade300),
      focusedErrorBorder: outline(Colors.red.shade700, 2),
      labelStyle: const TextStyle(color: inkSoft),
      hintStyle: const TextStyle(color: muted),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: brand,
        foregroundColor: Colors.white,
        minimumSize: const Size(64, 48),
        shape: const RoundedRectangleBorder(borderRadius: radius),
        textStyle: semiBold.copyWith(fontSize: 16),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        foregroundColor: brand,
        minimumSize: const Size(64, 48),
        side: const BorderSide(color: lineStrong),
        shape: const RoundedRectangleBorder(borderRadius: radius),
        textStyle: semiBold,
      ),
    ),
    textButtonTheme: TextButtonThemeData(style: TextButton.styleFrom(foregroundColor: brand, textStyle: semiBold)),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.transparent,
      elevation: 0,
      height: 68,
      indicatorColor: brandTint,
      labelTextStyle: WidgetStateProperty.resolveWith((states) => TextStyle(
            fontSize: 12,
            fontWeight: states.contains(WidgetState.selected) ? FontWeight.w700 : FontWeight.w500,
            color: states.contains(WidgetState.selected) ? brand : muted,
          )),
      iconTheme: WidgetStateProperty.resolveWith((states) => IconThemeData(color: states.contains(WidgetState.selected) ? brand : muted)),
    ),
    chipTheme: const ChipThemeData(backgroundColor: canvasDeep, side: BorderSide.none, shape: StadiumBorder(), labelStyle: TextStyle(fontSize: 12, color: ink)),
    dividerTheme: const DividerThemeData(color: line, space: 1, thickness: 1),
    listTileTheme: const ListTileThemeData(iconColor: brand),
    expansionTileTheme: const ExpansionTileThemeData(shape: Border(), collapsedShape: Border(), iconColor: brand),
    progressIndicatorTheme: const ProgressIndicatorThemeData(color: brand, linearTrackColor: brandTint),
    snackBarTheme: const SnackBarThemeData(
      behavior: SnackBarBehavior.floating,
      backgroundColor: ink,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.all(Radius.circular(12))),
    ),
    dialogTheme: const DialogThemeData(
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.transparent,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.all(Radius.circular(20))),
    ),
    bottomSheetTheme: const BottomSheetThemeData(
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.transparent,
      showDragHandle: true,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
    ),
  );
}
