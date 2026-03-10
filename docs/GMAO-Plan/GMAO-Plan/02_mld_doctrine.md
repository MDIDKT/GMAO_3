# GMAO SaaS MVP — Modèle Logique de Données (MLD Doctrine)

## Vue d'ensemble des entités

Le modèle comporte **10 entités** organisées autour du flux métier : patrimoine → demande → intervention. Les photos sont rattachées directement aux demandes et aux interventions via des relations ManyToOne.

---

## Entités détaillées

### Organisation

C'est le tenant racine. En MVP mono-client, tu n'auras qu'une seule organisation, mais la structure est prête pour le multi-tenant.

| Champ | Type Doctrine | Nullable | Remarques |
|---|---|---|---|
| id | integer (PK, auto) | non | |
| nom | string(255) | non | |
| actif | boolean | non | default true |
| createdAt | datetime_immutable | non | auto via trait |
| updatedAt | datetime_immutable | non | auto via trait |

**Relations sortantes :** aucune (c'est la racine)
**Relations entrantes :** User, Site, CategorieEquipement, Equipement, Demande, Intervention (toutes ManyToOne vers Organisation)

---

### User

L'utilisateur de l'application. Les rôles sont stockés en JSON comme le fait Symfony nativement.

| Champ | Type Doctrine | Nullable | Remarques |
|---|---|---|---|
| id | integer (PK, auto) | non | |
| email | string(180), unique | non | sert d'identifiant de connexion |
| password | string(255) | non | hashé automatiquement par Symfony |
| nom | string(100) | non | |
| prenom | string(100) | non | |
| telephone | string(20) | oui | |
| roles | json | non | ex: ["ROLE_ADMIN"] |
| actif | boolean | non | default true |
| organisation_id | FK → Organisation | non | |
| createdAt | datetime_immutable | non | |
| updatedAt | datetime_immutable | non | |

**Relations :** ManyToOne vers Organisation. Est référencé par Demande (demandeur), Intervention (technicien, planificateur), Photo (uploadePar).

---

### Site

Un lieu physique à maintenir (adresse postale).

| Champ | Type Doctrine | Nullable | Remarques |
|---|---|---|---|
| id | integer (PK, auto) | non | |
| nom | string(255) | non | |
| adresse | text | oui | |
| ville | string(100) | oui | |
| codePostal | string(10) | oui | |
| contact | string(255) | oui | nom du contact sur site |
| telephone | string(20) | oui | |
| actif | boolean | non | default true |
| organisation_id | FK → Organisation | non | |
| createdAt | datetime_immutable | non | |
| updatedAt | datetime_immutable | non | |

**Relations sortantes :** ManyToOne vers Organisation
**Relations entrantes :** Batiment (OneToMany), Equipement (OneToMany), Demande (OneToMany)

---

### Batiment

Subdivision d'un site. Permet de localiser précisément un problème ou un équipement (Bâtiment A, Aile Nord, Sous-sol -2).

| Champ | Type Doctrine | Nullable | Remarques |
|---|---|---|---|
| id | integer (PK, auto) | non | |
| nom | string(255) | non | ex: "Bâtiment A" |
| etage | string(50) | oui | ex: "RDC", "Étage 3", "Sous-sol -1" |
| zone | string(100) | oui | ex: "Aile Nord", "Hall principal" |
| actif | boolean | non | default true |
| site_id | FK → Site | non | |
| createdAt | datetime_immutable | non | |
| updatedAt | datetime_immutable | non | |

**Relations :** ManyToOne vers Site. Référencé par Equipement (ManyToOne nullable).

---

### CategorieEquipement

Permet de classer les équipements par domaine technique.

| Champ | Type Doctrine | Nullable | Remarques |
|---|---|---|---|
| id | integer (PK, auto) | non | |
| nom | string(255) | non | ex: "Vidéosurveillance", "CVC" |
| description | text | oui | |
| organisation_id | FK → Organisation | non | |

**Relations :** ManyToOne vers Organisation. Référencé par Equipement.

---

### Equipement

Un objet technique identifiable et maintenable.

| Champ | Type Doctrine | Nullable | Remarques |
|---|---|---|---|
| id | integer (PK, auto) | non | |
| designation | string(255) | non | ex: "Caméra IP Hall entrée" |
| marque | string(100) | oui | |
| modele | string(100) | oui | |
| numeroSerie | string(100) | oui | |
| dateInstallation | date | oui | |
| statut | string(30) | non | enum: EN_SERVICE, HORS_SERVICE, EN_PANNE |
| localisation | string(255) | oui | description libre ("Bureau 302, mur sud") |
| notes | text | oui | |
| categorie_id | FK → CategorieEquipement | oui | |
| batiment_id | FK → Batiment | oui | |
| site_id | FK → Site | non | |
| organisation_id | FK → Organisation | non | |
| createdAt | datetime_immutable | non | |
| updatedAt | datetime_immutable | non | |

**Relations :** ManyToOne vers CategorieEquipement (nullable), Batiment (nullable), Site, Organisation. Référencé par Demande (ManyToOne nullable).

**Enum StatutEquipement** (PHP 8.4 backed enum) : EN_SERVICE, HORS_SERVICE, EN_PANNE

---

### Demande

Le signalement d'un problème par un demandeur.

| Champ | Type Doctrine | Nullable | Remarques |
|---|---|---|---|
| id | integer (PK, auto) | non | |
| numero | string(20), unique | non | auto-généré "DEM-2026-0001" |
| titre | string(255) | non | |
| description | text | non | |
| priorite | string(10) | non | enum: P1, P2, P3, P4 |
| statut | string(20) | non | enum (voir ci-dessous) |
| dateEcheance | date | oui | deadline souhaitée |
| motifRejet | text | oui | rempli si REJETEE |
| demandeur_id | FK → User | non | qui a créé la demande |
| site_id | FK → Site | non | |
| batiment_id | FK → Batiment | oui | précision optionnelle |
| equipement_id | FK → Equipement | oui | pas toujours connu |
| organisation_id | FK → Organisation | non | |
| createdAt | datetime_immutable | non | |
| updatedAt | datetime_immutable | non | |

**Relations sortantes :** ManyToOne vers User, Site, Batiment (nullable), Equipement (nullable), Organisation
**Relations entrantes :** Intervention (OneToMany), Photo (OneToMany)

**Enum StatutDemande** : NOUVEAU, A_QUALIFIER, QUALIFIE, PLANIFIE, EN_COURS, CLOTURE, REJETEE

**Enum Priorite** : P1, P2, P3, P4

---

### Intervention

L'action technique qui répond à une demande.

| Champ | Type Doctrine | Nullable | Remarques |
|---|---|---|---|
| id | integer (PK, auto) | non | |
| numero | string(20), unique | non | auto-généré "INT-2026-0001" |
| datePlanifiee | datetime_immutable | oui | fixée par le planificateur |
| dateDebut | datetime_immutable | oui | enregistrée quand le technicien démarre |
| dateFin | datetime_immutable | oui | enregistrée quand le technicien clôture |
| statut | string(20) | non | enum (voir ci-dessous) |
| compteRendu | text | oui | obligatoire pour clôturer |
| dureeMinutes | integer | oui | calculé auto (dateFin - dateDebut) |
| notes | text | oui | notes internes planificateur |
| demande_id | FK → Demande | non | |
| technicien_id | FK → User | oui | assigné par le planificateur |
| planificateur_id | FK → User | oui | qui a créé l'intervention |
| organisation_id | FK → Organisation | non | |
| createdAt | datetime_immutable | non | |
| updatedAt | datetime_immutable | non | |

**Relations sortantes :** ManyToOne vers Demande, User (×2), Organisation
**Relations entrantes :** Photo (OneToMany)

**Enum StatutIntervention** : A_PLANIFIER, PLANIFIE, EN_COURS, TERMINEE, VALIDEE

---

### Photo

Rattachée soit à une Demande, soit à une Intervention. Jamais aux deux en même temps. C'est le seul endroit où l'on gère les fichiers uploadés.

| Champ | Type Doctrine | Nullable | Remarques |
|---|---|---|---|
| id | integer (PK, auto) | non | |
| filename | string(255) | non | nom unique sur disque (ex: "abc123def.jpg") |
| originalName | string(255) | non | nom d'origine du fichier |
| mimeType | string(100) | non | ex: "image/jpeg" |
| taille | integer | non | en octets |
| type | string(30) | non | enum: SIGNALEMENT, AVANT, APRES, COMPLEMENT |
| legende | string(255) | oui | description courte optionnelle |
| demande_id | FK → Demande | oui | rempli si photo de demande |
| intervention_id | FK → Intervention | oui | rempli si photo d'intervention |
| uploadePar_id | FK → User | non | qui a uploadé |
| createdAt | datetime_immutable | non | |

**Contrainte métier :** exactement un des deux FK (demande_id ou intervention_id) doit être rempli. L'autre est null. Cette contrainte se valide dans le code, pas en base.

**Enum TypePhoto** : SIGNALEMENT (pour les demandes), AVANT, APRES, COMPLEMENT (pour les interventions)

---

## Récapitulatif des relations

| Depuis | Vers | Type | Nullable | Explication |
|---|---|---|---|---|
| User | Organisation | ManyToOne | non | un user appartient à une org |
| Site | Organisation | ManyToOne | non | un site appartient à une org |
| Batiment | Site | ManyToOne | non | un bâtiment est dans un site |
| CategorieEquipement | Organisation | ManyToOne | non | catégorie propre à l'org |
| Equipement | Site | ManyToOne | non | un équipement est sur un site |
| Equipement | Batiment | ManyToOne | oui | localisation optionnelle |
| Equipement | CategorieEquipement | ManyToOne | oui | catégorisation optionnelle |
| Equipement | Organisation | ManyToOne | non | isolation multi-tenant |
| Demande | User (demandeur) | ManyToOne | non | qui a signalé |
| Demande | Site | ManyToOne | non | où est le problème |
| Demande | Batiment | ManyToOne | oui | précision optionnelle |
| Demande | Equipement | ManyToOne | oui | pas toujours connu |
| Demande | Organisation | ManyToOne | non | isolation multi-tenant |
| Intervention | Demande | ManyToOne | non | répond à quelle demande |
| Intervention | User (technicien) | ManyToOne | oui | assigné plus tard |
| Intervention | User (planificateur) | ManyToOne | oui | qui a créé |
| Intervention | Organisation | ManyToOne | non | isolation multi-tenant |
| Photo | Demande | ManyToOne | oui | si photo de signalement |
| Photo | Intervention | ManyToOne | oui | si photo d'intervention |
| Photo | User (uploadePar) | ManyToOne | non | qui a uploadé |

---

## Les 5 Enums PHP 8.4

Tous les enums sont des **backed enums de type string**, mappés directement dans Doctrine avec l'option `enumType`.

**Priorite** — P1, P2, P3, P4

**StatutDemande** — NOUVEAU, A_QUALIFIER, QUALIFIE, PLANIFIE, EN_COURS, CLOTURE, REJETEE

**StatutIntervention** — A_PLANIFIER, PLANIFIE, EN_COURS, TERMINEE, VALIDEE

**StatutEquipement** — EN_SERVICE, HORS_SERVICE, EN_PANNE

**TypePhoto** — SIGNALEMENT, AVANT, APRES, COMPLEMENT

---

## Notes Doctrine importantes

**Trait TimestampableTrait** — À appliquer sur toutes les entités sauf Photo (qui n'a que createdAt). Ce trait utilise les lifecycle callbacks `PrePersist` et `PreUpdate` pour auto-remplir les timestamps. Penser à ajouter l'attribut `#[ORM\HasLifecycleCallbacks]` sur chaque entité qui utilise le trait.

**Numérotation auto** — Les numéros DEM-YYYY-XXXX et INT-YYYY-XXXX se génèrent dans un service dédié (pas dans l'entité). Le service requête le dernier numéro de l'année en cours et incrémente.

**Organisation sur chaque entité** — Même si c'est redondant avec la hiérarchie (Equipement → Site → Organisation), avoir organisation_id directement sur chaque entité simplifie énormément les requêtes de filtrage multi-tenant. C'est un compromis classique en SaaS.

**Upload photos** — Symfony recommande soit un service FileUploader maison (documentation officielle : symfony.com/doc/current/controller/upload_file.html), soit VichUploaderBundle. Pour l'apprentissage, le service maison est préférable car il t'oblige à comprendre le mécanisme complet.
