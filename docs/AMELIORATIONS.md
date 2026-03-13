sy# Plan d'ameliorations du MVP

Objectif : faire passer le projet de "MVP fonctionnel" a "base solide pour pre-production".

## 1. Priorite immediate

### Securite et multi-tenant

- Proteger l'acces aux photos par voter ou controle sur la ressource parente
- Verrouiller `show`, `edit`, `delete` sur les referentiels admin par organisation
- Supprimer les points d'entree qui permettent un acces par ID hors tenant

### Coherence des donnees

- Corriger les fixtures pour n'affecter les interventions qu'a de vrais techniciens et planificateurs
- Repasser tous les numeros de demo au format `DEM-YYYY-NNNN` et `INT-YYYY-NNNN`
- Repartir d'une base de demo propre avant toute recette ou demo

### Robustesse du modele

- Rendre obligatoires en Doctrine les relations imposees par le MLD : `Batiment.site`, `CategorieEquipement.organisation`, `Equipement.site`, `Equipement.organisation`
- Ajouter des contraintes `Assert` sur les entites principales, pas uniquement dans les formulaires

## 2. Phase de consolidation

### Tests

- Ajouter des tests fonctionnels sur les cas multi-tenant sensibles
- Ajouter des tests d'acces sur les routes photo
- Ajouter des tests sur les CRUD admin du referentiel
- Ajouter des tests sur les fixtures ou un jeu de demo de reference

### Qualite de code

- Centraliser l'expediteur mail dans toute l'application
- Aligner le dashboard livre avec le dashboard documente, ou simplifier officiellement le besoin
- Nettoyer les noms de routes et de documents pour rester coherents dans tout le projet

## 3. Phase pre-production

### Infrastructure

- Forcer HTTPS
- Ajouter un rate limiter sur `/login`
- Ajouter des headers de securite
- Stabiliser la numerotation si une creation concurrente devient probable

### Exploitation

- Mettre en place un vrai SMTP
- Preparer un reset standard de la base de demo
- Ajouter un mode de chargement fixtures "clean demo"

## 4. Phase produit

- Audit trail des changements de statut
- Notifications metier plus completes
- Export PDF / Excel
- API REST
- SLA / alertes retard

## 5. Ordre recommande

1. Securiser les photos et les CRUD multi-tenant.
2. Corriger les fixtures et remettre une base de demo propre.
3. Durcir le schema Doctrine et la validation metier.
4. Etendre la couverture de tests.
5. Travailler les sujets pre-production.

