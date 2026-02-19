GMAO SaaS MVP — Symfony 8.0 · PHP 8.4	v1.1 — 11/02/2026










GMAO SaaS Cahier des Charges MVP




Stack technique
Symfony 8.0.5 · PHP 8.4 · Doctrine ORM · Twig · Tailwind CSS v4 · MySQL 8+



Attention : Symfony 8.0 n'est pas LTS (support jusqu'a juillet 2026). Le LTS actuel est 7.4 (securite jusqu'a nov. 2029). Prochain LTS : 8.4 (nov. 2027).



Version 1.1 — 11 fevrier 2026 Duree estimee : 19 jours · 3 semaines


































Page 1
GMAO SaaS MVP — Symfony 8.0 · PHP 8.4	v1.1 — 11/02/2026


Table des matieres


1. Perimetre MVP

2. Roles, pages, parcours

3. Matrice des permissions

4. Workflows de statuts

5. Modele de donnees (MLD Doctrine)

6. Numerotation (DEM/INT)

7. Upload photos

8. Securite MVP

9. Stack et versions

10. Plan de realisation (19 jours)

11. Post-MVP











































Page 2
GMAO SaaS MVP — Symfony 8.0 · PHP 8.4	v1.1 — 11/02/2026


1. Perimetre MVP



Objectif produit

Application web de maintenance corrective multi-sites avec un flux simple en 5 etapes :


Etape

1. Demande

2. Qualification

3. Intervention

4. Realisation

5. Validation

Description

Signalement + photos

Priorite / rattachement equipement

Assignation technicien + planification

Terrain : photos avant/apres + compte-rendu

Planificateur valide + cloture auto de la demande




Hors perimetre MVP (a garder pour apres)

✗ Paiement / abonnements / facturation
✗ Inventaire avance, preventif, gammes, stocks, achats
✗ SLA contractuel complet (juste une notion retard basee sur date planifiee) ✗ Notifications push / mobile natif (web responsive suffit)

































Page 3
GMAO SaaS MVP — Symfony 8.0 · PHP 8.4	v1.1 — 11/02/2026


2. Roles, pages, parcours



2.1 Roles


Role

ADMIN

PLANIFICATEUR

TECHNICIEN

DEMANDEUR

Description

Parametrage + creation comptes + acces total

Qualifie demandes, cree/assigne interventions, valide

Ne voit que ses interventions, execute, upload, CR, cloture

Cree demandes, suit ses demandes, pas d'edition apres qualification




2.2 Pages MVP (liste minimale)

Public /login
/activation/{token} (definition mot de passe)

Commun (connecte)
/ (redirige selon role)

/profil (optionnel MVP)

ADMIN
/admin/utilisateurs : liste + inviter + renvoyer + desactiver

/admin/sites (CRUD)

/admin/batiments (CRUD)

/admin/categories-equipement (CRUD leger)

/admin/equipements (CRUD)

/dashboard (meme vue que planificateur)

PLANIFICATEUR
/demandes : liste + filtres + pagination

/demandes/nouvelle

/demandes/{id} : qualifier / rejeter / creer intervention

/interventions : liste globale + filtres

/interventions/nouvelle : creation depuis une demande

/interventions/{id} : modifier planif/tech, valider, voir CR + photos

/reporting : tableaux KPI + filtres periode/site

TECHNICIEN
/mes-interventions : Aujourd'hui / En retard / 7 prochains jours

/interventions/{id} (ownership) : demarrer, upload, CR, cloturer

DEMANDEUR
/mes-demandes : liste + statut

/demandes/nouvelle






Page 4
GMAO SaaS MVP — Symfony 8.0 · PHP 8.4	v1.1 — 11/02/2026


3. Matrice des permissions


Action	ADM	PLAN	TECH	DEM


Creer une demande

Modifier demande (avant qualif.)

Qualifier une demande

Rejeter (motif obligatoire)

Creer/assigner intervention

✗

✗	(NOUVEAU)

✗	✗

✗	✗

✗	✗


Demarrer intervention	(rare)	(own)	✗


Upload photos intervention

Rediger CR

Cloturer intervention

Valider intervention

Voir reporting

(own+EC)	✗

(own+EC)	✗

(own+CR)	✗

✗	✗

✗	✗




Implementation Symfony 8 :

Couche  1  —  access_control  :  verifie  le  role  sur  la  route  (dans  security.yaml).  Exemple  :  seuls  ADMIN  et PLANIFICATEUR accedent a /reporting.
Couche 2 — Voters : verifie le lien personnel avec la ressource. Exemple : InterventionVoter verifie que le technicien est bien assigne a cette intervention avant d'autoriser l'action.
Doc : https://symfony.com/doc/8.0/security/voters.html



























Page 5
GMAO SaaS MVP — Symfony 8.0 · PHP 8.4	v1.1 — 11/02/2026


4. Workflows de statuts



4.1 Demande — StatutDemande


NOUVEAU ®  A_QUALIFIER ®  QUALIFIE ®  PLANIFIE ®  EN_COURS ®  CLOTURE ®  REJETEE (final, a tout moment par planificateur/admin)


NOUVEAU a A_QUALIFIER : automatique a la creation.

QUALIFIE : planificateur finalise infos (priorite, equipement optionnel).

PLANIFIE : des qu'au moins 1 intervention est creee.

EN_COURS : des qu'au moins 1 intervention passe EN_COURS.

CLOTURE : automatique quand toutes interventions sont TERMINEE ou VALIDEE.

REJETEE : impose motifRejet non vide.



4.2 Intervention — StatutIntervention


A_PLANIFIER ®  PLANIFIE ®  EN_COURS ®  TERMINEE ®  VALIDEE


PLANIFIE : technicien + datePlanifiee renseignes.

EN_COURS : action Demarrer (timestamp dateDebut).

TERMINEE : action Cloturer (timestamp dateFin), seulement si CR rempli.

VALIDEE : action planificateur/admin (utile pour reporting).






























Page 6
GMAO SaaS MVP — Symfony 8.0 · PHP 8.4	v1.1 — 11/02/2026


5. Modele de donnees (MLD Doctrine)



5.1 Principes structurels

Organisation sur chaque entite metier (filtrage multi-tenant).

Numeros DEM-YYYY-#### / INT-YYYY-#### generes par service.
Enums PHP string-backed + mapping Doctrine via enumType.
Formulaires : EnumType pour choisir les enums cote UI.



5.2 Entites MVP


Entite

Organisation

Champs cles

id, nom (UNIQUE), actif, createdAt, updatedAt

Index

UNIQUE(nom)



User



Site

Batiment

CategorieEquipem ent

Equipement



Demande



Intervention




Photo

email (UNIQUE), roles (json), actif, invitationToken, tokenExpiresAt. Relations : ManyToOne Organisation, ManyToOne Site (nullable)

nom, adresse, contact, actif, organisation_id

nom, site_id, actif

nom, organisation_id


nom, site_id, batiment_id?, categorie_id?, organisation_id, statut (StatutEquipement)


numero (UNIQUE), priorite, statut, site_id, batiment_id?, equipement_id?, demandeur_id, organisation_id, motifRejet?

numero (UNIQUE), statut, datePlanifiee, dateDebut, dateFin, dureeMinutes, compteRendu, demande_id, technicien_id?, planificateur_id?, organisation_id

type (TypePhoto), filename, mimeType, taille, demande_id? XOR intervention_id?, uploadePar_id


UNIQUE(email) INDEX(invitationToken) INDEX(organisation_id, actif)

INDEX(organisation_id, actif)

INDEX(site_id, actif)

INDEX(organisation_id)


INDEX(organisation_id) INDEX(site_id) INDEX(statut)

UNIQUE(numero) INDEX(org_id, statut)
INDEX(org_id, priorite, statut) INDEX(site_id, createdAt)

UNIQUE(numero) INDEX(org_id, statut)
INDEX(tech_id, statut, datePlan) INDEX(demande_id)

INDEX(demande_id)
INDEX(intervention_id)


Contrainte XOR Photo (demande_id OU intervention_id) : validation dans le code (Form/Service). Option MySQL 8+ : CHECK ((demande_id IS NULL) <> (intervention_id IS NULL))















Page 7
GMAO SaaS MVP — Symfony 8.0 · PHP 8.4	v1.1 — 11/02/2026


6. Numerotation (DEM/INT)



Format


DEM-2026-0001	INT-2026-0001



Strategie fiable

Table Demande / Intervention : champ numero UNIQUE.
Service NumberingService :
1) Recupere l'annee courante Y

