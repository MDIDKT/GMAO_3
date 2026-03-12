# Audit complet du projet GMAO

Date : 12/03/2026
Perimetre audite : code Symfony, configuration, base locale, tests, documentation fonctionnelle
Reference de cadrage : `docs/GMAO-Plan/GMAO-Plan/GMAO_MVP_Spec_v1_2.md`

## 1. Synthese executive

Le projet est globalement exploitable en mode MVP.
Les verifications techniques de base passent :

- `php bin/phpunit` : OK, 17 tests, 46 assertions
- `php bin/console lint:container` : OK
- `php bin/console lint:twig templates` : OK
- `php bin/console lint:yaml config` : OK
- `php bin/console doctrine:schema:validate` : OK

Conclusion :

- le socle Symfony est sain ;
- les grands flux metier existent ;
- le projet n'est pas encore parfaitement conforme au cadrage initial ;
- plusieurs ecarts concernent la securite multi-tenant et la coherence du jeu de donnees de demo.

## 2. Inventaire reel du projet

Code :

- 9 entites
- 14 controllers
- 9 form types
- 4 services
- 2 voters
- 54 templates Twig
- 1 migration
- 5 fichiers de tests pour 17 tests

Base locale observee au 12/03/2026 :

- 4 organisations
- 15 utilisateurs
- 20 sites
- 60 batiments
- 32 categories equipement
- 100 equipements
- 81 demandes
- 50 interventions
- 2 photos

Remarque :

- le cadrage et les fixtures visent un jeu de demo propre de 40 demandes et 24 interventions ;
- la base locale n'est plus sur un etat de demo "reset" ;
- elle contient des ajouts et des formats historiques melanges.

## 3. Conformite fonctionnelle au MVP

### Conforme ou quasi conforme

- Authentification par formulaire : OK
- Blocage des comptes inactifs : OK
- Invitation admin + activation par token : OK
- CRUD sites / batiments / categories / equipements : OK
- Creation des demandes avec photos : OK
- Filtres et pagination sur les listes principales : OK
- Creation d'interventions et workflow demarrer / terminer / valider : OK
- Reporting 4 KPI avec filtres : OK
- Separation par roles et redirection par role sur `/` : OK
- Protection metier sur demandes et interventions via voters : OK

### Partiellement conforme

- Dashboard : fonctionne, mais la version livree est plus courte que la version documentee
- Fixtures de demo : presentes, mais le contenu ne respecte pas totalement le format et les roles attendus
- Documentation : historiquement riche mais devenue redondante et contradictoire

## 4. Ecarts confirmes

### Critique 1 - Acces direct non protege aux photos

Constat :

- `src/Controller/DemandeController.php:154-179` sert les photos de demande sans `denyAccessUnlessGranted()`
- `src/Controller/InterventionController.php:124-149` sert les photos d'intervention sans voter ni verification d'organisation

Impact :

- un utilisateur authentifie pouvant atteindre la route peut tester des IDs de photo ;
- la protection "hors public/" existe bien, mais elle est contournee par l'absence de controle d'acces metier sur le controller ;
- c'est un ecart direct avec le cadrage MVP, qui demandait un controller protege par voter.

Verdict : non conforme et a corriger en priorite.

### Haute 2 - CRUD admin referentiel sans verrou tenant sur les fiches

Constat :

- `src/Controller/SiteController.php:73-107`
- `src/Controller/BatimentController.php:73-113`
- `src/Controller/EquipementController.php:110-149`
- `src/Controller/CategorieEquipementController.php:65-103`

Les index sont filtres par organisation, mais les actions `show`, `edit` et `delete` ne verifient pas que la ressource appartient a l'organisation de l'admin connecte.

Impact :

- un admin d'une organisation peut potentiellement consulter ou modifier une fiche d'une autre organisation en changeant l'ID dans l'URL ;
- cela contredit l'isolation multi-tenant documentee.

Verdict : non conforme et a corriger rapidement.

### Haute 3 - La liste des equipements exclut les equipements sans batiment

Constat :

- `src/Entity/Equipement.php:37-47` autorise `site`, `batiment` et `organisation` a `null`
- `src/Form/EquipementType.php:41-56` rend `batiment` optionnel
- `src/Repository/EquipementRepository.php:92-98` et `143-166` utilisent `innerJoin('e.batiment', 'b')`

Impact :

- un equipement valide rattache a un site mais sans batiment disparait des listes et des compteurs ;
- le modele et l'UI disent "batiment facultatif", mais le repository impose en pratique "batiment obligatoire".

Verdict : incoherence fonctionnelle confirmee.

### Moyenne 4 - Les fixtures ne respectent pas totalement le cadrage MVP

Constat dans le code :

