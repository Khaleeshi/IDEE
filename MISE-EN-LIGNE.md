# Mettre IDEE en ligne

Ce guide explique comment publier IDEE sur un serveur Webuzo (Nginx + Apache,
PHP 8.3, MySQL). Une fois les étapes 1 à 5 faites, **chaque fusion dans `main`
met le site à jour toute seule** : GitHub lance les tests, prépare le code et
l'envoie sur le serveur.

Tant que la variable `DEPLOY_ENABLED` n'est pas créée (étape 4), GitHub lance
seulement les tests et n'envoie rien.

## 1. Préparer le site dans Webuzo

1. **Adresse** : créez le domaine ou le sous-domaine (par exemple un sous-domaine
   provisoire `idee.votre-domaine.com`). Si le domaine est géré ailleurs,
   ajoutez chez votre registraire un enregistrement DNS de type `A` qui pointe
   vers l'adresse IP du serveur.
2. **Dossier** : choisissez le dossier de l'application, par exemple
   `/home/VOTRE_UTILISATEUR/idee`. La **racine du site** (document root) doit
   être le sous-dossier `public` : `/home/VOTRE_UTILISATEUR/idee/public`.
   Ne pointez jamais la racine sur le dossier de l'application lui-même : le
   fichier `.env` deviendrait lisible depuis Internet.
3. **PHP** : sélectionnez PHP 8.3 (ou plus récent) pour ce domaine, avec les
   extensions `mbstring`, `intl`, `pdo_mysql`, `openssl`, `fileinfo` et `curl`.
4. **HTTPS** : installez un certificat (Webuzo → SSL → Let's Encrypt). Le site
   refuse les cookies de connexion sans HTTPS.
5. **Base de données** : créez une base MySQL et un utilisateur qui a tous les
   droits sur elle. Notez le nom de la base, l'utilisateur et le mot de passe.

## 2. Créer la clé de déploiement

GitHub se connecte au serveur avec une clé SSH qui ne sert qu'à cela. Sur votre
ordinateur (ou sur le serveur), lancez :

```bash
ssh-keygen -t ed25519 -C "deploiement-idee" -f idee_deploy -N ""
```

Deux fichiers sont créés :

- `idee_deploy.pub` (clé publique) : ajoutez son contenu au fichier
  `~/.ssh/authorized_keys` de l'utilisateur Webuzo du site (Webuzo → SSH Access,
  ou à la main). Utilisez l'utilisateur du site, **pas `root`** : sinon les
  fichiers envoyés n'appartiendraient pas au site et PHP ne pourrait pas écrire.
- `idee_deploy` (clé privée) : elle ira dans GitHub à l'étape 3, puis
  supprimez-la de votre ordinateur. Ne l'envoyez jamais par message.

## 3. Ajouter les secrets dans GitHub

Dans le dépôt : **Settings → Secrets and variables → Actions → onglet Secrets
→ New repository secret**.

| Secret | Valeur |
| --- | --- |
| `DEPLOY_HOST` | Adresse IP ou nom du serveur |
| `DEPLOY_USER` | Utilisateur Webuzo du site |
| `DEPLOY_PATH` | Dossier de l'application, par exemple `/home/VOTRE_UTILISATEUR/idee` (sans `/` à la fin) |
| `DEPLOY_SSH_KEY` | Tout le contenu du fichier `idee_deploy` (clé privée) |
| `DEPLOY_KNOWN_HOSTS` | Facultatif : résultat de `ssh-keyscan ADRESSE_DU_SERVEUR`, pour vérifier l'identité du serveur |

## 4. Ajouter les variables dans GitHub

Même page, **onglet Variables → New repository variable**.

| Variable | Valeur |
| --- | --- |
| `DEPLOY_ENABLED` | `true` (c'est l'interrupteur de la mise en ligne automatique) |
| `DEPLOY_PHP` | Chemin de PHP 8.3 sur le serveur, par exemple `/usr/local/apps/php83/bin/php` |
| `DEPLOY_PORT` | Facultatif : port SSH, `22` par défaut |
| `APP_URL` | Adresse du site, par exemple `https://idee.votre-domaine.com` (sans `/` à la fin) |

Pour connaître le chemin de PHP, lancez sur le serveur `ls /usr/local/apps/`
puis choisissez le dossier `php83` (ou plus récent).

## 5. Premier envoi et installation

1. Dans GitHub : **Actions → Mise en ligne → Run workflow**. Le code est envoyé
   sur le serveur ; le journal indique ensuite qu'il reste à terminer
   l'installation.
2. Connectez-vous au serveur en SSH avec l'utilisateur du site, puis :

   ```bash
   cd /home/VOTRE_UTILISATEUR/idee
   bash deploy/premiere-installation.sh /usr/local/apps/php83/bin/php
   ```

   Le script crée le fichier `.env` et s'arrête.
3. Ouvrez `.env` (Webuzo → File Manager, ou `nano .env`) et remplacez chaque
   `CHANGEZ_MOI` : adresse du site, informations de la base MySQL et serveur
   d'e-mails (une boîte créée chez l'hébergeur suffit, par exemple
   `contact@votre-domaine.com` : son serveur SMTP, son identifiant et son mot de passe).
4. Relancez la même commande. Elle crée la clé de chiffrement, les tables et la
   liste des secteurs, puis affiche une ligne de tâche planifiée.
5. Ajoutez cette ligne dans **Webuzo → Cron Jobs**, à exécuter chaque minute.
   Elle relance les demandes de contact sans réponse, les fait expirer et met en
   sommeil les projets inactifs.
6. Ouvrez l'adresse du site : le fil des projets doit s'afficher, et
   `ADRESSE_DU_SITE/up` doit répondre.