2) Cherche le dernier numero commencant par DEM-Y-

3) Incremente

4) Reessaie si collision (rare mais possible en concurrence)

Post-MVP : table counters (organisation_id, type, year, seq) avec verrou transactionnel.





7. Upload photos



Stockage

Fichiers dans var/uploads/... (hors public/).
Exposition via controleur /photos/{id} qui verifie droits (Voter) et renvoie un BinaryFileResponse.



Regles serveur


Parametre

MIME autorises

Taille max

Nom disque

Methode

Valeur

image/jpeg, image/png, image/webp

5 Mo / fichier

UUID / uniqid + extension

UploadedFile::move() via service dedie


Doc : https://symfony.com/doc/8.0/controller/upload_file.html











Page 8
GMAO SaaS MVP — Symfony 8.0 · PHP 8.4	v1.1 — 11/02/2026


8. Securite MVP



8.1 Acces / Auth

Tout protege sauf /login et /activation/{token}.
Compte inactif bloque via UserCheckerInterface.


8.2 Token d'activation

Generation : bin2hex(random_bytes(32)) (64 hex).
Expiration : now + 48h.

Index DB sur invitationToken.


8.3 Ownership technicien (anti-IDOR)

Voter InterventionVoter sur VIEW / EDIT / DEMARRER / CLOTURER / AJOUTER_PHOTO.
Toujours passer par denyAccessUnlessGranted($attr, $intervention).
Doc : https://symfony.com/doc/8.0/security/voters.html





