# IDEE — application mobile

Application Flutter d'IDEE pour Android et iOS. Elle utilise l'API du serveur
(`backend/`, routes `/api/…`) : rien n'est stocké ailleurs que sur le serveur,
sauf le jeton de connexion, gardé dans le stockage sécurisé du téléphone.

## Ce que fait l'application

- Connexion par code à usage unique (SMS, WhatsApp ou e-mail)
- Projets qui cherchent du soutien, recherche, fiche projet et demande de contact
- Demandes reçues et envoyées, réponse, messagerie après accord des deux
- Parcours guidés : étapes, avancement
- Notifications et profil

Les fonctions plus rares (administration, organisations, paiements…) restent sur
le site, ouvert dans le navigateur du téléphone.

## Lancer l'application

Il faut Flutter 3.47 (`flutter --version`).

```bash
cd mobile
flutter pub get
flutter run --dart-define=API_URL=http://10.0.2.2:8000
```

`10.0.2.2` est l'adresse de l'ordinateur vu depuis l'émulateur Android : lancez
le serveur avec `php artisan serve` dans `backend/`. En production, donnez
l'adresse du site : `--dart-define=API_URL=https://votre-domaine`.

## Vérifier

```bash
flutter analyze
flutter test
```

## Publier

- Android : `flutter build appbundle --dart-define=API_URL=https://votre-domaine`,
  puis envoi sur la Google Play Console (25 USD une fois).
- iOS : `flutter build ipa --dart-define=API_URL=https://votre-domaine` sur un Mac,
  puis App Store Connect (99 USD par an).
- Les deux boutiques demandent une politique de confidentialité et la
  suppression du compte depuis l'application : à prévoir avant la publication.
