# GMAO_3
Création de ma GMAO en MVP

14-02-26

* Invitation + reception mail OK
* Faire le jour 5 validation de l'activation du compte

15-02-26

* Activation du compte OK
* prochainement terminer le jour 5 avec comprehension et faire le jour 6
* templates/email/invitation.html.twig doit être complété avec un bouton et un message disant de valider mon compte dans
  les 48h puis renvoyer vers la page de validation du mdp

16-02-26

* Activation du compte OK
* Rception mail OK
* Validation du mdp OK
* continuer a valider le fonctionnement du jour 5

17-02-26

* Validation du mdp OK
* je passe au jour 6
* J'ai cree les entty et CRUD pour site et batiment

18-02-26

* j'ai tenté de faire les filtre mais je ne les comprends pas je n'y arrive pas . je ne comprend pas trop la notion et
  comment l'implementer
* je passe au jour 7
* J'ai fait ajouter les entity pour categorie et equipement(+CRUD)
* il me reste a faire les filtres

19-02-26

* J'ai fait les filtres et les ajouter dans le tableau des batiments 100%
* jour6 tout est ok
* je passe au jour 7 avec les filtres a finir avant de passer au jour 8

20_02_26

* J'ai realisé le jours 7 avec mise en place des filtre comme demandé
* correction des different incoherence dans mon code.
* Mise en place des fixture
* modification des templates pour avoir la meme ui que sur GMAO_VISUEL
* Je passe au jour 8
* Début du jour 8
* verification des enum pour voir s'il sont tous fait
* creation de l'entité demande
* jour 8 tout est ok
*

21-02-26

* creation de mon service demande qui fait le numero de demande avec le prefixe et la date
* il passe bien dans mon controlleur puis je l'affiche
* j'ai mis un numero aleatoire mais je doit mettre en place la recherche du dernier numero de demande en bdd vie le
* repository
* le recuperer dans le service demande

22-02-26

* j'ai fini de mettre en place le numero de demande avec la date et le prefixe
* j'ai fini de mettre en place le service demande qui fait le numero de demande avec le prefixe et la date
* j'ai fini de mettre en place le repository demande qui fait la recherche du dernier numero de demande en bdd vie le
* repository
* j'ai fini de mettre en place le controller demande qui fait le recuperer dans le service demande
* jour 9 tout est ok
*
* jour 10 : Objectif : Permettre d'ajouter des photos lors de la creation ou edition d'une demande.

23-02-26
debut jour 10

* mise en place de l'entité photo
* mise en place du controller photo
* verification a faire et verifier la remonté des images

24-02-26
jour 10 finalisé

* upload des photos OK sur la creation et l'edition d'une demande
* affichage des images OK dans le detail de la demande (route photo_show)
* correction des erreurs sur le fichier temporaire et created_at
* jour 10 tout est ok

25-02-26
jour 11 - nettoyage et pagination des demandes

* correction des filtres dans index() (suppression $site = null)
* suppression du $_GET dans le repository
* findByFilters() réécrit avec des if séparés par filtre
* conversion string vers Enum avec tryFrom()
* récupération du Site via SiteRepository
* mise en place de la pagination KnpPaginator
* controller nettoyé (variables inutiles supprimées)
* correction de la valeur de limit de 10 a 3 car j'ai moins de 10 demandes donc la pagination ne s'affiche pas
  jour 11 tout est ok

28-02-26
jour 12 - Module Intervention

* creation de l'entite Intervention (relations Demande, User technicien/planificateur, Organisation, Photo)
* enum StatutIntervention (A_PLANIFIER, PLANIFIE, EN_COURS, TERMINEE, VALIDEE)
* controller CRUD avec filtrage par organisation
* NumberingService etendu pour generer les numeros INT-YYYY-NNNN
* correction NumberingService (or → ?? et ||)
* formulaire avec query_builder pour filtrer demandes et users par organisation
* templates stylises Tailwind/Flowbite (index avec compteurs, show 2 colonnes, form avec champs individuels)
* lien Interventions actif dans la sidebar
* jour 12 tout est ok

02-03-26
Audit complet Jour 0-12 + corrections CDC + Jour 13

