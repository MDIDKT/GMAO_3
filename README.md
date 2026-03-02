# GMAO_3
Création de ma GMAO en MVP

14-02-26

* Invitation + reception mail OK
* Faire le jour 5 validation de l'activation du compte

15-02-26

* Activation du compte OK mais pas fait seul
* fait avec chatgpt donc revoir comment c'est fait pour decortiqué et comprendre comment c'est fait
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
* correction réalisé avec l'aide de codex 100%
* il a corriger et fait du copie coller le code fonctionne une verification reste a faire pour bien comprendre
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
* corrections fait avec codex donc reste a comprendre ce qu'il fait et comment il fonctionne exactement
* jour6 tout est ok
* je passe au jour 7 avec les filtres a finir avant de passer au jour 8

20_02_26

* J'ai realisé le jours 7 avec codex mise en place des filtre comme demandé
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

02-03-26 (pause)
Jour 14 - Photos intervention (AVANT / APRES / COMPLEMENT) : en cours
* a reprendre : InterventionPhotoType (FileType + EnumType typePhoto AVANT/APRES/COMPLEMENT)
* a reprendre : action ajouterPhotos dans InterventionController (verification EN_COURS + technicien assigne)
* a reprendre : galerie groupee par type dans show.html.twig

