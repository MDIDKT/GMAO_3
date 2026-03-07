# Plan de Test Manuel — Conformite CDC Jour 0 a 19

> Demarrer le serveur avant de commencer : `symfony server:start`
> Comptes de demo : voir README.md (mot de passe unique : `Test1234!`)

---

## 1. Login + Securite de base (Jour 1-2)

- [ ] Acceder a `/demande` sans etre connecte → redirection vers `/login`
- [ ] Login avec mauvais mot de passe → erreur "Invalid credentials"
- [ ] Login avec bon compte (`admin@gmao.fr / Test1234!`) → redirection vers `/`

---

## 2. Compte inactif (Jour 3)

- [ ] Passer un user a `actif = false` en base
- [ ] Tenter de se connecter → erreur "compte desactive"
- [ ] Repasser a `actif = true` → login OK

---

## 3. Invitation + Activation (Jour 4-5)

- [ ] ADMIN : inviter un user (email, nom, prenom, role)
- [ ] Email visible dans le profiler Symfony (icone enveloppe)
- [ ] User cree en base avec `actif = false`, `invitation_token` renseigne
- [ ] Cliquer sur le lien `/activation/{token}` → formulaire mot de passe
- [ ] Definir mot de passe (min 8 chars) → redirection `/login`
- [ ] Se connecter avec le nouveau compte → succes
- [ ] Verifier en base : `actif = true`, `invitation_token = null`
- [ ] Token expire (> 48h) → acces refuse

---

## 4. Sites + Batiments (Jour 6)

- [ ] DEMANDEUR tente `/site` → erreur 403
- [ ] ADMIN accede `/site` → OK
- [ ] ADMIN cree un site → `organisation_id` rempli en base (NOT NULL)
- [ ] Sur `/batiment/new` : dropdown "Site" affiche les **noms** (pas des IDs)
- [ ] Dropdown Site ne montre que les sites de **l'organisation de l'admin connecte**
- [ ] ADMIN Org2 (`admin@patrimoine.fr`) ne voit pas les sites de Org1

---

## 5. Equipements (Jour 7)

- [ ] ADMIN accede `/equipement` → OK
- [ ] Sur `/equipement/new` :
    - [ ] Dropdown Site → noms uniquement, filtres par organisation
    - [ ] Dropdown Batiment → noms uniquement, filtres par organisation
    - [ ] Dropdown Categorie → filtree par organisation
  - [ ] Pas de dropdown "Organisation" dans le form
- [ ] Creer equipement → `organisation_id` correct en base
- [ ] Filtres index : site + categorie + statut se cumulent

---

## 6. Enums + Demande.motifRejet (Jour 8)

- [ ] `SELECT motif_rejet FROM demande LIMIT 1` → colonne existe en base
- [ ] Formulaire demande → pas de champ motifRejet expose
- [ non fonctionnel ] Dropdown Priorite affiche : "P1 — Urgente", "P2 — Haute", etc. (pas `p1_URGENTE`)

---

## 7. CRUD Demande + Numerotation (Jour 9)

- [ ] TECHNICIEN tente `/demande` → erreur 403
- [ PAS DE BOUTON DEMANDE SUR LA PAGE ] DEMANDEUR cree une demande → succes
- [ ] Verifier en base :
    - [ ] `numero` = `DEM-2026-XXXX` (format correct)
  - [ ] `statut` = `A_QUALIFIER`
      - [ ] `user_id` = user connecte
  - [ ] `organisation_id` = organisation du user
- [ ] Creer 2 autres demandes → numeros incrementaux
- [ ] Dropdown Site du form → uniquement sites de l'organisation
- [ ] Dropdown Batiment → uniquement batiments de l'organisation
- [ ] Dropdown Equipement → uniquement equipements de l'organisation

---

## 8. Photos demande (Jour 10)