9. Stack et versions (validees)



Technologie

PHP

Symfony

Doctrine ORM

Tailwind CSS

MySQL

KnpPaginatorBundle

Foundry

Symfony Mailer

Version

8.4

8.0.5

3.x

v4.1

8.0+

6.10.0

2.9.1

8.0

Note

Requis par Symfony 8.0

Standard (pas LTS). Support juil. 2026

Via doctrine-bundle

Play CDN OK pour dev/MVP

Support CHECK constraints

Compatible Symfony 8

Fixtures + tests

Dev : null://null

















Page 9
GMAO SaaS MVP — Symfony 8.0 · PHP 8.4	v1.1 — 11/02/2026


10. Plan de realisation — 19 jours



Comment lire ce plan
Chaque jour contient 3 elements :
Objectif = ce que tu dois avoir compris/construit a la fin du jour. Taches = les actions concretes a realiser dans l'ordre.
Validation = le test qui prouve que le jour est termine. Si cette validation passe, tu avances. Sinon, tu restes sur ce jour.



SEMAINE 1  Auth + Referentiel (sites/equipements) + Demandes


Jour 1  Initialisation + socle technique

Objectif : Avoir un projet Symfony 8 fonctionnel connecte a MySQL, pret a coder.
Creer le projet : symfony new gmao --webapp Configurer .env.local avec les identifiants MySQL
Creer la base de donnees : php bin/console doctrine:database:create Lancer la premiere migration (vide) pour verifier la connexion
Verifier que le serveur Symfony demarre sans erreur
Validation : L'app demarre dans le navigateur + la base est connectee + la migration passe.


Jour 2  Organisation + User + Login

Objectif : Avoir un systeme de login fonctionnel avec 2 entites de base.
Creer l'entite Organisation (nom unique, actif)
Creer l'entite User (email, roles json, actif) liee a Organisation Configurer le firewall dans security.yaml (form_login + logout) Configurer access_control (routes protegees par role)
Creer la page login en Twig avec Tailwind CSS v4
Validation : La page login s'affiche. Toute route protegee redirige vers /login si non connecte.


Jour 3  Blocage comptes inactifs + Mailer

Objectif : Empecher les comptes desactives de se connecter.
Implementer UserCheckerInterface : verifier champ actif avant login Enregistrer le UserChecker dans security.yaml
Configurer le Mailer en dev avec null://null (pas d'envoi reel)
Validation : Un user avec actif=false est refuse au login avec un message clair.


Jour 4  Invitation admin (creation user sans mot de passe)

Objectif : Permettre a l'admin de creer un compte qui sera active par l'utilisateur.
Creer le formulaire d'invitation : email, nom, prenom, role, site Generer le token : bin2hex(random_bytes(32)) + expiration 48h Stocker le user en base avec actif=false + token
Envoyer l'email d'invitation avec TemplatedEmail (Mailer)
Validation : User cree en base inactif, token stocke, email genere (visible dans le profiler).








Page 10
GMAO SaaS MVP — Symfony 8.0 · PHP 8.4	v1.1 — 11/02/2026



Jour 5  Activation / definition mot de passe

Objectif : Permettre au nouvel utilisateur de definir son mot de passe et activer son compte.
Creer la route /activation/{token}
Verifier que le token existe et n'est pas expire Afficher le formulaire mot de passe + confirmation
Hasher le mot de passe, passer actif=true, effacer le token
Validation : Cycle complet : invitation > email > clic lien > mot de passe > login reussi.


