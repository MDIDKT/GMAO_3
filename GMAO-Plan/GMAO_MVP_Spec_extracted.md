GMAO Saa
S MVP — Symfony 8.0 · PHP 8.4v1.2 — 12/02/2026GMAO Saa
S Cahier des Charges MVPStack technique
Symfony 8.0.5 · PHP 8.4 · Doctrine ORM 3.x · Twig · Tailwind CSS v4.1 · My
SQL 8+
** Attention : Symfony 8.0 n'est pas LTS (support jusqu'a juillet 2026). LTS actuel : 7.4 (securite jusqu'a nov. 2029). Prochain LTS : 8.4 (nov. 2027).
** Document complementaire : Un aide-memoire technique accompagne ce cahier des charges. Il contient les syntaxes Symfony, Doctrine, Security et Upload dont tu auras besoin sans retourner dans la doc.Version 1.2 — 12 fevrier 2026 Duree estimee : 19 jours · 3 semaines
Page 1GMAO Saa
S MVP — Symfony 8.0 · PHP 8.4v1.2 — 12/02/2026Table des matieres

## 1. Perimetre MVP

## 2. Roles, pages, parcours

## 3. Matrice des permissions

## 4. Workflows de statuts

## 5. Modele de donnees (MLD Doctrine)

## 6. Numerotation (DEM/INT)

## 7. Upload photos

## 8. Securite MVP

## 9. Stack et versions

## 10. Plan de realisation detaille (19 jours)

## 11. Post-MVPPage 2GMAO Saa
S MVP — Symfony 8.0 · PHP 8.4v1.2 — 12/02/

## 20261. Perimetre MVPObjectif produit
Application web de maintenance corrective multi-sites avec un flux en 5 etapes :Etape

## 1. Demande

## 2. Qualification

## 3. Intervention

## 4. Realisation

## 5. Validation
Description
Signalement + photos
Priorite / rattachement equipement
Assignation technicien + planification
Terrain : photos avant/apres + compte-rendu
Planificateur valide + cloture auto de la demande
Hors perimetre MVP
❌  Paiement / abonnements / facturation
❌  Inventaire avance, preventif, gammes, stocks, achats
❌  SLA contractuel complet (juste une notion retard)
❌  Notifications push / mobile natif (web responsive suffit)Page 3GMAO Saa
S MVP — Symfony 8.0 · PHP 8.4v1.2 — 12/02/

## 20262. Roles, pages, parcours2.1 Roles
Role
ADMINPLANIFICATEURTECHNICIENDEMANDEURDescription
Parametrage + creation comptes + acces total
Qualifie demandes, cree/assigne interventions, valide
Ne voit que ses interventions, execute, upload, CR, cloture
Cree demandes, suit ses demandes, pas d'edition apres qualification2.2 Pages MVPPublic 
• /login
• /activation/{token}Commun
• / (redirige selon role)
• /profil (optionnel)ADMIN 
• /admin/utilisateurs
• /admin/sites (CRUD)
• /admin/batiments (CRUD)
• /admin/categories-equipement
• /admin/equipements (CRUD)
• /dashboard
PLANIFICATEUR
• /demandes : liste + filtres + pagination
• /demandes/nouvelle
• /demandes/{id} : qualifier/rejeter
• /interventions : liste globale + filtres
• /interventions/nouvelle
• /interventions/{id} : planif/valider
• /reporting : KPI + filtres
TECHNICIEN
• /mes-interventions : aujourd'hui/retard/7j
• /interventions/{id} (ownership)DEMANDEUR
• /mes-demandes : liste + statut
• /demandes/nouvelle
Page 4GMAO Saa
S MVP — Symfony 8.0 · PHP 8.4v1.2 — 12/02/

## 20263. Matrice des permissions
Action
Creer une demande
Modifier demande (avant qualif.)Qualifier une demande
Rejeter (motif obligatoire)Creer/assigner intervention
Demarrer intervention
Upload photos intervention
Rediger CRCloturer intervention
Valider intervention
Voir reporting
ADMPLAN✓✓✓✓✓✓✓✓✓✓✓✓(rare)✓✓✓✓✓✓✓✓✓✓TECH
❌ 
❌ 
❌ 
❌ 
❌ ✓(own)✓(own+EC)✓(own+EC)✓(own+CR)
❌ 
❌ DEM✓✓(NOUVEAU)
❌ 
❌ 
❌ 
❌ 
❌ 
❌ 
❌ 
❌ 
❌ Implementation Symfony 8 :Couche 1 — access_control : verifie le role sur la route (dans security.yaml). Filtre global.Couche 2 — Voters : verifie le lien personnel avec la ressource. Filtre fin (anti-IDOR).Page 5GMAO Saa
S MVP — Symfony 8.0 · PHP 8.4v1.2 — 12/02/

## 20264. Workflows de statuts4.1 Demande — Statut
Demande
NOUVEAU ® A_QUALIFIER ® QUALIFIE ® PLANIFIE ® EN_COURS ® CLOTURE ® REJETEE (final)
** NOUVEAU a A_QUALIFIER : automatique a la creation.
** QUALIFIE : planificateur finalise infos (priorite, equipement).
** PLANIFIE : des qu'au moins 1 intervention est creee.
** EN_COURS : des qu'au moins 1 intervention passe EN_COURS.
** CLOTURE : automatique quand toutes interventions TERMINEE ou VALIDEE.
** REJETEE : impose motif
Rejet non vide.4.2 Intervention — Statut
Intervention
A_PLANIFIER ® PLANIFIE ® EN_COURS ® TERMINEE ® VALIDEE
** PLANIFIE : technicien + date
Planifiee renseignes.
** EN_COURS : action Demarrer (timestamp date
Debut).
** TERMINEE : action Cloturer (date
Fin), seulement si CR rempli.
** VALIDEE : action planificateur/admin.Page 6GMAO Saa
S MVP — Symfony 8.0 · PHP 8.4v1.2 — 12/02/

## 20265. Modele de donnees (MLD Doctrine)5.1 Principes
• Organisation sur chaque entite metier (filtrage multi-tenant).
•  Numeros DEM-YYYY-#### / INT-YYYY-#### generes par service.
•  Enums PHP string-backed + mapping Doctrine via enum
Type.5.2 Entites
Entite
Organisation
User
Champs clesid, nom (UNIQUE), actif, created
At, updated
Atemail (UNIQUE), roles (json), actif,Index
UNIQUE(nom)UNIQUE(email)invitation
Token, token
Expires
At.Many
To
One Organisation, Many
To
One Site?INDEX(invitation
Token)INDEX(org_id, actif)Site
Batiment
Categorie
Equipem ent
Equipement
Demande
Intervention
Photonom, adresse, contact, actif, org_idnom, site_id, actifnom, org_idnom, site_id, batiment_id?, categorie_id?, org_id, statut (Statut
Equipement)numero (UNIQUE), priorite, statut, site_id, batiment_id?, equipement_id?, demandeur_id, org_id, motif
Rejet?numero (UNIQUE), statut, date
Planifiee, date
Debut, date
Fin, duree
Minutes, compte
Rendu, demande_id, tech_id?, planif_id?, org_idtype (Type
Photo), filename, mime
Type, taille, demande_id? XOR intervention_id?INDEX(org_id, actif)INDEX(site_id, actif)INDEX(org_id)INDEX(org_id) INDEX(site_id) INDEX(statut)UNIQUE(numero) INDEX(org_id, statut)INDEX(org_id, priorite, statut)UNIQUE(numero) INDEX(org_id, statut) INDEX(tech_id, statut) INDEX(demande_id)INDEX(demande_id)INDEX(intervention_id)Page 7GMAO Saa
S MVP — Symfony 8.0 · PHP 8.4v1.2 — 12/02/

## 20266. Numerotation (DEM/INT)DEM-2026-0001INT-2026-0001
** Champ numero UNIQUE sur Demande et Intervention.
** Service Numbering
Service : annee > dernier numero > incremente > reessaie si collision.
**Post-MVP : table counters avec verrou transactionnel.

## 7. Upload photos
Parametre
Stockage
Acces
MIME autorises
Taille max
Nom disque
Valeurvar/uploads/ (hors public/)Controleur /photos/{id} + Voterimage/jpeg, image/png, image/webp5 Mo / fichier
UUID + extension

## 8. Securite MVP
• Auth : tout protege sauf /login et /activation/{token}.
• User
Checker : bloque login si actif=false.
• Token activation : bin2hex(random_bytes(32)), expiration 48h, index DB.
• Voters : Intervention
Voter sur VIEW/EDIT/DEMARRER/CLOTURER/AJOUTER_PHOTO. Toujours deny
Access
Unless
Granted().

## 9. Stack et versions
Tech
PHPSymfony
Doctrine ORMTailwind CSSVersion8.48.0.53.xv4.1Note
Requis par Symfony 8.0Standard (pas LTS). Support juil. 2026Via doctrine-bundle
Play CDN OK pour dev/MVPPage 8GMAO Saa
S MVP — Symfony 8.0 · PHP 8.4v1.2 — 12/02/2026My
SQLKnp
Paginator
Bundle
Foundry
Symfony Mailer8.0+Support CHECK constraints6.10.0Compatible Symfony 82.9.1Fixtures + tests8.0Dev : null://null
Page 9GMAO Saa
S MVP — Symfony 8.0 · PHP 8.4v1.2 — 12/02/

## 202610. Plan de realisation detaille — 19 jours
Comment lire ce plan
Objectif = ce que tu dois avoir construit a la fin du jour.Taches = actions concretes dans l'ordre. Les sous-points donnent le detail technique. Piege courant = erreur frequente a eviter (quand applicable).Validation = le test qui prouve que le jour est termine. Si ca passe, tu avances.Voir aide-memoire = reporte-toi au document complementaire pour la syntaxe.SEMAINE 1 Auth + Referentiel (sites/equipements) + Demandes
Jour 1 Initialisation + socle technique
Objectif : Avoir un projet Symfony 8 fonctionnel connecte a My
SQL, pret a coder. 
** Creer le projet Symfony 8
**composer create-project symfony/skeleton gmao "8.0.*" 
** Puis installer le pack webapp : composer require webapp
** Configurer la base de donnees
** Editer .env.local (jamais .env directement)
**DATABASE_URL="mysql://user:pass@127.0.0.1:3306/gmao" 
** Creer la base et lancer une premiere migration
**php bin/console doctrine:database:create
**php bin/console make:migration puis doctrine:migrations:migrate 
** Integrer Tailwind CSS v4 via Play CDN
** Ajouter le script CDN dans base.html.twig
**<script src="https://cdn.tailwindcss.com"></script>
** Verifier que le serveur Symfony demarre : symfony server:start
** Piege courant : .env.local ne doit JAMAIS etre commite. Ajoute-le dans .gitignore des le debut.✓ Validation : L'app affiche la page d'accueil Symfony dans le navigateur. La base existe. La migration passe.Page 10GMAO Saa
S MVP — Symfony 8.0 · PHP 8.4v1.2 — 12/02/2026Jour 2 Organisation + User + Login
Objectif : Avoir un systeme de login fonctionnel avec les 2 entites de base. 
** Creer l'entite Organisation
** Champs : nom (string 255, unique), actif (bool, default true), created
At, updated
At
**php bin/console make:entity Organisation 
** Creer l'entite User implementant User
Interface
** Champs : email (unique), password, roles (json), nom, prenom, actif 
** Relation Many
To
One vers Organisation (non nullable)
**php bin/console make:user (puis completer manuellement) 
** Configurer le systeme de securite
** Editer config/packages/security.yaml 
** Provider : entity sur User::email
** Firewall main : form_login (login_path, check_path) + logout 
** access_control : bloquer tout sauf /login
** Creer la page /login en Twig
**php bin/console make:security:form-login
** Mettre en forme avec Tailwind (formulaire centre, inputs styles) 
** Generer la migration et l'executer
** Piege courant : Ne pas oublier de hasher le mot de passe avant insertion manuelle. Utiliser : php bin/console security:hash-password✓ Validation : La page /login s'affiche. Toute autre URL redirige vers /login. Apres login (user insere manuellement en DB), redirection vers /.Jour 3 Blocage comptes inactifs + Mailer
Objectif : Empecher les comptes desactives de se connecter. Preparer le mailer pour l'invitation. 
** Implementer User
Checker
Interface
** Creer src/Security/User
Checker.php
** Methode check
Pre
Auth() : si actif === false, lancer Account
Disabled
Exception 
** Voir aide-memoire section User
Checker pour la syntaxe exacte
** Enregistrer le checker dans security.yaml
** Ajouter user_checker: App\Security\User
Checker dans le firewall 
** Configurer Symfony Mailer en mode dev
** Dans .env.local : MAILER_DSN=null://null
** Les emails seront captures par le profiler Symfony (barre de debug) 
** Tester : creer un user avec actif=false en base et tenter le login
** Piege courant : Le User
Checker doit etre dans le firewall, pas dans le provider. Erreur frequente de placement.✓ Validation : Login refuse avec message clair pour un user inactif. Login OK pour un user actif. Mailer configure.Page 11GMAO Saa
S MVP — Symfony 8.0 · PHP 8.4v1.2 — 12/02/2026Jour 4 Invitation admin (creation user sans mot de passe)Objectif : L'admin peut creer un compte qui sera active plus tard par l'utilisateur lui-meme. 
** Creer le formulaire d'invitation
** Champs : email, nom, prenom, role (Choice
Type), site (Entity
Type optionnel) 
** Le role est un select avec les 4 valeurs possibles
** Logique du controleur d'invitation
** Generer le token : bin2hex(random_bytes(32)) = 64 caracteres hex 
** Stocker token
Expires
At = now() + 48h
** Creer le user avec actif=false, password=null 
** Envoyer l'email d'invitation
** Utiliser Templated
Email avec un template Twig contenant le lien /activation/{token} 
** En dev : l'email est visible dans le profiler Symfony (icone enveloppe)
** Ajouter les champs token a l'entite User si pas deja fait 
** invitation
Token : string 64, nullable, indexe
** token
Expires
At : datetime, nullable
** Piege courant : N'utilise pas md5/sha1 pour le token. bin2hex(random_bytes(32)) est cryptographiquement sur. ✓ Validation : User cree en base avec actif=false + token stocke. Email visible dans le profiler Symfony.Jour 5 Activation / definition mot de passe
Objectif : Le nouvel utilisateur clique sur le lien, definit son mot de passe et active son compte. 
** Creer la route GET /activation/{token}
** Chercher le user par invitation
Token en base
** Si token inexistant ou expire : afficher erreur + lien vers /login 
** Si token valide : afficher le formulaire mot de passe
** Creer le formulaire de definition mot de passe 
** Champ password + confirmation (Repeated
Type) 
** Contraintes : Not
Blank + Length(min=8)
** Traitement du formulaire (POST)
** Hasher le mot de passe avec User
Password
Hasher
Interface 
** Passer actif = true
** Effacer le token : invitation
Token = null, token
Expires
At = null 
** Flush + redirection vers /login avec message flash de succes
** Tester le cycle complet de bout en bout
** Piege courant : Ne pas oublier d'effacer le token apres activation, sinon le lien reste utilisable indefiniment.✓ Validation : Cycle complet : admin invite > email genere > clic lien > mot de passe defini > login reussi.Page 12GMAO Saa
S MVP — Symfony 8.0 · PHP 8.4v1.2 — 12/02/2026Jour 6 Sites + Batiments (CRUD)Objectif : Avoir le referentiel geographique en place, editable par l'admin. 
** Creer l'entite Site
** Champs : nom, adresse, code
Postal, ville, telephone, email, actif 
** Relation Many
To
One vers Organisation
** Creer l'entite Batiment
** Champs : nom, etage (optionnel), actif 
** Relation Many
To
One vers Site
** Generer le CRUD pour chaque entite 
**php bin/console make:crud Site
** Adapter les formulaires et les templates Twig
** Filtrer par organisation dans les requetes (multi-tenant)
** Restreindre l'acces ADMIN dans access_control et dans les controleurs 
** Is
Granted('ROLE_ADMIN') sur les controleurs ou via attribut PHP 8
** Ajouter un filtre actif/inactif dans les listes
** Piege courant : Toujours filtrer par organisation_id dans les requetes. Ne jamais afficher les donnees d'une autre organisation. ✓ Validation : 3 sites et 6 batiments crees via l'interface. Les batiments sont bien lies a leurs sites.Jour 7 Categories + Equipements
Objectif : Avoir le referentiel equipement fonctionnel avec enum de statut et filtres. 
** Creer l'enum Statut
Equipement
** Valeurs : EN_SERVICE, HORS_SERVICE, EN_PANNE 
** Fichier : src/Enum/Statut
Equipement.php
** Voir aide-memoire section Enums pour la syntaxe 
** Creer l'entite Categorie
Equipement
** Champs : nom, description (optionnel), organisation_id 
** Creer l'entite Equipement
** Champs : nom, marque?, modele?, numero
Serie?, statut (enum), actif
** Relations : Many
To
One Site, Many
To
One Batiment (nullable), Many
To
One Categorie (nullable), Many
To
One Organisation 
** CRUD equipement avec filtres combines
** Filtres GET : par site (Entity
Type), par categorie (Entity
Type), par statut (Enum
Type)
** Les filtres doivent se cumuler (si site=A et statut=EN_PANNE, on voit les equipements en panne du site A)
**Piege courant : Pour le mapping enum en Doctrine, utiliser #[ORM\Column(enum
Type: Statut
Equipement::class)] — pas 'type: string'. ✓ Validation : 10 equipements repartis. Les 3 filtres fonctionnent ensemble sans conflit.Jour 8 Enums metier + structure entite Demande
Objectif : Avoir toutes les enums du projet et l'entite Demande prete (sans UI complete). 
** Creer les enums restantes
** Priorite : P1_URGENTE, P2_HAUTE, P3_NORMALE, P4_BASSE
** Statut
Demande : NOUVEAU, A_QUALIFIER, QUALIFIE, PLANIFIE, EN_COURS, CLOTURE, REJETEE 
** Statut
Intervention : A_PLANIFIER, PLANIFIE, EN_COURS, TERMINEE, VALIDEE
** Type
Photo : SIGNALEMENT, AVANT, APRES, COMPLEMENT 
** Creer l'entite Demande
** Champs : numero (unique), titre, description, priorite (enum), statut (enum), motif
Rejet (nullable) 
** Champs temporels : created
At, updated
At
** Relations : Many
To
One Site, Many
To
One Batiment (nullable), Many
To
One Equipement (nullable), Many
To
One User (demandeur), Many
To
One Organisation
** Generer et executer la migration
** Verifier dans la migration SQL que les colonnes enum sont bien VARCHAR, pas INT
** Piege courant : Verifie toujours la migration generee AVANT de l'executer. Doctrine peut parfois mal interpreter un changement.✓ Validation : Migrations OK. Un SELECT sur la table demande montre les bonnes colonnes. Enums mappees correctement.Page 13GMAO Saa
S MVP — Symfony 8.0 · PHP 8.4v1.2 — 12/02/2026SEMAINE 2 Demandes completes + Interventions (workflow) + Photos
Jour 9 CRUD Demande + numerotation DEMObjectif : Pouvoir creer des demandes avec un numero unique auto-genere et un statut initial automatique. 
** Creer le service Numbering
Service
** Methode generate
Numero(string $prefix) : string
** Logique : recupere l'annee, cherche le dernier numero DEM-{annee}-*, incremente 
** Retourne DEM-2026-0001, DEM-2026-0002, etc.
** Gerer le cas 1er janvier (reset compteur) 
** Creer le formulaire de creation demande
** Champs : titre, description, site (Entity
Type), batiment (Entity
Type optionnel), equipement (Entity
Type optionnel), priorite (Enum
Type)
** Accessible aux roles : DEMANDEUR, PLANIFICATEUR, ADMIN 
** Logique du controleur
** A la creation : statut = A_QUALIFIER automatiquement (sauter NOUVEAU ou le garder 1 seconde) 
** Appeler Numbering
Service pour generer le numero
** Setter le demandeur = user connecte, organisation = user.organisation
** Afficher la liste des demandes avec numero, titre, priorite, statut, date
** Piege courant : Le numero doit etre genere DANS le service, pas dans l'entite. L'entite ne doit pas connaitre la logique de numerotation.✓ Validation : 3 demandes creees avec numeros sequentiels uniques. Statut initial correct. Numero visible dans la liste.Jour 10 Photos demande (signalement)Objectif : Permettre d'ajouter des photos lors de la creation ou edition d'une demande. 
** Creer l'entite Photo
** Champs : type (Type
Photo), filename (string), original
Name (string), mime
Type, taille (int), created
At
** Relations : Many
To
One Demande (nullable), Many
To
One Intervention (nullable), Many
To
One User (uploade
Par) 
** Creer le service File
Upload
Service
** Methode upload(Uploaded
File $file, string $directory) : string (retourne le nouveau nom) 
** Valider MIME (jpeg/png/webp) et taille (max 5Mo)
** Renommer : uniqid() + extension originale
** Deplacer dans le dossier configure (var/uploads/photos/) 
** Integrer dans le formulaire demande
** Ajouter un champ File
Type avec multiple=true
** Contraintes : File(max
Size: '5M', mime
Types: ['image/jpeg', 'image/png', 'image/webp']) 
** Afficher les photos sur la page detail demande
** Creer un controleur /photos/{id} qui verifie les droits et renvoie Binary
File
Response 
** Afficher en galerie simple (miniatures cliquables)
** Piege courant : JAMAIS stocker les uploads dans public/. Toujours dans var/uploads/ et servir via un controleur protege. ✓ Validation : Photos uploadees et visibles sur le detail. Fichiers dans var/uploads/. Acces protege.Page 14GMAO Saa
S MVP — Symfony 8.0 · PHP 8.4v1.2 — 12/02/2026Jour 11 Liste demandes (filtres + pagination)Objectif : Avoir une liste exploitable avec filtres cumulables et pagination. 
** Creer la methode find
By
Filters() dans Demande
Repository
** Parametres : ?site, ?priorite, ?statut, ?search (titre/description), ?organisation
** Construire le Query
Builder dynamiquement (ajouter les WHERE seulement si le filtre est renseigne) 
** Toujours filtrer par organisation (multi-tenant)
** Installer et configurer Knp
Paginator
Bundle 
**composer require knplabs/knp-paginator-bundle 
** Passer le Query
Builder (pas le resultat) au paginator
**$pagination = $paginator->paginate($qb, $request->query->get
Int('page', 1), 20) 
** Creer le formulaire de filtres (methode GET)
** Formulaire non lie a une entite (standalone)
** Selects : site, priorite, statut + champ texte recherche
** Methode GET pour que les filtres soient dans l'URL (partageables, paginables) 
** Afficher la pagination sous la liste (template Tailwind)
** Piege courant : Passer le Query
Builder au paginator, PAS le get
Result(). Sinon la pagination ne fonctionnera pas. ✓ Validation : Les filtres se cumulent. La pagination reste coherente apres filtrage. 20 resultats par page.Jour 12 Entite Intervention + numerotation INT + CRUDObjectif : Pouvoir creer des interventions liees a une demande avec transition de statut automatique. 
** Creer l'entite Intervention
** Champs : numero (unique), statut (enum), date
Planifiee (?), date
Debut (?), date
Fin (?), duree
Minutes (?), compte
Rendu (text ?)
** Relations : Many
To
One Demande, Many
To
One User (technicien, nullable), Many
To
One User (planificateur, nullable), Many
To
One Organisation
** Etendre Numbering
Service pour les interventions 
** Meme logique que DEM mais avec prefixe INT
** Tu peux factoriser : generate
Numero('DEM') / generate
Numero('INT') 
** CRUD intervention
** Creation uniquement depuis une demande (bouton sur /demandes/{id})
** Formulaire : technicien (Entity
Type filtre par role TECHNICIEN), date
Planifiee 
** A la creation : statut = A_PLANIFIER (ou PLANIFIE si tech + date renseignes)
** Cascade vers la demande
** Quand une intervention est creee : passer la demande en PLANIFIE 
** Implementer dans le service, pas dans le controleur
** Piege courant : Ne pas creer l'intervention si la demande est CLOTURE ou REJETEE. Verifier le statut avant.✓ Validation : Interventions creees et liees. Numeros INT uniques. Demande passe automatiquement en PLANIFIE.Page 15GMAO Saa
S MVP — Symfony 8.0 · PHP 8.4v1.2 — 12/02/2026Jour 13 Workflow intervention (demarrer / cloturer)Objectif : Implementer le cycle de vie complet d'une intervention avec toutes les regles metier. 
** Action Demarrer (POST)
** Verifier statut actuel = PLANIFIE
** Passer statut = EN_COURS, date
Debut = new Date
Time()
** Cascade : si c'est la 1ere intervention EN_COURS, passer la demande en EN_COURS 
** Action Cloturer (POST)
** Verifier statut actuel = EN_COURS
** Verifier que compte
Rendu n'est PAS vide (sinon erreur) 
** Passer statut = TERMINEE, date
Fin = new Date
Time() 
** Calculer duree
Minutes = diff entre date
Debut et date
Fin
** Cascade vers la demande a la cloture
** Recuperer toutes les interventions de la demande
** Si TOUTES sont TERMINEE ou VALIDEE : passer la demande en CLOTURE automatiquement 
** Sinon : ne rien faire (il reste des interventions en cours)
** Tester les cas d'erreur
** Tentative de cloture sans CR : doit echouer avec message explicite
** Tentative de demarrer une intervention deja EN_COURS : doit echouer
** Piege courant : La cascade demande doit verifier TOUTES les interventions, pas seulement celle qu'on vient de cloturer.✓ Validation : Cas nominal OK (demarrer > CR > cloturer > demande CLOTURE). Cas erreur (cloture sans CR) gere.Jour 14 Photos intervention (AVANT / APRES / COMPLEMENT)Objectif : Le technicien peut ajouter des photos typees pendant son intervention. 
** Reutiliser File
Upload
Service existant
** Meme service, meme validation, meme dossier de stockage 
** Formulaire d'upload sur la page intervention
** Champ File
Type multiple + champ Choice
Type pour le type (AVANT/APRES/COMPLEMENT) 
** Afficher le formulaire uniquement si intervention EN_COURS
** Regles d'acces strictes
** Upload autorise SEULEMENT si : intervention EN_COURS ET user = technicien assigne 
** Utiliser le Voter (ou verification manuelle en attendant J15)
** Affichage galerie par type
** Sur la page detail intervention : grouper les photos par type (3 sections) 
** Miniatures cliquables renvoyant vers /photos/{id}
** Piege courant : Verifier que le type de photo est bien enregistre (AVANT vs APRES). C'est une valeur metier importante pour le reporting.✓ Validation : Le technicien ajoute des photos avant/apres. Elles sont groupees par type et protegees.Page 16GMAO Saa
S MVP — Symfony 8.0 · PHP 8.4v1.2 — 12/02/2026SEMAINE 3 Permissions, Dashboard, Reporting, Demo
Jour 15 Voters (ownership + tenant)Objectif : Proteger chaque ressource individuellement. C'est le verrou anti-IDOR. 
** Creer Intervention
Voter
** Attributs supportes : VIEW, EDIT, DEMARRER, CLOTURER, AJOUTER_PHOTO
** Logique : si ADMIN/PLANIF, autoriser. Si TECHNICIEN, verifier que intervention.technicien === user connecte 
** Verifier aussi que l'intervention appartient a la meme organisation que le user
** Voir aide-memoire section Voters pour la structure complete 
** Integrer dans TOUS les controleurs d'intervention
** Remplacer les verifications manuelles par deny
Access
Unless
Granted('VIEW', $intervention) 
** Faire de meme pour chaque action : DEMARRER, CLOTURER, AJOUTER_PHOTO
** Tester avec 2 techniciens
** Tech1 cree une intervention, Tech2 essaie d'y acceder > 403 
** Tech1 accede a sa propre intervention > 200
** Admin accede a toutes les interventions > 200 
** Etendre aux photos
** Upload interdit si le user n'est pas owner de l'intervention liee
** Piege courant : Ne jamais verifier les droits UNIQUEMENT cote Twig (masquer un bouton). Toujours verifier cote serveur dans le controleur.✓ Validation : Tech2 sur l'intervention de Tech1 = erreur 

