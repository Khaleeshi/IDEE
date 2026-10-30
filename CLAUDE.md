# IDEE — consignes pour les agents

- Langue : l'interface, les messages d'erreur, les commentaires et la documentation sont en français.
- Le serveur est dans `backend/` (Laravel 13). Lancer `php artisan test` et `vendor/bin/pint` depuis ce dossier avant chaque envoi de code.
- Les règles de gestion viennent du cahier des charges (références RG-xx, MR-xx, AA-xx…). Leurs valeurs réglables sont dans `backend/config/idee.php` : ne jamais les écrire en dur ailleurs.
- La logique métier vit dans `backend/app/Services` ; les contrôleurs restent minces.
- Les identifiants exposés dans les adresses web sont des ULID.
- Ne jamais exposer le téléphone ni l'e-mail d'un membre dans une ressource publique (`MemberResource`).
- Trois lignes rouges juridiques : aucun argent de tiers ne transite par la plateforme, aucune offre de titres au public, aucune rémunération au succès d'une levée de fonds.
