# Plan de Test Manuel — Conformité CDC Jour 0 à 12

> Démarrer le serveur avant de commencer : `symfony server:start`
> Avoir en base : 1 compte ADMIN, 1 PLANIFICATEUR, 1 TECHNICIEN, 1 DEMANDEUR (2 organisations distinctes recommandées)

---

## 1. Login + Sécurité de base (Jour 1-2)

- [ ] Accéder à `/demande` sans être connecté → redirection vers `/login`
- [ ] Login avec mauvais mot de passe → erreur "Invalid credentials"
- [ ] Login avec bon compte → redirection vers `/`

---

## 2. Compte inactif (Jour 3)

- [ ] Passer un user à `actif = false` en base
- [ ] Tenter de se connecter → erreur "compte désactivé"
- [ ] Repasser à `actif = true` → login OK

---

## 3. Invitation + Activation (Jour 4-5)

- [ ] ADMIN : inviter un user (email, nom, prénom, rôle)
- [ ] Email visible dans le profiler Symfony (icône enveloppe)
- [ ] User créé en base avec `actif = false`, `invitation_token` renseigné
- [ ] Cliquer sur le lien `/activation/{token}` → formulaire mot de passe
- [ ] Définir mot de passe (min 8 chars) → redirection `/login`
- [ ] Se connecter avec le nouveau compte → succès
- [ ] Vérifier en base : `actif = true`, `invitation_token = null`

---

## 4. Sites + Bâtiments (Jour 6)

- [ ] DEMANDEUR tente `/site` → erreur 403
- [ ] ADMIN accède `/site` → OK
- [ ] ADMIN crée un site → `organisation_id` rempli en base (NOT NULL)
- [ ] Sur `/batiment/new` : dropdown "Site" affiche les **noms** (pas des IDs)
- [ ] Dropdown Site ne montre que les sites de **l'organisation de l'admin connecté**
- [ ] ADMIN Org2 ne voit pas les sites de Org1

---

## 5. Equipements (Jour 7)

- [ ] ADMIN accède `/equipement` → OK
- [ ] Sur `/equipement/new` :
  - [ ] Dropdown Site → noms uniquement, filtrés par organisation
  - [ ] Dropdown Bâtiment → noms uniquement, filtrés par organisation
  - [ ] Dropdown Catégorie → filtrée par organisation
  - [ ] Pas de dropdown "Organisation" dans le form
- [ ] Créer équipement → `organisation_id` correct en base
- [ ] Filtres index : site + catégorie + statut se cumulent

---

## 6. Enums + Demande.motifRejet (Jour 8)

- [ ] `SELECT motif_rejet FROM demande LIMIT 1` → colonne existe en base
- [ ] Formulaire demande → pas de champ motifRejet exposé
- [ ] Dropdown Priorité affiche : "P1 — Urgente", "P2 — Haute", etc. (pas `p1_URGENTE`)

---

## 7. CRUD Demande + Numérotation (Jour 9)

- [ ] TECHNICIEN tente `/demande` → erreur 403
- [ ] DEMANDEUR crée une demande → succès
- [ ] Vérifier en base :
  - [ ] `numero` = `DEM-2026-0001` (format correct)
  - [ ] `statut` = `A_QUALIFIER`
  - [ ] `user_id` = user connecté
  - [ ] `organisation_id` = organisation du user
- [ ] Créer 2 autres demandes → `DEM-2026-0002`, `DEM-2026-0003`
- [ ] Dropdown Site du form → uniquement sites de l'organisation
- [ ] Dropdown Bâtiment → uniquement bâtiments de l'organisation
- [ ] Dropdown Équipement → uniquement équipements de l'organisation

---

## 8. Photos (Jour 10)

- [ ] Uploader un `.jpg` valide (< 5MB) → succès
- [ ] Uploader un `.png` → succès
- [ ] Uploader un `.webp` → succès
- [ ] Uploader un `.pdf` ou `.exe` → erreur de validation MIME
- [ ] Uploader un `.jpg` > 5MB → erreur de taille
- [ ] Photo visible sur la page détail de la demande
- [ ] Fichier stocké dans `var/uploads/photos/` (pas dans `public/`)
- [ ] Accès `/demande/photos/{id}` → fichier servi correctement

---

## 9. Filtres + Pagination (Jour 11)

- [ ] Filtrer par site → demandes du site uniquement
- [ ] Ajouter filtre priorité → cumul des deux filtres
- [ ] Ajouter filtre statut → cumul des trois filtres
- [ ] Recherche texte → résultats sur titre ou description
- [ ] Bouton "Réinitialiser" → tous les filtres vidés
- [ ] Avec 25+ demandes → pagination active, page 2 accessible

---

## 10. Interventions (Jour 12)

### Création

- [ ] DEMANDEUR tente `/intervention` → erreur 403
- [ ] TECHNICIEN tente `/intervention` → erreur 403
- [ ] PLANIFICATEUR accède `/intervention` → OK
- [ ] Sur `/demande/{id}` → bouton "Planifier une intervention" visible
- [ ] Cliquer → redirection vers `/intervention/new/{demande}`

### Formulaire

- [ ] Seuls 3 champs visibles : **Technicien**, **Date planifiée**, **Planificateur**
- [ ] Pas de champs : statut, dateDebut, dateFin, compteRendu, dureeMinutes, notes, demande, organisation
- [ ] Dropdown Technicien → uniquement users `ROLE_TECHNICIEN` de l'organisation
- [ ] Dropdown Planificateur → uniquement users `ROLE_PLANIFICATEUR` de l'organisation

### Règles métier

- [ ] Créer sans technicien ni date → `statut = A_PLANIFIER` en base
- [ ] Créer avec technicien + date → `statut = PLANIFIE` en base
- [ ] Après création → `demande.statut = PLANIFIE` en base
- [ ] Numéro généré : `INT-2026-0001`, `INT-2026-0002`, etc.

### Gardes

- [ ] Demande avec statut `CLOTURE` → flash d'erreur + pas de création
- [ ] Demande avec statut `REJETEE` → flash d'erreur + pas de création
- [ ] PLANIFICATEUR Org2 ne voit pas les interventions de Org1

---

## 11. Isolation multi-tenant

- [ ] User Org1 crée : site, demande, équipement, intervention
- [ ] User Org2 se connecte → **ne voit rien de Org1** sur toutes les listes
- [ ] User Org2 tente d'accéder directement à `/demande/{id_org1}` → 403 ou objet vide

---

## Vérifications techniques finales

```bash
php bin/console lint:container       # Container OK
php bin/console lint:twig templates/ # 37 templates valides
php bin/console doctrine:migrations:status # Migration exécutée
```