7. Connectez-vous une première fois sur le site avec votre numéro, puis donnez-vous
   l'accès à l'administration (signalements, vérifications, fiches, indicateurs) :

   ```bash
   /usr/local/apps/php83/bin/php artisan idee:admin 771234567
   ```

   Le lien « Administration de l'équipe IDEE » apparaît alors dans votre profil.
   Ajoutez au moins une deuxième personne : un recours doit être examiné par un
   autre modérateur que celui qui a pris la décision.

Les mises en ligne suivantes sont automatiques : à chaque fusion dans `main`,
le site passe quelques secondes en maintenance, la base est mise à jour, puis
il revient.

## 6. Envoyer les vrais codes de connexion

Au départ, `IDEE_OTP_DRIVER=log` : les codes sont seulement écrits dans
`storage/logs`, donc aucun vrai membre ne peut encore se connecter. Ouvrez
d'abord un des deux canaux ci-dessous, puis changez la valeur dans `.env` et
lancez `php artisan optimize` (avec le chemin de PHP 8.3).

| Valeur | Canal |
| --- | --- |
| `whatsapp_sms` | WhatsApp d'abord, SMS Orange si WhatsApp échoue (conseillé) |
| `whatsapp` | WhatsApp seulement |
| `sms` | SMS Orange seulement (numéros du Sénégal, +221) |

**SMS Orange** — sur developer.orange.com, créez une application avec l'API
« SMS Sénégal », achetez un forfait de SMS, puis reportez dans `.env` :
`ORANGE_SMS_CLIENT_ID`, `ORANGE_SMS_CLIENT_SECRET` et le numéro d'expéditeur
fourni par Orange dans `ORANGE_SMS_SENDER` (forme `tel:+221…`).
`ORANGE_SMS_SENDER_NAME` est facultatif : il faut le faire valider par Orange.

**WhatsApp** — sur developers.facebook.com, créez une application de type
Business avec le produit WhatsApp, ajoutez et vérifiez le numéro de l'entreprise,
puis créez un modèle de message de catégorie **Authentification**, nommé
`idee_code_connexion`, en français, avec un bouton « Copier le code ». Une fois
le modèle approuvé, reportez dans `.env` un jeton d'accès permanent (utilisateur
système) dans `WHATSAPP_TOKEN` et l'identifiant du numéro dans
`WHATSAPP_PHONE_NUMBER_ID`.

**Alertes WhatsApp** (demandes de contact, rappels, décisions de modération) —
créez un second modèle, de catégorie **Utilitaire**, nommé `idee_alerte`, en
français, dont le texte contient une seule variable, par exemple :
« IDEE : {{1}} ». Une fois approuvé, mettez `IDEE_WHATSAPP_ALERTS=true` dans
`.env`. Chaque membre peut refuser ces alertes dans son profil.

**SMS de secours** — si l'API SMS d'Orange est configurée, mettez aussi
`IDEE_SMS_FALLBACK=true` : une demande de contact ou une relance qui n'a pas pu
partir sur WhatsApp est alors envoyée par SMS (numéros du Sénégal).

Ces branchements ont été vérifiés avec des réponses simulées, pas encore
avec les vrais services : faites un essai avec votre propre numéro avant
d'ouvrir le site au public. En cas d'échec, le motif est écrit dans
`storage/logs`.

## 7. Activer les notifications push (gratuites)

Les membres peuvent recevoir les alertes sur leur téléphone, même IDEE fermé.
Sur le serveur, lancez une seule fois :

```bash
/usr/local/apps/php83/bin/php artisan idee:cles-push
```

Copiez les deux lignes affichées dans `.env`, indiquez votre adresse de contact
dans `WEBPUSH_SUBJECT` (forme `mailto:contact@votre-domaine.com`), puis lancez
`php artisan optimize`. Chaque membre active ensuite les notifications depuis
la page « Notifications » de son téléphone. Ne changez plus ces clés : les
abonnements existants cesseraient de fonctionner.

## 8. Activer l'assistant IA (business plan)

L'assistant utilise Claude Opus 5.5 d'Anthropic, payé à l'usage : 4 $ par million
de jetons (morceaux de mots) lus et 20 $ par million écrits, soit de l'ordre de 100 à
200 FCFA par business plan. Des plafonds par projet limitent la dépense
(`IDEE_AI_PLANS_PER_MONTH`, 3 par défaut).

1. Créez un compte sur platform.claude.com, ajoutez un moyen de paiement et
   fixez une limite de dépense mensuelle.
2. Créez une clé d'API et collez-la dans `.env` : `ANTHROPIC_API_KEY=…`
3. Mettez `IDEE_AI_ENABLED=true`, puis lancez `php artisan optimize`.

Les business plans sont rédigés en arrière-plan : la tâche planifiée de
l'étape 5 les traite chaque minute (`QUEUE_CONNECTION=database`). En cas de
refus pour raison de sécurité, la demande est rejouée automatiquement sur le
modèle de secours d'Anthropic.

## En cas de problème

| Symptôme | Où regarder |
| --- | --- |
| La mise en ligne échoue dans GitHub | Actions → Mise en ligne → étape en rouge |
| Erreur 500 sur le site | `storage/logs/laravel-AAAA-MM-JJ.log` sur le serveur |
| « Permission denied » à l'envoi | La clé publique n'est pas dans `authorized_keys`, ou mauvais `DEPLOY_USER` |
| Page « 419 » à la connexion | Le site n'est pas en HTTPS, ou `APP_URL` ne correspond pas à l'adresse |
| Le site reste en maintenance | Sur le serveur : `php artisan up` (avec le chemin de PHP 8.3) |

Ne mettez jamais le fichier `.env` ni la clé privée sur GitHub.