## 403. Admin partout = OK. Anti-IDOR valide.Jour 16 Dashboard adapte par role
Objectif : Chaque role atterrit sur une page d'accueil utile avec les bonnes informations. 
** Controleur Dashboard
Controller
** Route / : detecter le role du user et rediriger ou afficher le bon contenu 
** Si TECHNICIEN : rediriger vers /mes-interventions
** Si DEMANDEUR : rediriger vers /mes-demandes
** Si PLANIFICATEUR ou ADMIN : afficher le dashboard complet 
** Widgets dashboard planificateur/admin
** Widget 1 : nombre de demandes P1 (urgentes) ouvertes 
** Widget 2 : nombre de demandes a qualifier
** Widget 3 : interventions en retard (date
Planifiee < aujourd'hui ET statut != TERMINEE/VALIDEE) 
** Widget 4 : interventions du jour
** Requetes Doctrine pour chaque widget
** Creer des methodes dediees dans les repositories : count
P1Ouvertes(), count
AQualifier(), etc. 
** Filtrer par organisation a chaque fois
** Page /mes-interventions pour le technicien
** 3 sections : Aujourd'hui, En retard, 7 prochains jours 
** Filtrer par technicien_id = user connecte
** Piege courant : Le widget 'en retard' doit comparer date
Planifiee avec la date du jour, PAS avec created
At.✓ Validation : Compteurs coherents avec les donnees. Navigation adaptee. Tech voit ses interventions, admin voit tout.Page 17GMAO Saa
S MVP — Symfony 8.0 · PHP 8.4v1.2 — 12/02/2026Jour 17 Reporting (4 KPI minimum)Objectif : Fournir des indicateurs de pilotage au planificateur/admin. 
** Creer la page /reporting
** Acces : PLANIFICATEUR + ADMIN uniquement
** Filtres en haut : site (select) + periode (date debut / date fin) 
** KPI 1 : Demandes par statut
** COUNT groupe par statut, filtre par site + periode 
** Affichage : tableau simple ou compteurs colores
** KPI 2 : Delai moyen de traitement
** Moyenne de (date
Cloture - date
Creation) pour les demandes CLOTURE 
** Afficher en heures ou en jours selon la valeur
** KPI 3 : Interventions par technicien
** COUNT groupe par technicien, filtre par periode 
** Permet de voir la charge de travail de chacun
** KPI 4 : Demandes par site et priorite
** Tableau croise : lignes = sites, colonnes = priorites 
** Permet d'identifier les sites les plus problematiques
** Piege courant : Utiliser des requetes DQL ou Query
Builder dediees dans les repositories. Ne pas calculer les stats en PHP sur des collections.✓ Validation : Les 4 KPI affichent des chiffres coherents avec le dataset de fixtures.Jour 18 Fixtures Foundry + READMEObjectif : Avoir un jeu de donnees demo complet, reproductible en une commande. 
** Installer Foundry v2.9
**composer require --dev zenstruck/foundry 
** Creer les factories pour chaque entite
** Organisation
Factory, User
Factory, Site
Factory, Batiment
Factory, etc. 
**php bin/console make:factory pour chaque entite
** Definir les defaults() avec des valeurs Faker realistes 
** Creer le Data
Fixtures principal
** 1 organisation, 3 sites, 6 batiments, 5 categories, 10 equipements
** 5 users (1 admin, 1 planif, 2 techniciens, 1 demandeur) — mots de passe connus 
** 15 demandes a differents statuts, 20 interventions
** Quelques photos de demo (placeholder images) 
** Ecrire le README.md
** Instructions d'installation (clone, composer install, .env.local, migrations, fixtures) 
** Comptes de demo avec emails et mots de passe
** Parcours de test : scenario nominal a derouler
** Tester : php bin/console doctrine:database:drop --force && doctrine:database:create && doctrine:migrations:migrate && doctrine:fixtures:load
** Piege courant : Les mots de passe dans les fixtures doivent etre hashes. Injecter User
Password
Hasher
Interface dans la factory. ✓ Validation : Reset complet de la base > fixtures chargees > l'app fonctionne avec des donnees coherentes.Page 18GMAO Saa
S MVP — Symfony 8.0 · PHP 8.4v1.2 — 12/02/2026Jour 19 Scenario demo + durcissement final
Objectif : Avoir un MVP propre, teste de bout en bout, pret a presenter. 
** Derouler le scenario nominal complet
** 

## 1. Admin invite un demandeur > activation > login 
** 

## 2. Demandeur cree une demande avec photos
** 

## 3. Planificateur qualifie la demande, cree une intervention, assigne un technicien 
** 

## 4. Technicien demarre, ajoute photos avant/apres, redige CR, cloture
** 

## 5. Planificateur valide l'intervention
** 

## 6. Verifier : demande auto-cloturee, KPI mis a jour dans reporting 
** Verifications securite
** Toutes les routes admin inaccessibles en TECHNICIEN/DEMANDEUR 
** Voter : technicien B ne voit pas les interventions de technicien A
** Token d'activation expire : acces refuse 
** Performance basique
** Ouvrir le profiler Symfony sur les pages listes
** Verifier qu'il n'y a pas de requetes N+1 flagrantes
** Si N+1 : ajouter des JOIN FETCH dans les requetes Doctrine 
** Tag Git final
**git add -A && git commit -m "MVP complete" && git tag v1.0.0-mvp✓ Validation : Scenario repetable sans bug. Securite validee. Tag Git v1.0.0-mvp cree.Page 19GMAO Saa
S MVP — Symfony 8.0 · PHP 8.4v1.2 — 12/02/

## 202611. Post-MVP — 5 evolutions les plus rentables#Evolution1Table counters2Event
Log3Mailpit Docker4SLA retard5Multi-tenant reel
Description
Numerotation transactionnelle (verrou DB)Audit des changements de statuts (par qui, quand)Email catcher en dev au lieu de null://null
Base sur priorite (P1=4h, P2=24h...) + alertes
Plusieurs organisations + super-admin
Resume
Ce document contient : pages MVP par role, matrice permissions (access_control + Voters), workflows verrouilles, MLD Doctrine (index + contraintes), numerotation DEM/INT, upload securise, et un plan jour par jour detaille (19 jours) avec objectif / taches detaillees / pieges courants / validation.Stack : Symfony 8.0.5 · PHP 8.4 · Tailwind CSS v4.1 · Knp
Paginator 6.10 · Foundry 2.9.1 · My
SQL 8+Document complementaire : Aide-memoire technique (syntaxes Symfony, Doctrine, Security, Upload).Page 20
