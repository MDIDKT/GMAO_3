# Audit Complet — GMAO MVP
> Date : 06/03/2026 | Stack : Symfony 8 · PHP 8.4 · MySQL 8 · Tailwind v4.1

---

## 1. Inventaire du projet

### Entites (9)
| Entite | Champs principaux | Relations |
|--------|------------------|-----------|
| Organisation | nom (unique), actif | OneToMany: users, sites, catEquipements, equipements, demandes, interventions |
| User | email (unique), roles, password, nom, prenom, actif, invitationToken, tokenExpiresAt | ManyToOne: Organisation · OneToMany: demandes, photos, interventions |
| Site | nom, adresse, codePostal, ville, telephone, email, actif | ManyToOne: Organisation · OneToMany: batiments, equipements, demandes |
| Batiment | nom, etage, actif | ManyToOne: Site · OneToMany: equipements, demandes |
| CategorieEquipement | nom, description | ManyToOne: Organisation · OneToMany: equipements |
| Equipement | nom, marque, modele, numeroDeSerie, statut (enum), actif | ManyToOne: Site, Batiment, Categorie, Organisation |
| Demande | numero (unique), titre, description, priorite (enum), statut (enum), motifRejet | ManyToOne: Site, Batiment, Equipement, User, Organisation · OneToMany: photos, interventions |
| Intervention | numero (unique), statut (enum), datePlanifiee, dateDebut, dateFin, dureeMinutes, compteRendu | ManyToOne: Demande, technicien (User), planificateur (User), Organisation · OneToMany: photos |
| Photo | fileName, originalName, mimeType, taille, typePhoto (enum) | ManyToOne: Demande, Intervention, uploadPar (User) |

### Enums (5)
| Enum | Valeurs |
|------|---------|
| StatutEquipement | EN_SERVICE, HORS_SERVICE, EN_PANNE |
| Priorite | P1_URGENTE, P2_HAUTE, P3_NORMALE, P4_BASSE |
| StatutDemande | NOUVEAU, A_QUALIFIER, QUALIFIE, PLANIFIE, EN_COURS, CLOTURE, REJETEE |
| StatutIntervention | A_PLANIFIER, PLANIFIE, EN_COURS, TERMINEE, VALIDEE |
| TypePhoto | SIGNALEMENT, AVANT, APRES, COMPLEMENT |

### Controllers (15)
| Controller | Route | Acces |
|------------|-------|-------|
| SecurityController | /login, /logout | PUBLIC |
| ActivationController | /activation/{token} | PUBLIC |
| HomeController | / | ROLE_USER (redirige par role) |
| DashboardController | /dashboard | PLANIFICATEUR, ADMIN |
| AdminUserController | /admin/user/inviter | ADMIN |
| SiteController | /site (CRUD) | ADMIN |
| BatimentController | /batiment (CRUD) | ADMIN |
| EquipementController | /equipement (CRUD + filtres) | ADMIN |
| CategorieEquipementController | /admin/categories-equipement (CRUD) | ADMIN |
| DemandeController | /demande (CRUD + qualifier/rejeter) | DEMANDEUR, PLANIFICATEUR, ADMIN |
| MesDemandesController | /mes-demandes | DEMANDEUR |
| InterventionController | /intervention (CRUD + demarrer/terminer/valider + photos) | PLANIFICATEUR, ADMIN, TECHNICIEN |
| MesInterventionsController | /mes-interventions | TECHNICIEN |
| ReportingController | /reporting | PLANIFICATEUR, ADMIN |

### Services (3)
| Service | Responsabilite |
|---------|---------------|
| NumberingService | Generation des numeros DEM-YYYY-NNNN et INT-YYYY-NNNN |
| InterventionService | Logique metier : createIntervention, demarrer, terminer (cascade statuts) |
| FileUploadService | Upload securise dans var/uploads/photos/ |

### Voters (2)
| Voter | Attributs proteges |
|-------|-------------------|
| InterventionVoter | VIEW, EDIT, DEMARRER, TERMINER, AJOUTER_PHOTO, DELETE, VALIDER |
| DemandeVoter | VIEW, EDIT, DELETE |

### Repositories avec methodes custom
| Repository | Methodes cles |
|------------|--------------|
| DemandeRepository | findByFilters, countP1Ouvertes, countAQualifier, countByStatut, delaiMoyenTraitement, countBySiteAndPriorite |
| InterventionRepository | getQueryBuilderByFilters, countInterventionsDuJour, countEnRetard, countByTechnicien |
| SiteRepository | getQueryBuilderByOrganisation, paginateSites, countActive |
| BatimentRepository | getQueryBuilderByOrganisation, paginateBatiments, countActive |
| EquipementRepository | getQueryBuilderByFilters (site+categorie+statut), paginateEquipements |

### Fixtures (9)
| Fixture | Donnees |
|---------|---------|
| OrganisationFixtures | 4 organisations (3 actives, 1 inactive) |
| UserFixtures | 15 utilisateurs, 4 roles, mot de passe Test1234! |
| SiteFixtures | 20 sites (5 par org) |
| BatimentFixtures | 60 batiments (3 par site) |
| CategorieEquipementFixtures | 32 categories (8 par org) |
| EquipementFixtures | 100 equipements (25 par org) |
| DemandeFixtures | 40 demandes (20 par org), tous statuts |
| InterventionFixtures | 24 interventions (12 par org), tous statuts |
| AppFixtures | Vide (generee par defaut) |

