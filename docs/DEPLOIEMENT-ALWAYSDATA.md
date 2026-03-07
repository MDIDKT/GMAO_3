# Guide de Déploiement — GMAO sur AlwaysData
> **Tu es formé par un expert Symfony + AlwaysData. Chaque étape est expliquée en profondeur.**
> Version PHP requise : **8.4** — Hébergeur : **AlwaysData (mutualisé)**

---

## Sommaire

1. [Comprendre l'architecture du déploiement](#1-comprendre-larchitecture-du-déploiement)
2. [Prérequis avant de toucher AlwaysData](#2-prérequis-avant-de-toucher-alwaysdata)
3. [Configurer AlwaysData](#3-configurer-alwaysdata)
4. [Déployer le code sur le serveur](#4-déployer-le-code-sur-le-serveur)
5. [Configurer l'environnement de production](#5-configurer-lenvironnement-de-production)
6. [Initialiser la base de données](#6-initialiser-la-base-de-données)
7. [Finaliser Symfony en mode production](#7-finaliser-symfony-en-mode-production)
8. [Vérifications et mise en ligne](#8-vérifications-et-mise-en-ligne)
9. [Procédure de mise à jour (après la v1)](#9-procédure-de-mise-à-jour-après-la-v1)
10. [Résolution des problèmes courants](#10-résolution-des-problèmes-courants)

---

## 1. Comprendre l'architecture du déploiement

### Pourquoi c'est important de comprendre ça avant de toucher quoi que ce soit ?

En développement local, tu lances `symfony server:start` ou `docker compose up`. Le serveur PHP intégré sert directement ton application. En production sur AlwaysData, c'est différent :

```
Internet
   │
   ▼
[AlwaysData Nginx/Apache]  ← reçoit les requêtes HTTP
   │
   │  redirige TOUT vers
   ▼
[/public/index.php]        ← le seul point d'entrée de Symfony
   │
   ▼
[Symfony Kernel]           ← charge l'environnement "prod", pas "dev"
   │
   ▼
[PostgreSQL AlwaysData]    ← base de données sur leur infrastructure
```

**Ce que ça implique concrètement :**

- Le **document root** (la racine du site web) doit pointer sur `public/`, pas sur la racine du projet. Si tu pointes sur la racine, `index.php`, `.env`, `composer.json`, et tout ton code source seront accessibles publiquement — c'est une faille de sécurité majeure.
- Symfony a deux modes : `dev` (verbeux, avec profiler) et `prod` (optimisé, silencieux). En prod, les erreurs ne s'affichent pas à l'écran — elles vont dans `var/log/prod.log`.
- Les **assets** (CSS, JS) doivent être compilés **avant** le déploiement car AlwaysData en hébergement mutualisé n'a pas `npm`/`node` disponible.

---

## 2. Prérequis avant de toucher AlwaysData

Ces étapes se font **sur ton ordinateur**.

### 2.1 Compiler les assets en mode production

**Pourquoi ?** En développement, Webpack Encore génère des assets non minifiés avec des source maps. En production, on génère des fichiers minifiés, versionnés (nom avec hash), et optimisés. Le résultat va dans `public/build/`.

```bash
cd /Users/diakite/GMAO/GMAO
npm run build
```

Tu dois voir des fichiers apparaître dans `public/build/` :
```
public/build/
├── app.css
├── app.js
├── manifest.json    ← Symfony utilise ce fichier pour retrouver les assets versionnés
└── ...
```

> **Si cette commande échoue :** lance d'abord `npm install` puis réessaie.

**Important :** le contenu de `public/build/` doit être déployé sur le serveur. Si tu utilises Git, vérifie que `public/build/` n'est pas dans ton `.gitignore`. Si c'est le cas, tu devras l'uploader manuellement via SFTP.

### 2.2 Générer un APP_SECRET sécurisé

**Pourquoi ?** `APP_SECRET` est une clé secrète utilisée par Symfony pour :
- Signer les tokens CSRF (protection contre les attaques sur les formulaires)
- Sécuriser les sessions
- Générer des tokens sécurisés (liens d'activation, etc.)

Ton `.env` actuel a `APP_SECRET=` vide — c'est une faille critique. Génère-en un maintenant :

```bash
openssl rand -hex 32
```

**Copie le résultat** (ex: `a3f8c2e1d4b5...`), tu en auras besoin à l'étape 5.

### 2.3 Vérifier que le projet est propre

```bash
cd /Users/diakite/GMAO/GMAO
git status
```

Assure-toi que tous tes fichiers importants sont commités (ou prêts à être uploadés). Les fichiers suivants **ne doivent jamais** être dans le repo Git ni sur le serveur public :
- `.env.local` (contient les mots de passe)
- `var/cache/`
- `var/log/`

---

## 3. Configurer AlwaysData

### 3.1 Créer la base de données MySQL

**Pourquoi MySQL ?** Tes migrations utilisent la syntaxe MySQL (`ALTER TABLE ... CHANGE ...`). Doctrine est configuré avec le driver `pdo_mysql`. C'est la base de données de ton projet.

1. Connecte-toi sur [admin.alwaysdata.com](https://admin.alwaysdata.com)
2. Menu **Bases de données → MySQL**
3. Clique sur **Ajouter une base de données**
   - Nom : `gmao` (AlwaysData préfixe automatiquement avec ton identifiant, ça donnera `diakite_gmao`)
4. Clique sur **Ajouter un utilisateur MySQL**
   - Identifiant : `gmao_user` (deviendra `diakite_gmao_user`)
   - Mot de passe : génère-en un fort et note-le
   - Droits : **Tous les droits** sur la base `diakite_gmao`

**L'hôte MySQL AlwaysData** est toujours de la forme :
```
mysql-TONIDENTIFIANT.alwaysdata.net
```
Ex: `mysql-diakite.alwaysdata.net`

> Ce n'est **pas** `localhost`. Sur un hébergement mutualisé, la base de données est sur un serveur séparé du serveur web.

### 3.2 Configurer le site web (document root)

1. Menu **Web → Sites**
2. Clique sur ton site (ou **Ajouter un site**)
3. Configure :

| Paramètre | Valeur | Pourquoi |
|---|---|---|
| **Adresses** | `tondomaine.alwaysdata.net` | Ton domaine |
| **Type** | PHP | Ton application est en PHP |
| **Version PHP** | **8.4** | Ton `composer.json` exige `>=8.4` |
| **Dossier racine** | `www/gmao/GMAO/public` | ⚠️ Critique : pointe sur `/public` |
| **Activer HTTPS** | Oui | Toujours activer SSL |

> **Sur AlwaysData**, le champ s'appelle **"Répertoire racine"** et le chemin est relatif à `/home/diakite/`. Donc saisis juste `www/gmao/GMAO/public` sans le `/home/diakite/`.

**Explication du dossier racine :**
Sur AlwaysData, le dossier de base de ton compte est `/home/diakite/`. Le dossier `www/` est conventionnellement utilisé pour les sites web. On va cloner le projet dans `www/gmao/`, donc la structure sera :

```
/home/diakite/
└── www/
    └── gmao/
        └── GMAO/           ← racine du projet Symfony
            ├── public/     ← document root à configurer
            ├── src/
            ├── .env
            └── ...
```

### 3.3 Configurer PHP

1. Menu **Web → Configuration PHP** (ou dans les paramètres du site)
2. Vérifie/active ces extensions :
   - `pdo_mysql` — connexion MySQL
   - `intl` — internationalisation Symfony
   - `mbstring` — gestion des chaînes multi-octets
   - `opcache` — mise en cache du bytecode PHP (performances)

### 3.4 Configurer l'email

Les invitations utilisateurs envoient des emails. AlwaysData fournit un serveur SMTP.

1. Menu **Emails** → crée ou identifie une adresse type `noreply@tondomaine.fr`
2. Note les paramètres SMTP :
   - Hôte : `smtp-diakite.alwaysdata.net`
   - Port : `587` (STARTTLS)
   - Identifiant : ton adresse email complète
   - Mot de passe : celui de ta boîte email

---

## 4. Déployer le code sur le serveur

### Option A — Via Git (recommandé)

**Pourquoi Git est préférable ?** Git te permet de déployer en une commande, de versionner, et de revenir en arrière facilement.

#### 4.1 Se connecter en SSH

```bash
ssh diakite@ssh-diakite.alwaysdata.net
```

> AlwaysData fournit l'accès SSH sur tous les comptes. Si tu n'as pas encore ajouté ta clé SSH publique, va dans **Accès distant → SSH** dans l'interface admin.

#### 4.2 Cloner le projet

```bash
# Se placer dans le bon répertoire
cd ~/www

# Cloner le repo (remplace par ton URL)
git clone https://github.com/TON_COMPTE/GMAO.git gmao

# Se placer dans le projet Symfony
cd gmao/GMAO
```

#### 4.3 Installer les dépendances PHP

```bash
composer install --no-dev --optimize-autoloader
```

**Explication des options :**
- `--no-dev` : n'installe pas les packages de développement (PHPUnit, Symfony profiler, etc.). Réduit la taille et améliore la sécurité.
- `--optimize-autoloader` : génère un autoloader optimisé avec un fichier de map statique. En dev, l'autoloader cherche les fichiers à la volée. En prod, il a une carte complète → **bien plus rapide**.

> Cette commande peut prendre 1-2 minutes. C'est normal.

---

### Option B — Via SFTP (si pas de Git)

Utilise **FileZilla** ou **Cyberduck** :
- Hôte : `sftp-diakite.alwaysdata.net`
- Port : `22`
- Identifiant/mot de passe : tes credentials AlwaysData

Upload **tout le projet** dans `/home/diakite/www/gmao/GMAO/`, **sauf** :
- `vendor/` (trop lourd — tu feras `composer install` en SSH)
- `node_modules/`
- `var/cache/`
- `var/log/`

Puis connecte-toi en SSH et lance `composer install --no-dev --optimize-autoloader`.

---

## 5. Configurer l'environnement de production

### Pourquoi ne pas modifier `.env` directement ?

Le fichier `.env` est dans Git — c'est intentionnel. Il contient les valeurs **par défaut** pour le développement. En production, on ne le modifie pas. On crée un fichier `.env.local` qui **surcharge** uniquement les valeurs à changer.

**Symfony charge les fichiers dans cet ordre** (le dernier gagne) :
```
.env          ← valeurs par défaut (dans Git)
.env.local    ← surcharges locales (jamais dans Git)
.env.prod     ← surcharges spécifiques à prod (peut être dans Git sans secrets)
.env.prod.local ← le plus prioritaire (jamais dans Git)
```

### 5.1 Créer `.env.local` sur le serveur

```bash
# Toujours depuis ~/www/gmao/GMAO/
nano .env.local
```

Colle et adapte ce contenu :

```dotenv
# ─── MODE PRODUCTION ──────────────────────────────────────────────
APP_ENV=prod
APP_DEBUG=0

# ─── SÉCURITÉ ─────────────────────────────────────────────────────
# Colle ici le résultat de : openssl rand -hex 32
APP_SECRET=COLLE_TON_SECRET_ICI

# ─── BASE DE DONNÉES ──────────────────────────────────────────────
# Format : mysql://USER:PASSWORD@HOST:3306/DBNAME?serverVersion=8.0&charset=utf8mb4
DATABASE_URL="mysql://diakite_gmao_user:TON_MOT_DE_PASSE@mysql-diakite.alwaysdata.net:3306/diakite_gmao?serverVersion=8.0&charset=utf8mb4"

# ─── EMAIL ────────────────────────────────────────────────────────
# Format Symfony Mailer DSN
MAILER_DSN=smtp://noreply%40tondomaine.fr:TON_MOT_DE_PASSE_EMAIL@smtp-diakite.alwaysdata.net:587

# ─── URL DE L'APPLICATION ─────────────────────────────────────────
DEFAULT_URI=https://tondomaine.alwaysdata.net

# ─── MESSENGER (file de messages async) ───────────────────────────
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0

# ─── UPLOAD ───────────────────────────────────────────────────────
APP_SHARE_DIR=var/share
```

> **⚠️ Note sur MAILER_DSN :** le `@` dans l'email doit être encodé en `%40` dans l'URL DSN.

Sauvegarde : `Ctrl+O`, `Entrée`, puis `Ctrl+X`.

### 5.2 Sécuriser les permissions du fichier

```bash
chmod 600 .env.local
```

`600` = lecture/écriture uniquement pour le propriétaire. Personne d'autre ne peut lire ce fichier.

### 5.3 Préparer le dossier var/

```bash
mkdir -p var/cache var/log var/share
chmod -R 775 var/
```

**Pourquoi `775` ?** Le serveur web (Apache/Nginx) tourne sous un utilisateur différent du tien mais dans le même groupe. `775` autorise le groupe à écrire dans `var/` — nécessaire pour que Symfony puisse écrire ses logs et son cache.

---

## 6. Initialiser la base de données

### 6.1 Lancer les migrations

**Qu'est-ce qu'une migration ?** C'est un fichier PHP généré par Doctrine qui décrit les modifications à apporter à la base de données (créer des tables, ajouter des colonnes, etc.). Ton projet en a **10** — elles seront jouées dans l'ordre.

```bash
APP_ENV=prod php bin/console doctrine:migrations:migrate --no-interaction
```

- `APP_ENV=prod` : force l'utilisation des paramètres de production (sinon Symfony utilise `dev` et tenterait de se connecter à ta DB locale)
- `--no-interaction` : ne demande pas de confirmation (utile pour les scripts automatisés)

Tu dois voir :
```
[notice] Migrating up to DoctrineMigrations\Version20260301102519
[notice] finished in Xs, used XMiB memory, X migrations executed, X sql queries
```

> **Si une migration échoue :** note le numéro de version qui a planté et consulte la section [Résolution des problèmes](#10-résolution-des-problèmes-courants).

### 6.2 Vérifier l'état des migrations

```bash
APP_ENV=prod php bin/console doctrine:migrations:status
```

Tu dois voir `Current == Latest` et `New: 0`.

---

## 7. Finaliser Symfony en mode production

### 7.1 Vider et préchauffer le cache

**Pourquoi deux commandes ?**
- `cache:clear` supprime l'ancien cache (dev ou un précédent déploiement)
- `cache:warmup` pré-génère le cache : injection de dépendances compilée, routes compilées, templates Twig compilés. Sans ça, Symfony les génère à la première requête — plus lent.

```bash
APP_ENV=prod php bin/console cache:clear
APP_ENV=prod php bin/console cache:warmup
```

### 7.2 Vérifier les assets

Si tu as déployé via Git et que `public/build/` est dans ton `.gitignore`, tu dois uploader ce dossier manuellement via SFTP.

Vérifie que le dossier existe :
```bash
ls public/build/
# Doit afficher : app.css, app.js, manifest.json, etc.
```

### 7.3 Créer le premier admin (si base vide)

Si c'est un déploiement sur une base toute neuve, tu n'as pas d'utilisateur. Deux options :

**Option 1 — Utiliser les fixtures de dev** (si tu en as)
```bash
# ⚠️ Uniquement si tu veux des données de test — efface la base !
APP_ENV=prod php bin/console doctrine:fixtures:load
```

**Option 2 — Créer le premier admin manuellement**
```bash
APP_ENV=prod php bin/console app:create-admin
# ou selon ta commande personnalisée
```

> Si tu n'as pas de commande dédiée, tu peux créer un utilisateur via le formulaire d'inscription puis modifier son rôle directement en SQL :
> ```sql
> UPDATE "user" SET roles = '["ROLE_ADMIN"]' WHERE email = 'ton@email.fr';
> ```

---

## 8. Vérifications et mise en ligne

### 8.1 Checklist finale

Effectue chaque vérification dans l'ordre :

```
□ Ouvrir https://tondomaine.alwaysdata.net
□ La page de login s'affiche (pas d'erreur 500)
□ Le CSS et le JS se chargent (pas de page blanche sans style)
□ Se connecter avec un compte admin
□ Créer un site, un bâtiment, un équipement → vérifier que ça s'enregistre
□ Inviter un utilisateur → vérifier que l'email arrive
□ Activer le compte depuis le lien email → vérifier la redirection
□ Créer une demande d'intervention en tant que demandeur
□ Qualifier la demande en tant que planificateur
□ Créer et terminer une intervention en tant que technicien
```

### 8.2 Surveiller les logs en cas d'erreur

Si quelque chose ne fonctionne pas, **ne cherche pas dans le navigateur** (Symfony affiche une page d'erreur générique en prod). Va directement dans les logs :

```bash
tail -f ~/www/gmao/GMAO/var/log/prod.log
```

Puis reproduis l'erreur dans le navigateur. Le log affichera l'erreur précise.

### 8.3 Vérification des routes

```bash
APP_ENV=prod php bin/console debug:router | grep -v _profiler
```

Toutes tes routes doivent apparaître. Si une route manque, le cache n'est pas à jour → relance `cache:warmup`.

---

## 9. Procédure de mise à jour (après la v1)

Quand tu développes les améliorations du plan et que tu veux les déployer :

```bash
# 1. En local : compiler les assets
npm run build

# 2. Committer et pusher
git add .
git commit -m "feat: amélioration XYZ"
git push

# 3. Sur le serveur (via SSH)
cd ~/www/gmao/GMAO

# Récupérer les changements
git pull

# Mettre à jour les dépendances si composer.json a changé
composer install --no-dev --optimize-autoloader

# Jouer les nouvelles migrations si il y en a
APP_ENV=prod php bin/console doctrine:migrations:migrate --no-interaction

# Vider et reconstruire le cache
APP_ENV=prod php bin/console cache:clear
APP_ENV=prod php bin/console cache:warmup

# Si les assets ont changé, les uploader via SFTP (si public/build/ est dans .gitignore)
# sinon git pull suffit
```

> **Bonne pratique :** crée un fichier `deploy.sh` à la racine du projet avec ces commandes — tu n'auras qu'à l'exécuter.

---

## 10. Résolution des problèmes courants

### Erreur 500 au chargement

```bash
tail -50 ~/www/gmao/GMAO/var/log/prod.log
```
Cherche `[critical]` ou `[error]`.

### "An exception occurred while executing a query"

→ Les migrations n'ont pas toutes été jouées, ou `DATABASE_URL` est incorrect.
→ Vérifie aussi le `serverVersion` dans `DATABASE_URL`. Pour connaître la version MySQL d'AlwaysData :
```bash
mysql -h mysql-diakite.alwaysdata.net -u diakite_gmao_user -p -e "SELECT VERSION();"
```
Puis ajuste `?serverVersion=8.0` en conséquence.
```bash
APP_ENV=prod php bin/console doctrine:migrations:status
```

### Les assets ne se chargent pas (page sans style)

→ `public/build/` est vide ou absent. Upload-le via SFTP depuis ton ordinateur.

### Les emails n'arrivent pas

→ Vérifie le `MAILER_DSN`. Le `@` doit être encodé `%40`. Teste :
```bash
APP_ENV=prod php bin/console mailer:test ton@email.fr
```

### "Permission denied" sur var/

```bash
chmod -R 775 ~/www/gmao/GMAO/var/
```

### Symfony utilise encore le mode dev

→ Vérifie que `.env.local` contient bien `APP_ENV=prod` et que le cache a été vidé.
```bash
APP_ENV=prod php bin/console about
# La ligne "Environment" doit afficher "prod"
```

### Composer échoue : "Your PHP version does not satisfy requirements"

→ AlwaysData est peut-être sur une version PHP inférieure à 8.4. Vérifie :
```bash
php -v
```
Si < 8.4, change la version PHP dans **Web → Sites → Configuration PHP** et reconnecte-toi en SSH (la session SSH garde l'ancienne version).

---

*Document généré le 07/03/2026 — GMAO MVP v1*