- [ ] Uploader un `.jpg` valide (< 5MB) → succes
- [ ] Uploader un `.png` → succes
- [ ] Uploader un `.webp` → succes
- [ ] Uploader un `.pdf` ou `.exe` → erreur de validation MIME
- [ ] Uploader un `.jpg` > 5MB → erreur de taille
- [ ] Photo visible sur la page detail de la demande
- [ ] Fichier stocke dans `var/uploads/photos/` (pas dans `public/`)
- [ ] Acces `/demande/photos/{id}` → fichier servi correctement

---

## 9. Filtres + Pagination (Jour 11)

- [ ] Filtrer par site → demandes du site uniquement
- [ ] Ajouter filtre priorite → cumul des deux filtres
- [ ] Ajouter filtre statut → cumul des trois filtres
- [ ] Recherche texte → resultats sur titre ou description
- [ ] Bouton "Reinitialiser" → tous les filtres vides
- [ ] Avec 25+ demandes → pagination active, page 2 accessible
- [ ] Pagination sur toutes les pages : Site, Batiment, Equipement, CategorieEquipement, Demande, Intervention (5 par
  page)

---

## 10. Interventions (Jour 12)

### Creation

- [ ] DEMANDEUR tente `/intervention` → erreur 403
- [ ] PLANIFICATEUR accede `/intervention` → OK
- [ ] Sur `/demande/{id}` → bouton "Planifier une intervention" visible

### Formulaire

- [ ] Seuls 3 champs visibles : **Technicien**, **Date planifiee**, **Planificateur**
- [ CHAMPS CR VISIBLE ] Pas de champs : statut, dateDebut, dateFin, compteRendu, dureeMinutes, notes, demande,
  organisation
- [ ] Dropdown Technicien → uniquement users `ROLE_TECHNICIEN` de l'organisation
- [ ] Dropdown Planificateur → uniquement users `ROLE_PLANIFICATEUR` de l'organisation

### Regles metier

- [ ] Creer avec technicien + date → `statut = PLANIFIE` en base
- [ ] Apres creation → `demande.statut = PLANIFIE` en base
- [ ] Numero genere : `INT-2026-XXXX`

### Gardes

- [ ] Demande avec statut `CLOTURE` → pas de creation
- [ ] Demande avec statut `REJETEE` → pas de creation
- [ ] PLANIFICATEUR Org2 ne voit pas les interventions de Org1

---

## 11. Workflow intervention (Jour 13)

### Demarrer

- [ ] Bouton "Demarrer" visible uniquement si statut = PLANIFIE
- [ ] Cliquer Demarrer → statut passe a EN_COURS
- [ ] `dateDebut` renseignee automatiquement
- [ ] Demande liee passe a statut EN_COURS (cascade)

### Terminer

- [ ] Bouton "Terminer" visible uniquement si statut = EN_COURS
- [ ] Cliquer Terminer sans compte rendu → erreur (obligatoire)
- [ ] Cliquer Terminer avec compte rendu → statut passe a TERMINEE
- [ ] `dateFin` renseignee automatiquement
- [ CALCUL NON FAIT ET PAS AFFICHE ] `dureeMinutes` calculee automatiquement
- [ ] Demande liee passe a statut CLOTURE (cascade)

---

## 12. Photos intervention (Jour 14)

- [ ] Formulaire upload photos visible uniquement si intervention EN_COURS
- [ ] Formulaire visible uniquement pour le technicien assigne
- [ ] Upload photo type AVANT → succes
- [ ] Upload photo type APRES → succes
- [ ] Upload photo type COMPLEMENT → succes
- [ ] Galerie groupee par type visible sur la page detail (tous statuts)
- [ ] PLANIFICATEUR voit la galerie mais pas le formulaire upload

---

## 13. Voters - protection anti-IDOR (Jour 15)

### InterventionVoter

