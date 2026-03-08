# GMAO — Application de Gestion de Maintenance Assistée par Ordinateur

Application web Symfony 7 multi-organisations pour la gestion des demandes d'intervention et du suivi de maintenance.

**Production :** https://mdidkt.alwaysdata.net
**Version :** MVP v1.0 — Mars 2026

---

## Fonctionnalités

- Authentification par invitation (email + activation de compte)
- 4 rôles : `ROLE_ADMIN`, `ROLE_PLANIFICATEUR`, `ROLE_TECHNICIEN`, `ROLE_DEMANDEUR`
- Gestion multi-organisations (isolation complète des données)
- CRUD : Sites, Bâtiments, Équipements, Catégories
- Workflow complet : Demande → Qualification → Intervention → Clôture → Validation
- Upload de photos (demandes et interventions, par type : AVANT/APRÈS/COMPLÉMENT)
- Dashboard avec KPI dynamiques par rôle
- Reporting : 4 indicateurs (statuts, délais, techniciens, sites)
- Protection anti-IDOR via Voters Symfony
- Pagination sur toutes les listes

---

## Stack technique

| Technologie | Version |
|---|---|
| PHP | 8.4 |
| Symfony | 7.x |
| Doctrine ORM | 3.x |
| MariaDB | 10.11 (prod) / MySQL 8 (dev) |
| Webpack Encore | 5.x |
| Tailwind CSS | 4.x |
| Flowbite | 4.x |

---

## Installation (développement local)

### Prérequis
- PHP 8.4+
- Composer
- Node.js + npm
- MySQL 8+ ou MariaDB
- Symfony CLI

### Étapes

```bash
git clone https://github.com/MDIDKT/GMAO_3.git
cd GMAO_3

# Dépendances PHP
composer install

# Dépendances JS + compilation assets
npm install
npm run build
```

Crée un fichier `.env.local` :
```dotenv
APP_ENV=dev
APP_SECRET=CHANGE_MOI
DATABASE_URL="mysql://root:@127.0.0.1:3306/gmao?serverVersion=8.0&charset=utf8mb4"
MAILER_DSN=smtp://localhost:1025
DEFAULT_URI=http://localhost:8000
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
APP_SHARE_DIR=var/share
```

Initialise la base de données :
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
```

Charge les données de démo :
```bash
php bin/console doctrine:fixtures:load --append
```

Lance le serveur :
```bash
symfony server:start
```

---

## Comptes de démo (fixtures)

Mot de passe unique : `Test1234!`

| Email | Rôle | Organisation |
|---|---|---|
| admin@gmao.fr | ROLE_ADMIN | GMAO Industries |
| planificateur@gmao.fr | ROLE_PLANIFICATEUR | GMAO Industries |
| tech1@gmao.fr | ROLE_TECHNICIEN | GMAO Industries |
| tech2@gmao.fr | ROLE_TECHNICIEN | GMAO Industries |
| demandeur@gmao.fr | ROLE_DEMANDEUR | GMAO Industries |
| admin@maintenance-sud.fr | ROLE_ADMIN | Maintenance Sud |
| admin@patrimoine.fr | ROLE_ADMIN | Patrimoine Services |
| admin@infra-ouest.fr | ROLE_ADMIN | Infra Support Ouest |

---

## Déploiement (AlwaysData)

Voir le guide complet : [`docs/DEPLOIEMENT-ALWAYSDATA.md`](docs/DEPLOIEMENT-ALWAYSDATA.md)

### Mise à jour rapide

```bash
# Sur le Mac — compiler et pousser
npm run build
git add .
git commit -m "..."
git push

# Sur AlwaysData (SSH)
cd ~/www/gmao
git pull
APP_ENV=prod php bin/console doctrine:migrations:migrate --no-interaction
APP_ENV=prod php bin/console cache:clear
```

---

## Scénario de test nominal

1. Se connecter en **ADMIN** (`admin@gmao.fr`)
2. Vérifier le Dashboard : KPI demandes + interventions
3. Créer une demande → vérifier le numéro auto (`DEM-YYYY-NNNN`)
4. Qualifier la demande
5. Créer une intervention liée
6. Se connecter en **TECHNICIEN** (`tech1@gmao.fr`)
7. Démarrer l'intervention → ajouter des photos
8. Terminer l'intervention (compte rendu obligatoire)
9. Se reconnecter en **ADMIN** → Valider l'intervention
10. Vérifier le Reporting : les 4 KPI reflètent les actions

---

## Journal de développement

<details>
<summary>Voir le journal (Jours 1–18)</summary>

### 14-02-26
- Invitation + réception mail OK

### 15-02-26
- Activation du compte OK
- Validation du mot de passe OK

### 16–17-02-26
- CRUD Sites et Bâtiments
- Filtres sur les listes

### 18–19-02-26
- CRUD Catégories et Équipements
- Filtres finalisés

### 20-02-26
- Fixtures mises en place
- Entité Demande créée (Jour 8)

### 21–22-02-26
- Service numérotation des demandes (`DEM-YYYY-NNNN`)
- Repository pour récupérer le dernier numéro

### 23–24-02-26
- Entité Photo + upload sur demande
- Affichage galerie dans le détail

### 25-02-26
- Pagination KnpPaginator sur les demandes
- Nettoyage des filtres et du repository

### 28-02-26 — Jour 12 : Module Intervention
- Entité Intervention + enum StatutIntervention
- CRUD + NumberingService (`INT-YYYY-NNNN`)
- Formulaires filtrés par organisation

### 02-03-26 — Audit + Jour 13–14
- Corrections MLD : motifRejet, contraintes FK
- Workflow demarrer/terminer intervention
- Upload photos intervention (AVANT/APRÈS/COMPLÉMENT)

### 03-03-26 — Jour 15–16
- InterventionVoter + DemandeVoter (anti-IDOR)
- Dashboard adapté par rôle
- KPI dynamiques

### 05-03-26 — Jour 16 finalisé
- Pagination uniforme (5/page) sur toutes les listes
- Sidebar filtrée par rôle
- Fixtures complètes (4 org / 20 sites / 60 bâtiments / 100 équipements)

### Jour 17
- ReportingController : 4 KPI (statuts, délais, techniciens, sites x priorités)

### Jour 18
- Fixtures complètes et documentées
- README installation

### 08-03-26 — Déploiement production
- Déployé sur AlwaysData (https://mdidkt.alwaysdata.net)
- MariaDB 10.11, PHP 8.4, Apache + .htaccess
- Commande `app:create-admin` ajoutée pour la prod

</details>
