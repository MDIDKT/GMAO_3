# Guide de Deploiement — GMAO MVP

> Objectif : deployer l'app pour qu'elle soit testable en ligne.
> Contexte : MVP d'apprentissage, pas encore de production a fort trafic.

---

## Option 1 : Cloudways (recommande pour ton cas)

### Pourquoi Cloudways ?
- Interface simple, pas besoin de gerer un serveur Linux a la main
- Support PHP 8.4 + MySQL 8
- SSH disponible pour les commandes Symfony
- SSL gratuit (Let's Encrypt)
- A partir de ~11$/mois (DigitalOcean 1 Go)

### Etapes de deploiement

#### 1. Creer le serveur
1. Creer un compte sur cloudways.com
2. Choisir : DigitalOcean > 1 Go RAM > Region Paris (ou la plus proche)
3. Lancer le serveur (~2 min)

#### 2. Creer l'application
1. Dans Cloudways : "Add Application" > PHP > Custom App
2. Cloudways fournit : IP, user SSH, base MySQL, phpmyadmin

#### 3. Configurer PHP
Dans l'onglet "Application Settings" :
- PHP Version : 8.4
- Extensions a activer : `intl`, `pdo_mysql`, `mbstring`, `zip`, `gd`
- `memory_limit` : 256M
- `upload_max_filesize` : 10M
- `post_max_size` : 10M

#### 4. Deployer le code
```bash
# En SSH sur le serveur Cloudways
cd /home/master/applications/tonapp/public_html

# Cloner le repo
git clone https://github.com/tonuser/GMAO.git .

# Installer les dependances (sans les devs)
composer install --no-dev --optimize-autoloader

# Creer le .env.local
cp .env .env.local
nano .env.local
```

Contenu du `.env.local` :
```
APP_ENV=prod
APP_SECRET=genere_une_cle_aleatoire_ici
DATABASE_URL="mysql://user:password@127.0.0.1:3306/gmao?serverVersion=8.0"
MAILER_DSN=smtp://user:pass@smtp.cloudways.com:587
```

Pour generer APP_SECRET :
```bash
php -r "echo bin2hex(random_bytes(16));"
```

#### 5. Configurer la base de donnees
```bash
# Creer les tables
php bin/console doctrine:migrations:migrate --no-interaction

# Charger les fixtures (optionnel, pour tester)
php bin/console doctrine:fixtures:load --append
```

#### 6. Configurer le Document Root
Dans Cloudways > Application Settings :
- **Document Root** : `/public`
(Cloudways sert le dossier public_html par defaut, il faut pointer vers public_html/public)

#### 7. Vider le cache et optimiser
```bash
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

#### 8. SSL (HTTPS)
Dans Cloudways > SSL Certificate > Let's Encrypt > Installer
- Cocher "Force HTTPS"

#### 9. Configurer le CRON (optionnel)
Si tu ajoutes des commandes planifiees (SLA, notifications) :
```
*/5 * * * * cd /home/master/applications/tonapp/public_html && php bin/console app:check-sla
```

---

## Option 2 : Railway.app (plus moderne, gratuit pour debuter)

### Pourquoi Railway ?
- Deploiement automatique depuis GitHub (push = deploy)
- Gratuit jusqu'a 5$/mois d'usage
- Support Docker natif
- Base de donnees MySQL en 1 clic
- Pas de gestion de serveur du tout

### Etapes

#### 1. Creer un compte
1. railway.app > Sign up avec GitHub

#### 2. Deployer
1. "New Project" > "Deploy from GitHub repo"
2. Selectionner ton repo GMAO
3. Railway detecte automatiquement PHP/Symfony

#### 3. Ajouter MySQL
1. Dans le projet : "New" > "Database" > "MySQL"
2. Railway genere automatiquement les variables d'environnement

#### 4. Variables d'environnement
Dans l'onglet "Variables" du service :
```
APP_ENV=prod
APP_SECRET=ta_cle_secrete
DATABASE_URL=${{MySQL.DATABASE_URL}}
```

#### 5. Commandes de build
Dans le fichier `Procfile` (a la racine du projet) :
```
web: php -S 0.0.0.0:$PORT -t public
release: php bin/console doctrine:migrations:migrate --no-interaction && php bin/console cache:clear --env=prod
```

Ou mieux, avec un `nixpacks.toml` :
```toml
[phases.setup]
nixPkgs = ["php84", "php84Extensions.pdo_mysql", "php84Extensions.intl"]

[phases.build]
cmds = ["composer install --no-dev --optimize-autoloader"]

[start]
cmd = "php bin/console doctrine:migrations:migrate --no-interaction && php -S 0.0.0.0:$PORT -t public"
```

---

## Option 3 : VPS classique (OVH, Hetzner, DigitalOcean)

### Pour qui ?
Pour ceux qui veulent apprendre l'administration systeme. Plus de controle, plus de complexite.

### Stack recommandee
- Ubuntu 24.04 LTS
- Nginx + PHP-FPM 8.4
- MySQL 8 ou MariaDB 11
- Let's Encrypt (Certbot)
- Git pour le deploiement

### Etapes resumees
```bash
# 1. Installer les paquets
apt update && apt install nginx php8.4-fpm php8.4-mysql php8.4-intl php8.4-mbstring php8.4-zip php8.4-gd mysql-server git composer

# 2. Cloner le projet
cd /var/www
git clone https://github.com/tonuser/GMAO.git gmao
cd gmao

# 3. Installer les dependances
composer install --no-dev --optimize-autoloader

# 4. Configurer Nginx
# Voir config ci-dessous

# 5. Permissions
chown -R www-data:www-data var/
chmod -R 775 var/

# 6. Base de donnees
mysql -u root -e "CREATE DATABASE gmao; CREATE USER 'gmao'@'localhost' IDENTIFIED BY 'mot_de_passe_fort'; GRANT ALL ON gmao.* TO 'gmao'@'localhost';"
php bin/console doctrine:migrations:migrate --no-interaction

# 7. SSL
apt install certbot python3-certbot-nginx
certbot --nginx -d tondomaine.com
```

### Config Nginx
```nginx
server {
    listen 443 ssl;
    server_name tondomaine.com;

    root /var/www/gmao/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/tondomaine.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/tondomaine.com/privkey.pem;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    client_max_body_size 10M;
}
```

---

## Comparatif des 3 options

| Critere | Cloudways | Railway | VPS |
|---------|-----------|---------|-----|
| **Prix** | ~11$/mois | Gratuit → 5$/mois | ~4-6$/mois |
| **Facilite** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐ |
| **Controle** | ⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Auto-deploy (Git push)** | Non (manuel SSH) | Oui | Non (script a faire) |
| **SSL gratuit** | Oui | Oui | Oui (Certbot) |
| **SSH** | Oui | Non | Oui |
| **PHP 8.4** | Oui | Oui (Docker) | Oui |
| **MySQL** | Inclus | Add-on 1 clic | A installer |
| **Apprentissage sysadmin** | Faible | Aucun | Fort |
| **Scalabilite** | Bonne | Bonne | A gerer soi-meme |

---

## Ma recommandation pour ton cas

**Pour tester et montrer le MVP :** Railway.app
- Gratuit, deploiement en 5 minutes, push = deploy
- Parfait pour un MVP de validation

**Pour le futur SaaS en production :** Cloudways ou VPS
- Plus de controle, meilleure performance
- Cloudways si tu veux pas gerer le serveur
- VPS si tu veux apprendre l'administration

---

## Checklist pre-deploiement

- [ ] `APP_ENV=prod` dans .env.local
- [ ] `APP_SECRET` genere avec `bin2hex(random_bytes(16))`
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `php bin/console cache:clear --env=prod`
- [ ] `php bin/console doctrine:migrations:migrate --no-interaction`
- [ ] HTTPS active et force
- [ ] Fichier `var/uploads/` accessible en ecriture par PHP
- [ ] `upload_max_filesize` et `post_max_size` >= 10M dans php.ini
- [ ] Mailer configure (SMTP reel, pas null://)
- [ ] `.env.local` NON commite dans git (.gitignore)
- [ ] Mot de passe base de donnees FORT (pas "password")