- [ ] `tech1@gmao.fr` voit ses interventions dans "Mes interventions"
- [ ] `tech1@gmao.fr` ne voit PAS les interventions de `tech2@gmao.fr`
- [ ] `tech1@gmao.fr` tente `/intervention/{id_de_tech2}` → 403
- [ ] PLANIFICATEUR peut voir toutes les interventions de son organisation
- [ ] PLANIFICATEUR ne peut pas voir les interventions d'une autre organisation

### DemandeVoter

- [ ] DEMANDEUR voit uniquement ses propres demandes dans "Mes demandes"
- [ ] DEMANDEUR tente `/demande/{id_autre_demandeur}` → 403
- [ ] ADMIN/PLANIFICATEUR peut voir toutes les demandes de son organisation

### Actions protegees

- [ ] Demarrer : seul le technicien assigne peut demarrer
- [ ] Terminer : seul le technicien assigne peut terminer
- [ ] Ajouter photos : seul le technicien assigne peut ajouter
- [ ] Valider : seul ADMIN ou PLANIFICATEUR peut valider

---

## 14. Qualifier / Rejeter demande + Valider intervention (Jour 16)

### Qualifier une demande

- [ ] Bouton "Qualifier" visible sur demande A_QUALIFIER
- [ ] Cliquer → statut passe a QUALIFIE

### Rejeter une demande

- [ ] Bouton "Rejeter" visible sur demande A_QUALIFIER
- [ ] Rejeter sans motif → erreur (motifRejet obligatoire)
- [ ] Rejeter avec motif → statut passe a REJETEE, motifRejet enregistre

### Valider une intervention

- [ ] Bouton "Valider" visible sur intervention TERMINEE
- [ ] Cliquer → statut passe a VALIDEE
- [ ] Seul ADMIN ou PLANIFICATEUR peut valider

---

## 15. Dashboard (Jour 16)

- [ ] Se connecter en ADMIN → redirection vers Dashboard
- [ ] Se connecter en PLANIFICATEUR → redirection vers Dashboard
- [ ] Se connecter en TECHNICIEN → redirection vers "Mes interventions"
- [ ] Se connecter en DEMANDEUR → redirection vers "Mes demandes"
- [ ] Dashboard affiche les KPI dynamiques :
    - [ ] Nombre de demandes P1 ouvertes
    - [ ] Nombre de demandes a qualifier
    - [ ] Interventions du jour
    - [ ] Interventions en retard
    - [ ] Compteurs demandes : urgentes, ouvertes, cloturees, total
    - [ ] Compteurs interventions : a planifier, en cours, terminees, total

---

## 16. Reporting (Jour 17)

- [ ] ADMIN accede `/reporting` → OK
- [ ] PLANIFICATEUR accede `/reporting` → OK
- [ ] TECHNICIEN tente `/reporting` → 403
- [ ] DEMANDEUR tente `/reporting` → 403

### Filtres

- [ ] Filtre par site (select) → KPI recalcules
- [ ] Filtre par date debut → KPI recalcules
- [ ] Filtre par date fin → KPI recalcules
- [ ] Combinaison site + periode → KPI recalcules

### KPI

- [ ] KPI 1 : Demandes par statut → compteurs colores par statut
- [ ] KPI 2 : Delai moyen de traitement → affichage heures ou jours
- [ ] KPI 3 : Interventions par technicien → tableau avec total par tech
- [ ] KPI 4 : Demandes par site et priorite → tableau croise sites x P1/P2/P3/P4

---

## 17. Fixtures (Jour 18)

- [ ] `php bin/console doctrine:fixtures:load --append` → charge sans erreur
- [ ] 4 organisations en base
- [ ] 15 utilisateurs avec les bons roles
- [ ] 20 sites, 60 batiments, 32 categories, 100 equipements
- [ ] 40 demandes (tous statuts et priorites)
- [ ] 24 interventions (tous statuts)
- [ ] Connexion `admin@gmao.fr / Test1234!` → succes

---

## 18. Scenario nominal E2E (Jour 19)

### 1. Admin invite un demandeur