Jour 6  Sites + Batiments (CRUD)

Objectif : Avoir le referentiel geographique de base en place.
Creer les entites Site et Batiment avec leurs relations Implementer le CRUD complet pour chaque entite Restreindre l'acces aux roles ADMIN
Ajouter des filtres sur le champ actif dans les listes
Validation : 3 sites et 6 batiments crees via l'interface. Les relations sont visibles.


Jour 7  Categories + Equipements

Objectif : Avoir le referentiel equipement fonctionnel avec filtres.
Creer les entites CategorieEquipement et Equipement
Implementer l'enum StatutEquipement (EN_SERVICE, HORS_SERVICE, EN_PANNE) CRUD equipement avec filtres par site, categorie, statut
Validation : 10 equipements repartis sur les sites. Les 3 filtres fonctionnent ensemble.


Jour 8  Enums + structure Demande

Objectif : Avoir toutes les enums metier et l'entite Demande prete (sans UI complete).
Creer les enums : Priorite, StatutDemande, StatutIntervention, TypePhoto Creer l'entite Demande avec toutes ses relations
Generer et executer la migration Verifier le mapping Doctrine enumType
Validation : Migrations OK. Les enums sont correctement mappees en base (colonnes VARCHAR).

























Page 11
GMAO SaaS MVP — Symfony 8.0 · PHP 8.4	v1.1 — 11/02/2026



SEMAINE 2  Demandes completes + Interventions (workflow) + Photos


Jour 9  CRUD Demande + numerotation DEM

