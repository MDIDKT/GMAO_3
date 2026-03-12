# Guide de deploiement

Ce document remplace les anciennes variantes generalistes et AlwaysData.

## 1. Cible supportee

Le projet est concu pour :

- PHP 8.4
- MySQL 8+
- Symfony 8
- serveur web pointant vers `public/`

Cas prioritaire documente ici :

- hebergement mutualise ou VPS simple
- AlwaysData inclus comme cas de reference

## 2. Pre-requis avant livraison

- code a jour
- `composer install` deja valide localement
- `npm run build` execute si les assets compiles doivent etre publies
- base locale migrable sans erreur
- variables de prod preparees : `APP_ENV`, `APP_SECRET`, `DATABASE_URL`, `MAILER_DSN`, `MAILER_FROM_ADDRESS`

## 3. Checklist de preparation

Avant transfert :

- verifier que le document root cible `public/`
- ne jamais exposer la racine du projet
- ne pas commiter `.env.local`
- ne pas deployer `var/cache/`, `var/log/`, `node_modules/`
- conserver `public/build/` si les assets sont generes en amont

Commandes utiles :

```bash
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --env=prod
npm run build
```

## 4. Configuration serveur

### Regles generales

- PHP 8.4 actif
- extensions PHP minimales : `pdo_mysql`, `intl`, `mbstring`, `opcache`
- dossier racine web : `.../public`
- HTTPS actif

### Cas AlwaysData

- type de site : PHP
- version PHP : 8.4
- repertoire racine : `www/gmao/GMAO/public`
- base MySQL distincte
- deploiement prefere : Git + SSH

## 5. Variables d'environnement

Fichier a utiliser en prod :

- `.env.local` ou variables serveur

Exemple minimal :

```dotenv
APP_ENV=prod
APP_SECRET=secret-fort
DATABASE_URL="mysql://user:pass@host:3306/gmao?serverVersion=8.0&charset=utf8mb4"
MAILER_DSN="smtp://user:pass@smtp.example.com:587"
MAILER_FROM_ADDRESS="noreply@example.com"
```

## 6. Mise en ligne

Sequence recommandee :

1. transferer ou cloner le code
2. lancer `composer install --no-dev --optimize-autoloader`
3. renseigner la configuration d'environnement
4. lancer les migrations
5. vider et rechauffer le cache prod
6. verifier les permissions sur `var/`
7. tester `/login`, `/activation/{token}`, `/dashboard`

## 7. Verification apres deploiement

- page de login accessible
- connexion fonctionnelle
- upload photo possible
- emails envoyes avec le bon expediteur
- reporting charge sans erreur
- aucune erreur critique dans `var/log/prod.log`

## 8. Mise a jour

Procedure simple :

1. sauvegarder la base
2. recuperer le nouveau code
3. `composer install --no-dev --optimize-autoloader`
4. `php bin/console doctrine:migrations:migrate --no-interaction`
5. `php bin/console cache:clear --env=prod`
6. tester les routes critiques

## 9. Points d'attention

- les routes photo doivent etre securisees avant toute exposition publique
- l'expediteur d'invitation doit etre aligne sur `MAILER_FROM_ADDRESS`
- la base de demo doit etre nettoyee avant une recette externe