- `src/DataFixtures/DemandeFixtures.php:196-204` genere `DEM-0001`, `DEM-0002`, etc.
- `src/DataFixtures/InterventionFixtures.php:139-145` genere `INT-0001`, `INT-0002`, etc.
- `src/Service/NumberingService.php:19-35` attend pourtant le format `DEM-YYYY-NNNN` / `INT-YYYY-NNNN`
- `src/DataFixtures/InterventionFixtures.php:109-145` prend les utilisateurs dans la liste complete de l'organisation, sans filtrer les roles

Constat sur la base locale :

- 79 demandes utilisent encore un format `DEM-000X`
- 48 interventions utilisent encore un format `INT-000X`
- 31 interventions ont un "technicien" qui n'a pas `ROLE_TECHNICIEN`
- 25 interventions ont un "planificateur" qui n'a ni `ROLE_PLANIFICATEUR` ni `ROLE_ADMIN`

Impact :

- le jeu de demo brouille la validation du MVP ;
- certaines pages de reporting et de workflow reposent sur des donnees qui ne representent pas le metier attendu ;
- les captures, tests manuels et demos peuvent etre trompeurs.

Verdict : non bloquant pour le code, mais bloquant pour une demo propre.

### Moyenne 5 - Le schema Doctrine est plus permissif que le MLD cible

Constat :

- `src/Entity/Batiment.php:27-28` : `site` est nullable
- `src/Entity/CategorieEquipement.php:25-26` : `organisation` est nullable
- `src/Entity/Equipement.php:37-47` : `site` et `organisation` sont nullable

Impact :

- la contrainte "chaque entite metier doit rester rattachee a son tenant / son parent" n'est pas forcee en base ;
- des scripts, fixtures ou imports peuvent produire des donnees orphelines sans erreur ;
- cela affaiblit la robustesse multi-tenant.

Verdict : ecart structurel par rapport au MLD initial.

### Moyenne 6 - Dashboard plus court que l'attendu documente

Constat :

- `src/Controller/DashboardController.php:26-31` n'injecte que 4 compteurs
- `templates/dashboard/dashborad.html.twig:16-59` n'affiche que 4 KPI + 2 blocs simples

Alors que la documentation de suivi et les plans de test mentionnaient aussi des compteurs globaux demandes / interventions plus riches.

Impact :

- le dashboard ne contredit pas le MVP minimal ;
- en revanche, il ne suit plus l'attendu documente dans les autres fichiers du projet.

Verdict : ecart documentaire / fonctionnel mineur, mais reel.

### Basse 7 - Incoherences documentaires et de configuration

Constat :

- `config/packages/security.yaml:53-64` laisse `/` en `PUBLIC_ACCESS`, alors que le cadrage initial reservait le public a `/login` et `/activation`
- `src/Controller/AdminUserController.php:61-72` utilise un expediteur mail hardcode (`mdidkt@alwaysdata.net`) au lieu du parametre env deja centralise dans `config/services.yaml:28-30`
- l'ancien `docs/AUDIT-COMPLET.md` ne reflechissait plus l'etat reel du code

Impact :

- risque faible sur le fonctionnement ;
- confusion forte sur l'etat attendu du projet.

Verdict : a harmoniser, sans urgence critique.

## 5. Points forts confirmes

- Architecture Symfony propre et lisible
- Separation controller / repository / service bien respectee sur le coeur metier
- Voters presentes pour les demandes et interventions
- Upload hors `public/`
- Workflow intervention coherent et teste
- Reporting calcule en repository, pas en Twig
- Schema Doctrine en sync avec le code courant

## 6. Risques residuels

- Les tests existent mais couvrent surtout le socle, pas les cas multi-tenant sensibles
- L'etat de la base locale ne permet plus une validation demo propre sans reset
- Le projet depend encore fortement de conventions de formulaire la ou certaines contraintes devraient etre enforcees en entite / base

## 7. Priorites recommandees

1. Proteger les routes de lecture des photos avec un voter ou une verification sur la demande / intervention parente.
2. Ajouter un verrou d'organisation sur `show`, `edit`, `delete` pour Site, Batiment, Equipement et CategorieEquipement.
3. Corriger `EquipementRepository` pour partir de `e.site` et passer `batiment` en `leftJoin`.
4. Refaire les fixtures de demo pour respecter les roles et le format des numeros.
5. Rehausser les contraintes Doctrine sur les relations qui doivent etre obligatoires selon le MLD.
6. Aligner le dashboard documente et le dashboard reel, ou simplifier clairement la documentation.

## 8. Nettoyage documentaire realise dans ce passage

- `docs/GMAO-visuel` laisse intact, comme demande
- audit reecrit sur des constats verifiables
- fusion des documents redondants dans `docs`
- creation d'un fichier MVP canonique en Markdown dans `docs/GMAO-Plan/GMAO-Plan`
- suppression des variantes parasites, PDF doublons et artefacts de conversion