---

## 2. Conformite CDC — Jour par Jour

| Jour | Objectif | Statut | Remarque |
|------|----------|--------|----------|
| 1 | Init projet + Symfony + DB + Tailwind | ✅ 100% | Projet fonctionnel |
| 2 | Organisation + User + Login | ✅ 100% | Form login + security.yaml |
| 3 | Blocage compte inactif + Mailer | ✅ 100% | UserChecker + null://null |
| 4 | Invitation admin (token) | ✅ 100% | AdminUserController + email template |
| 5 | Activation / mot de passe | ✅ 100% | ActivationController + SetPasswordType |
| 6 | Sites + Batiments CRUD | ✅ 100% | Filtre par organisation |
| 7 | Categories + Equipements + filtres | ✅ 100% | 3 filtres cumulables |
| 8 | Enums + entite Demande | ✅ 100% | 5 enums + motifRejet |
| 9 | CRUD Demande + numerotation | ✅ 100% | NumberingService DEM-YYYY-NNNN |
| 10 | Photos demande | ✅ 100% | FileUploadService + var/uploads/ |
| 11 | Filtres + pagination | ✅ 100% | KnpPaginator + filtres GET |
| 12 | Intervention CRUD + numerotation | ✅ 100% | NumberingService INT-YYYY-NNNN |
| 13 | Workflow intervention (demarrer/terminer) | ✅ 100% | InterventionService + cascade statuts |
| 14 | Photos intervention (AVANT/APRES) | ✅ 100% | InterventionPhotoType + galerie groupee |
| 15 | Voters anti-IDOR | ✅ 100% | InterventionVoter + DemandeVoter |
| 16 | Dashboard par role + KPI | ✅ 100% | Redirection par role + compteurs dynamiques |
| 17 | Reporting 4 KPI | ✅ 100% | countByStatut, delaiMoyen, parTechnicien, parSitePriorite |
| 18 | Fixtures + README | ✅ 100% | doctrine-fixtures-bundle (pas Foundry, choix MVP) |
| 19 | Scenario demo + durcissement | 🔄 En cours | Tests manuels en cours, 4 corrections appliquees |

---

## 3. Points forts

- **Architecture propre** : separation controller / service / repository
- **Multi-tenant** : organisation_id sur chaque entite, filtre systematique
- **Securite en 2 couches** : access_control (role sur route) + Voters (ownership sur ressource)
- **Workflow metier** : cascades de statuts automatiques (demande suit intervention)
- **Numerotation unique** : service dedie avec prefixe + annee + sequence
- **Upload securise** : fichiers hors public/, servis par controller protege
- **Pagination uniforme** : 5 par page sur toutes les listes
- **Fixtures realistes** : 15 users, 100 equipements, 40 demandes, 24 interventions

---

## 4. Points d'attention / Dettes techniques

| # | Sujet | Severite | Detail |
|---|-------|----------|--------|
| 1 | Numerotation non transactionnelle | Faible (MVP) | En cas d'acces concurrent, collision possible. Post-MVP : table counters avec verrou |
| 2 | Pas de tests automatises | Moyenne | Aucun PHPUnit/Panther. Acceptable en MVP mais a ajouter avant production |
| 3 | N+1 potentiel | Faible | Pas de JOIN FETCH dans les QueryBuilder. A verifier via le Profiler |
| 4 | Pas de validation cote entite | Faible | Les contraintes sont dans les FormTypes, pas sur les entites (Assert) |
| 5 | Mailer en null:// | Normal (dev) | Emails non envoyes, visibles uniquement dans le profiler |
| 6 | Tailwind via CDN | Normal (MVP) | Pas de build CSS, acceptable en dev. Production : build avec Vite |
| 7 | Pas d'audit trail | Faible | Aucun log des changements de statut (qui a fait quoi, quand) |
| 8 | Mot de passe unique fixtures | Normal (dev) | Test1234! pour tous les comptes, acceptable en dev uniquement |

---

## 5. Securite

### Ce qui est en place
- [x] Login avec password hashe (bcrypt/argon2)
- [x] UserChecker bloque les comptes inactifs
- [x] Token d'invitation cryptographique (random_bytes)
- [x] Expiration token 48h
- [x] access_control sur toutes les routes
- [x] Voters anti-IDOR sur Demande et Intervention
- [x] Upload hors public/ avec controller protege
- [x] CSRF sur toutes les actions POST (delete, demarrer, terminer, valider)
- [x] Filtrage multi-tenant dans tous les repositories

### Ce qui manque (post-MVP)
- [ ] Rate limiting sur /login
- [ ] Validation MIME cote serveur (verifier le contenu reel, pas juste l'extension)
- [ ] CSP headers
- [ ] HTTPS force
- [ ] Audit log des actions sensibles

---

## 6. Compteurs finaux

| Element | Nombre |
|---------|--------|
| Entites | 9 |
| Controllers | 15 |
| Services | 3 |
| Voters | 2 |
| Enums | 5 |
| Form Types | 9 |
| Templates Twig | 48 |
| Fixtures | 9 |
| Migrations | 10 |
| Routes | ~35 |
| Lignes PHP estimees | ~3500 |
