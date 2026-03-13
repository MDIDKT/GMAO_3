# GMAO MVP

Application web de gestion de maintenance assistee par ordinateur construite avec Symfony 8. Le projet couvre le cycle MVP de suivi des demandes, planification des interventions, gestion des equipements et controle d'acces multi-organisation.

## Fonctionnalites

- Gestion multi-organisation avec isolation par organisation
- Referentiels techniques : sites, batiments, categories et equipements
- Demandes de maintenance avec priorite, qualification, rejet et photos
- Interventions avec planification, demarrage, cloture, validation et photos avant/apres
- Dashboards et reporting avec KPI metier
- Interface responsive avec sidebar mobile et bascule dark/light
- Notifications email metier sur les etapes clefs
- Tests unitaires et fonctionnels sur les workflows sensibles

## Stack technique

- PHP 8.4
- Symfony 8
- Doctrine ORM et Doctrine Migrations
- Twig
- Webpack Encore, Tailwind CSS et Flowbite
- PHPUnit
- MySQL ou MariaDB

## Roles applicatifs

- `ROLE_ADMIN`
- `ROLE_PLANIFICATEUR`
- `ROLE_TECHNICIEN`
- `ROLE_DEMANDEUR`

## Prerequis

- PHP 8.4 ou plus
- Composer
- Node.js et npm
- MySQL 8 ou MariaDB 10.11+
- Symfony CLI optionnel

## Installation

```bash
git clone <url-du-repo>
cd GMAO
composer install
npm install
```

Creer un fichier `.env.local` adapte a ton environnement :

```dotenv
APP_ENV=dev
APP_SECRET=change-me
DATABASE_URL="mysql://user:password@127.0.0.1:3306/gmao?serverVersion=8.0"
MAILER_DSN="smtp://localhost:1025"
```

Initialiser la base puis charger les assets :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
npm run build
```

Pour charger un jeu de donnees local de demonstration :

```bash
php bin/console doctrine:fixtures:load --append
```

## Lancement

Avec Symfony CLI :

```bash
symfony server:start
```

Ou avec le serveur PHP integre :

```bash
php -S 127.0.0.1:8000 -t public
```

## Qualite et verification

Commandes utiles avant validation :

```bash
php bin/phpunit
php bin/console lint:container
php bin/console lint:twig templates
php bin/console doctrine:schema:validate --skip-sync
```

## Conventions Twig / Tailwind

- Les formulaires Symfony utilisent un theme Twig global : `templates/form/tailwind_theme.html.twig`
- Par defaut, utiliser `{{ form_row(form.champ) }}`
- Ne separer `form_label()`, `form_widget()` et `form_errors()` qu'en cas de besoin de layout specifique
- Laisser `form_start()` gerer automatiquement le `multipart/form-data`
- En Tailwind v4, preferer `shrink-0` a `flex-shrink-0`

## Structure

```text
src/        logique metier, controllers, services, voters, repositories
templates/  vues Twig
templates/form/  theme global des formulaires Symfony
tests/      tests unitaires et fonctionnels
public/     point d'entree HTTP et assets compiles
docs/       documentation locale du projet
```

## Etat du projet

Le projet est au stade MVP avec workflows demande/intervention, reporting, notifications email et couverture de tests unitaires et fonctionnels sur les points sensibles. L'interface principale repose sur un layout responsive desktop/mobile avec theme sombre et clair, une sidebar harmonisee, des cartes KPI a accent colore coherent en dark comme en light, et un dashboard qui remonte maintenant de vraies demandes prioritaires et interventions du jour dans des blocs de synthese. Un audit recent a aussi conduit au durcissement des controles d'acces multi-organisation, et la navigation mobile a ete revalidee en navigateur apres correction du drawer ferme qui captait encore les clics.