- [ ] Se connecter `admin@gmao.fr / Test1234!`
- [ ] Utilisateurs → Inviter un utilisateur (email, nom, prenom, role DEMANDEUR)
- [ ] Verifier email envoye (profiler Symfony)

### 2. Activation + Login

- [ ] Ouvrir le lien d'activation
- [ ] Definir mot de passe → redirection login
- [ ] Se connecter avec le nouveau compte → OK

### 3. Demandeur cree une demande avec photos

- [ ] Creer une demande : titre, description, site, priorite
- [ ] Ajouter 1-2 photos
- [ ] Verifier numero auto DEM-2026-XXXX
- [ ] Demande visible dans "Mes demandes"

### 4. Planificateur qualifie + cree intervention

- [ ] Se connecter `planificateur@gmao.fr / Test1234!`
- [ ] Qualifier la demande (A_QUALIFIER → QUALIFIE)
- [ ] Creer intervention → assigner `tech1@gmao.fr` + date planifiee
- [ ] Verifier : demande passe a PLANIFIE

### 5. Technicien execute

- [ ] Se connecter `tech1@gmao.fr / Test1234!`
- [ ] Mes interventions → Demarrer l'intervention
- [ ] Verifier : statut EN_COURS, dateDebut renseignee
- [ ] Ajouter photos AVANT + APRES
- [ ] Terminer → compte rendu obligatoire → statut TERMINEE

### 6. Planificateur valide

- [ ] Se reconnecter `planificateur@gmao.fr`
- [ ] Valider l'intervention → statut VALIDEE
- [ ] Verifier : demande auto-cloturee (statut CLOTURE)

### 7. Verifier Reporting

- [ ] Aller dans Reporting
- [ ] KPI refletent les actions effectuees

---

## 19. Securite (Jour 19)

### Acces par role

- [ ] En TECHNICIEN (`tech1@gmao.fr`) : tenter `/admin/user/inviter` → 403
- [ ] En TECHNICIEN : tenter `/site` → 403
- [ ] En TECHNICIEN : tenter `/batiment` → 403
- [ ] En TECHNICIEN : tenter `/equipement` → 403
- [ ] En TECHNICIEN : tenter `/reporting` → 403
- [ ] En DEMANDEUR (`demandeur@gmao.fr`) : tenter `/intervention` → 403
- [ ] En DEMANDEUR : tenter `/admin/user/inviter` → 403
- [ ] En DEMANDEUR : tenter `/reporting` → 403

### Voter isolation

- [ ] `tech1@gmao.fr` ne voit PAS les interventions de `tech2@gmao.fr`
- [ ] `tech1@gmao.fr` tente URL intervention de tech2 → 403
- [ ] `demandeur@gmao.fr` tente URL demande d'un autre user → 403

### Multi-tenant

- [ ] `admin@gmao.fr` (Org1) ne voit pas les donnees de `admin@patrimoine.fr` (Org3)
- [ ] Tenter d'acceder directement a une ressource d'une autre organisation → 403

---

## 20. Performance (Jour 19)

- [ ] Ouvrir le Profiler Symfony sur la page Demandes → noter le nombre de requetes SQL
- [ ] Ouvrir le Profiler sur la page Interventions → noter le nombre de requetes SQL
- [ ] Ouvrir le Profiler sur le Dashboard → noter le nombre de requetes SQL
- [ ] Ouvrir le Profiler sur le Reporting → noter le nombre de requetes SQL
- [ ] Pas de N+1 flagrant (pas de requete repetee en boucle)

---

## 21. Verifications techniques finales

```bash
php bin/console lint:container       # Container OK
php bin/console lint:twig templates/ # Templates valides
php bin/console doctrine:migrations:status # Migration executee
```

- [ ] Aucune erreur au demarrage du serveur
- [ ] Tag Git `v1.0.0-mvp` cree

---

> Total : ~100 verifications couvrant Jour 0 a 19
