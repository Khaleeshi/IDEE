# IDEE — serveur et API

Application Laravel 13 qui porte l'API du réseau IDEE : comptes, projets,
appels à action, propositions d'aide et demandes de contact.

La présentation du projet, l'installation et la liste des routes se
trouvent dans le [README à la racine du dépôt](../README.md).

## Commandes utiles

```bash
php artisan test                    # lancer les tests
vendor/bin/pint                     # remettre le code au bon format
php artisan migrate:fresh --seed    # recréer la base avec les données de démonstration
php artisan idee:contact-requests   # relances et expirations des demandes (RG-03)
php artisan idee:dormant-projects   # mise en sommeil des projets inactifs (RG-07)
php artisan schedule:work           # lancer les tâches planifiées en local
```

Les règles de gestion réglables (quotas, délais, limites) sont regroupées dans
`config/idee.php` et peuvent être modifiées dans le fichier `.env`.