Audit et corrections Jour 0-12 :
* ajout motifRejet (TEXT nullable) sur entite Demande (manquant MLD §5.2)
* correction contrainte File dans DemandeType : passage aux named arguments Symfony 8 (plus d'array de config)
* nettoyage InterventionType : suppression des 8 champs hors perimetre (dateDebut, dateFin, compteRendu, dureeMinutes, notes, statut, demande, organisation)
* ajout access_control security.yaml pour /intervention (ROLE_PLANIFICATEUR, ROLE_ADMIN)
* correction SiteType : suppression champ organisation (deja set par le controller)
* correction EquipementType : ajout option organisation, query_builder filtre par organisation, choice_label nom
* correction EquipementController : setOrganisation() + passage organisation au form
* correction BatimentType : ajout option organisation, query_builder filtre sites actifs par organisation, choice_label nom
* correction BatimentController : passage organisation au form
* ajout JoinColumn nullable: false sur Site.organisation
* migration : motif_rejet + organisation_id NOT NULL
* correction templates site/_form et equipement/_form : suppression bloc form.organisation (erreur Twig apres nettoyage FormType)
* creation PLAN-TEST-MANUEL.md

Jour 13 - Workflow intervention (Démarrer / Terminer) :
* InterventionService : demarrerIntervention() PLANIFIE→EN_COURS + dateDebut + cascade demande EN_COURS
* InterventionService : terminerIntervention() EN_COURS→TERMINEE + compteRendu obligatoire + dateFin + dureeMinutes + cascade demande CLOTURE
* InterventionController : actions demarrer et terminer (POST, CSRF, flash LogicException)
* MesInterventionsController : liste des interventions du technicien connecte
* show.html.twig : ajout bouton Demarrer (visible si PLANIFIE) et bouton Terminer (visible si EN_COURS)
* InterventionType : ajout champ compteRendu (TextareaType, required: false)
* jour 13 tout est ok

Jour 14 - Photos intervention (AVANT / APRES / COMPLEMENT) :
* InterventionPhotoType : FileType multiple + EnumType typePhoto (AVANT/APRES/COMPLEMENT, SIGNALEMENT exclu)
* InterventionController : action ajouterPhotos (POST /{id}/photos, verification EN_COURS + technicien assigne)
* show.html.twig : galerie groupee par type (toujours visible) + formulaire upload (EN_COURS + technicien uniquement)
* correction currentPage → currentPageNumber dans mes_interventions (KnpPaginator)
* jour 14 tout est ok

Jour 15 - Voters (protection anti-IDOR) :
* InterventionVoter : attributs VIEW, EDIT, DEMARRER, TERMINER, AJOUTER_PHOTO, DELETE
* security.yaml : ROLE_TECHNICIEN autorise sur /intervention
* denyAccessUnlessGranted sur toutes les actions du controller
* suppression verification manuelle redondante dans ajouterPhotos
* jour 15 tout est ok

03-03-26
Corrections post-audit + Début Jour 16

Corrections post-audit :
* fix Doctrine mapping Intervention#planificateur : suppression inversedBy incohérent
* fix User extends Site (erreur linter) : rétabli implements UserInterface
* fix terminerIntervention : guard null sur dateDebut
* fix DemandeRepository : ajout paramètre ?User $user à getQueryBuilderByFilters() et findByFilters()
* fix MesDemandesController : passage $currentUser via repository au lieu de inline query
* fix mes_demandes/index.html.twig : remplacement champs Intervention par champs Demande (createdAt, site, priorite) + statuts corrigés
* création CategorieEquipement CRUD complet (/admin/categories-equipement)
* ajout actions qualifier et rejeter sur Demande (avec motifRejet)
* ajout action valider sur Intervention (TERMINEE → VALIDEE) + attribut VALIDER dans InterventionVoter

Début Jour 16 - Dashboard adapté par rôle :
* création DemandeVoter (copie du pattern InterventionVoter adapté pour Demande)
* création HomeController route / avec redirection par rôle (admin/planif → dashboard, tech → mes-interventions, demandeur → mes-demandes)

05-03-26
Jour 16 finalisé - Dashboard + KPI + Pagination uniforme

Dashboard KPI :
* DashboardController : injection DemandeRepository + InterventionRepository, denyAccessUnlessGranted ROLE_PLANIFICATEUR
* DemandeRepository : countP1Ouvertes(), countAQualifier(), countUrgent(), countOpen(), countClosed(), countTotal()
* InterventionRepository : countInterventionsDuJour(), countInterventionsEnRetard(), countAPlanifier(), countEnCours(), countTerminees(), countTotal()
* template dashboard/dashborad.html.twig : remplacement des valeurs en dur par les variables du controller

Pagination uniforme sur toutes les pages :
* ajout pagination manuelle (Precedent/Suivant) sur : Site, Batiment, Equipement, CategorieEquipement
* remplacement knp_pagination_render() par pagination manuelle sur : Demande, Intervention
* suppression double requete sur Demande et Intervention (stats calculees par repository au lieu de boucle Twig)
* repositories : ajout getQueryBuilder*(), paginate*(), countActive(), countTotal() sur Site, Batiment, Equipement, CategorieEquipement
* limite pagination : 5 par page sur toutes les pages
* liens de pagination preservent les filtres actifs (site, statut, priorite, search, actif)

Corrections securite Jour 16 :
* fix DemandeVoter : suppression getTechnicien() (n'existe pas sur Demande), remplace par getUser() pour DEMANDEUR
* nettoyage attributs DemandeVoter : VIEW, EDIT, DELETE (suppression DEMARRER/TERMINER/AJOUTER_PHOTO/VALIDER qui n'ont pas de sens sur Demande)
* DemandeController : ajout denyAccessUnlessGranted sur show, edit, delete (anti-IDOR)
* sidebar filtree par role : Dashboard/Demandes/Interventions (ADMIN+PLANIF), Mes demandes (DEMANDEUR), Mes interventions (TECHNICIEN), Sites/Batiments/Equipements/Utilisateurs (ADMIN)
* suppression double requete MesInterventionsController (meme fix que Demande/Intervention)
* fix mes_interventions template : interventions is empty → pagination.items is empty
* dashboard : suppression donnees fake, remplacement par compteurs dynamiques + liens vers pages filtrees
* jour 16 tout est ok

Refonte des fixtures :
* CategorieEquipementFixtures : 4 → 8 categories par org (ajout Plomberie, Ascenseurs, Incendie, Menuiserie)
* SiteFixtures : 3 → 5 sites par org (20 sites total, ajout Strasbourg, Toulouse, Montpellier, Aix, Caen, Saint-Malo, La Rochelle, Limoges)
* BatimentFixtures : reecrit avec noms realistes (Batiment A - Administration, Hall Technique, Aile Nord - Bureaux…), 3 par site = 60 total
* EquipementFixtures : reecrit avec noms/marques/modeles realistes (Daikin, Schneider, Otis, Siemens…), 25 par org = 100 total
* creation DemandeFixtures : 20 demandes par org, tous statuts/priorites couverts, motifs de rejet inclus
* creation InterventionFixtures : 12 interventions par org, tous statuts, comptes rendus, durees, dates planifiees/debut/fin
* Users inchanges (6 users)
* chargement verifie : 4 org / 20 sites / 60 bat / 32 cat / 100 equip / 40 demandes / 24 interventions

Jour 17 - Reporting (4 KPI) :
* creation ReportingController route /reporting (PLANIFICATEUR + ADMIN)
* filtres : site (select) + periode (date debut / date fin)
* KPI 1 : demandes par statut (compteurs colores par statut)
* KPI 2 : delai moyen de traitement (heures/jours, base sur demandes CLOTURE)
* KPI 3 : interventions par technicien (tableau avec total par tech)
* KPI 4 : demandes par site et priorite (tableau croise sites x priorites)
* methodes repository dediees : countByStatut(), delaiMoyenTraitement(), countBySiteAndPriorite(), countByTechnicien()
* access_control /reporting dans security.yaml
* lien Reporting dans la sidebar (ADMIN + PLANIFICATEUR)
* jour 17 tout est ok

Jour 18 - Fixtures completes + README installation

Fixtures (doctrine-fixtures-bundle) :
* jeu de donnees demo complet, reproductible en une commande (php bin/console doctrine:fixtures:load --append)
* OrganisationFixtures : 4 organisations (3 actives, 1 inactive)
* SiteFixtures : 5 sites par organisation (20 total) avec adresses, telephones, emails
* BatimentFixtures : 3 batiments par site (60 total) avec noms realistes
* CategorieEquipementFixtures : 8 categories par organisation (32 total)
* EquipementFixtures : 25 equipements par organisation (100 total) avec marques/modeles reels
* DemandeFixtures : 20 demandes par organisation (40 total), tous statuts et priorites couverts
* InterventionFixtures : 12 interventions par organisation (24 total), tous statuts couverts
* UserFixtures : 15 utilisateurs repartis sur les 4 organisations (admin, planif, techniciens, demandeurs), mot de passe unique Test1234!
* dependances entre fixtures respectees (DependentFixtureInterface)

---

## Installation

```bash
git clone <url-du-repo>
cd GMAO
composer install
```

Copier le fichier `.env` en `.env.local` et configurer la base de donnees :
```
DATABASE_URL="mysql://user:password@127.0.0.1:3306/gmao?serverVersion=8.0"
```

Creer la base et executer les migrations :
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
```

Charger les fixtures :
```bash
php bin/console doctrine:fixtures:load --append
```

Lancer le serveur :
```bash
symfony server:start
```

## Comptes de demo

Mot de passe unique pour tous les comptes : `Test1234!`

| Email                   | Role            | Organisation        |
|-------------------------|-----------------|---------------------|
| admin@gmao.fr           | ROLE_ADMIN      | GMAO Industries     |
| planificateur@gmao.fr   | ROLE_PLANIFICATEUR | GMAO Industries  |
| tech1@gmao.fr           | ROLE_TECHNICIEN | GMAO Industries     |
| tech2@gmao.fr           | ROLE_TECHNICIEN | GMAO Industries     |
| demandeur@gmao.fr       | ROLE_DEMANDEUR  | GMAO Industries     |
| admin@maintenance-sud.fr | ROLE_ADMIN     | Maintenance Sud     |
| admin@patrimoine.fr     | ROLE_ADMIN      | Patrimoine Services |
| admin@infra-ouest.fr    | ROLE_ADMIN      | Infra Support Ouest |

## Scenario de test nominal

1. Se connecter en tant qu'ADMIN (admin@gmao.fr / Test1234!)
2. Verifier le Dashboard : KPI demandes + interventions
3. Aller dans Demandes : filtrer par statut, site, priorite
4. Creer une demande → verifier le numero auto (DEM-YYYY-NNNN)
5. Qualifier la demande (statut A_QUALIFIER → QUALIFIE)
6. Aller dans Interventions → creer une intervention liee a la demande
7. Se connecter en tant que TECHNICIEN (tech1@gmao.fr / Test1234!)
8. Voir Mes interventions → Demarrer l'intervention
9. Ajouter des photos (AVANT / APRES)
10. Terminer l'intervention (compte rendu obligatoire)
11. Se reconnecter en ADMIN → Valider l'intervention (TERMINEE → VALIDEE)
12. Verifier le Reporting : les 4 KPI refletent les actions effectuees
13. Se connecter en DEMANDEUR (demandeur@gmao.fr / Test1234!) → voir Mes demandes uniquement

* jour 18 tout est ok

08-03-26
Déploiement production sur AlwaysData

* premier déploiement de l'application en production
* hébergeur : AlwaysData (https://mdidkt.alwaysdata.net)
* base de données : MariaDB 10.11 (serverVersion=mariadb-10.11.15)
* PHP 8.4 + Apache avec .htaccess Symfony
* création commande app:create-admin pour créer le premier admin sans fixtures
* assets compilés (npm run build) et commités dans Git (public/build/ retiré du .gitignore)
* schéma créé via doctrine:schema:create + migrations marquées comme appliquées
* guide de déploiement complet disponible dans docs/DEPLOIEMENT-ALWAYSDATA.md
* application fonctionnelle en production

08-03-26
Audit de sécurité + corrections

* `FileUploadService` : remplacement de `uniqid()` par `bin2hex(random_bytes(16))` (noms de fichiers cryptographiquement aléatoires, non devinables)
* `AdminUserController` : suppression de l'email hardcodé `mdidkt@alwaysdata.net`, externalisé dans la variable d'environnement `MAILER_FROM` (`.env`)
* `InterventionType` : suppression des requêtes `LIKE '%"ROLE_*"%'` sur la colonne JSON des rôles ; remplacement par `UserRepository::findByOrganisationAndRole()` avec filtrage PHP strict via `in_array()`
* `security.yaml` : `always_remember_me: true` → `false` (cookie "se souvenir de moi" non forcé pour tous)
* `DemandeController` : limite 1000 caractères sur le motif de rejet (`mb_strlen`)
* `Intervention` entity + `InterventionService` + `InterventionFixtures` : `\DateTime` → `\DateTimeImmutable` sur `datePlanifiee`, `dateDebut`, `dateFin` (immuabilité + suppression des `clone` inutiles)