Objectif : Pouvoir creer des demandes avec un numero unique auto-genere.
Creer le formulaire de creation demande (accessible DEMANDEUR/ADMIN/PLANIF) Implementer NumberingService : genere DEM-YYYY-####
Statut automatique : NOUVEAU > A_QUALIFIER a la creation Afficher la liste des demandes (sans filtres avances pour l'instant)
Validation : 3 demandes creees avec numeros sequentiels uniques (DEM-2026-0001, 0002, 0003).


Jour 10  Photos demande (signalement)

Objectif : Permettre d'ajouter des photos lors de la creation d'une demande.
Creer l'entite Photo (type, filename, mimeType, taille)
Creer le service d'upload (validation MIME + taille + nom unique) Integrer un champ multi-fichiers dans le formulaire demande Afficher les photos sur la page de detail de la demande
Validation : Photos uploadees visibles sur le detail de la demande. Fichiers stockes dans var/uploads/.


Jour 11  Liste demandes (filtres + pagination)

Objectif : Avoir une liste de demandes exploitable avec filtres cumulables.
Creer la methode findByFilters() dans le repository (site, priorite, statut, recherche) Integrer KnpPaginatorBundle v6.10 pour la pagination
Afficher les filtres dans l'interface (formulaire GET) Tester les combinaisons de filtres
Validation : Les filtres se cumulent correctement. La pagination reste stable apres filtrage.


Jour 12  Entite Intervention + numerotation INT + CRUD

Objectif : Pouvoir creer des interventions liees a une demande.
Creer l'entite Intervention avec toutes ses relations Etendre NumberingService pour INT-YYYY-####
CRUD intervention : creation uniquement depuis une demande non cloturee Passage auto de la demande en PLANIFIE quand intervention creee
Validation : Interventions creees liees a leurs demandes. Numeros INT uniques. Demande passe en PLANIFIE.


Jour 13  Workflow intervention (demarrer / cloturer)

Objectif : Implementer le cycle de vie complet d'une intervention.
Action Demarrer : statut EN_COURS + dateDebut = now()
Action Cloturer : statut TERMINEE + dateFin = now() + calcul dureeMinutes Bloquer la cloture si le compte-rendu est vide
Cascade vers demande : si toutes interventions TERMINEE/VALIDEE > CLOTURE demande
Validation : Cas nominal OK. Tentative de cloture sans CR > erreur. Cascade demande fonctionne.










Page 12
GMAO SaaS MVP — Symfony 8.0 · PHP 8.4	v1.1 — 11/02/2026



Jour 14  Photos intervention (AVANT / APRES / COMPLEMENT)

Objectif : Permettre au technicien d'ajouter des photos pendant l'intervention.
Reutiliser le service d'upload existant
Autoriser l'upload uniquement si intervention EN_COURS + ownership Typer les photos (AVANT, APRES, COMPLEMENT) via l'enum TypePhoto Afficher les photos groupees par type sur le detail intervention
Validation : Le technicien ajoute des photos avant/apres. Elles sont visibles et protegees par le Voter.






















































Page 13
GMAO SaaS MVP — Symfony 8.0 · PHP 8.4	v1.1 — 11/02/2026



SEMAINE 3  Permissions, Dashboard, Reporting, Demo


Jour 15  Voters (ownership + tenant)

Objectif : Proteger chaque ressource individuellement (anti-IDOR).
Creer InterventionVoter : verifier ownership technicien + meme organisation Integrer denyAccessUnlessGranted() dans tous les controleurs d'intervention Tester avec 2 techniciens : chacun ne voit que ses propres interventions Etendre aux photos : upload interdit si pas owner de l'intervention
Validation : Tech2 tente d'acceder a l'intervention de Tech1 > erreur 403. Anti-IDOR OK.


Jour 16  Dashboard (adapte par role)

Objectif : Chaque role voit une page d'accueil adaptee a son travail.
PLANIFICATEUR / ADMIN : widgets (P1 ouvertes, a qualifier, en retard, interventions du jour) TECHNICIEN : redirection vers /mes-interventions (aujourd'hui, en retard, 7 jours) DEMANDEUR : redirection vers /mes-demandes
Creer les requetes Doctrine pour chaque widget (compteurs)
Validation : Compteurs coherents avec les donnees. Navigation adaptee selon le role connecte.


Jour 17  Reporting (4 KPI minimum)

Objectif : Fournir des indicateurs de pilotage au planificateur.
Creer la page /reporting avec filtres site + periode KPI 1 : nombre de demandes par statut
KPI 2 : delai moyen de traitement (creation > cloture) KPI 3 : interventions par technicien
KPI 4 : demandes par site et par priorite Requetes dediees dans les repositories
Validation : Les chiffres sont coherents avec le dataset de fixtures.


Jour 18  Fixtures Foundry + README

Objectif : Avoir un jeu de donnees demo complet et reproductible.
Installer et configurer Foundry v2.9
Creer les factories pour chaque entite (Organisation, User, Site, etc.)
Generer un dataset coherent : 1 org, 3 sites, 10 equipements, 15 demandes, 20 interventions Ecrire le README : installation, comptes de demo, parcours de test
Validation : reset DB > migrate > load fixtures > demo complete sans erreur.


Jour 19  Scenario demo + durcissement final

Objectif : Avoir un MVP propre, testable et pret a presenter.
Derouler le scenario complet : demande > qualification > intervention > photos > CR > cloture > reporting Verifier toutes les routes protegees (access_control)
Verifier les Voters sur tous les points d'entree Verifier les requetes N+1 avec le profiler Symfony Tag Git : git tag v1.0.0-mvp
Validation : Scenario repetable de bout en bout sans bug. Tag Git cree.







Page 14
GMAO SaaS MVP — Symfony 8.0 · PHP 8.4	v1.1 — 11/02/2026


11. Post-MVP — 5 evolutions les plus rentables



#	Evolution

1	Table counters

2	EventLog

3	Mailpit Docker

4	SLA retard

5	Multi-tenant reel

Description

Numerotation transactionnelle (verrou DB)

Audit des changements de statuts (par qui, quand)

Email catcher en dev au lieu de null://null

Base sur priorite (P1=4h, P2=24h...) + alertes

Plusieurs organisations + super-admin






Resume



Ce document contient : pages MVP par role, matrice de permissions (access_control + Voters), workflows de statuts verrouilles (Demande + Intervention), MLD Doctrine renforce (index + contraintes), strategie numerotation fiable (DEM/INT), approche upload securisee (hors public/), et un plan jour par jour (19 jours) avec objectif / taches / validation pour chaque jour.

Stack validee : Symfony 8.0.5 (PHP 8.4) · Tailwind CSS v4.1 · KnpPaginatorBundle 6.10 · Foundry 2.9.1 · MySQL 8+

Points techniques cadres par docs officielles : UserChecker, Voters (ownership + tenant), Upload Symfony, Mailer null transport, Tailwind Play CDN, Enums Doctrine.




References documentation

https://symfony.com/doc/8.0/security.html https://symfony.com/doc/8.0/security/voters.html https://symfony.com/doc/8.0/security/user_checkers.html https://symfony.com/doc/8.0/controller/upload_file.html https://symfony.com/doc/8.0/mailer.html https://symfony.com/doc/8.0/doctrine.html https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html https://tailwindcss.com/docs/installation















Page 15
