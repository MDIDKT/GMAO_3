# Guide Pedagogique — Comprendre chaque partie du projet GMAO

> Ce document t'explique POURQUOI chaque choix technique a ete fait et COMMENT chaque partie fonctionne.
> Lis-le section par section, en ouvrant les fichiers concernes en parallele.

---

## 1. L'architecture globale : pourquoi cette organisation ?

```
src/
  Controller/     → Recoit les requetes HTTP, delegue au service, retourne une reponse
  Entity/         → Represente les tables en base de donnees (1 classe = 1 table)
  Enum/           → Valeurs fixes et limitees (comme un menu deroulant code en dur)
  Form/           → Definit les champs d'un formulaire HTML
  Repository/     → Contient les requetes SQL vers la base de donnees
  Security/       → Verification des droits d'acces (UserChecker, Voters)
  Service/        → Logique metier complexe (ce qui n'est ni requete ni affichage)
  DataFixtures/   → Donnees de test injectees en base
templates/        → Fichiers HTML (Twig) qui affichent les pages
config/           → Configuration de Symfony (securite, routes, services)
```

**Principe fondamental : Separation des responsabilites**
Chaque couche a UN seul role. Le controller ne fait jamais de SQL. Le repository ne fait jamais de logique metier. Le service ne fait jamais d'affichage.

---

## 2. Les Entites — Comment ca marche ?

### Pourquoi des entites ?
En Symfony/Doctrine, tu ne fais JAMAIS de SQL brut pour creer tes tables. Tu ecris des classes PHP avec des annotations, et Doctrine genere les tables automatiquement.

### Exemple concret : l'entite Demande

```php
#[ORM\Column(enumType: Priorite::class)]
private ?Priorite $priorite = null;
```

**Ce que ca veut dire :**
- `#[ORM\Column]` = "cette propriete correspond a une colonne en base"
- `enumType: Priorite::class` = "stocke la valeur de l'enum PHP dans la colonne"
- En base, ca stocke `p1_URGENTE` (string), mais en PHP tu manipules `Priorite::P1_URGENTE` (objet)

### Les relations

```php
#[ORM\ManyToOne(inversedBy: 'demandes')]
#[ORM\JoinColumn(nullable: false)]
private ?Organisation $organisation = null;
```

**Traduction :**
- `ManyToOne` = "plusieurs demandes peuvent appartenir a UNE organisation"
- `inversedBy: 'demandes'` = "cote Organisation, la propriete $demandes contient la liste inverse"
- `JoinColumn(nullable: false)` = "chaque demande DOIT avoir une organisation (NOT NULL en base)"

### Lifecycle Callbacks

```php
#[ORM\PrePersist]
public function initializeTimestamps(): void
```

**Pourquoi :** Quand Doctrine va inserer l'entite en base (persist), il appelle automatiquement cette methode AVANT l'INSERT. Ca evite d'oublier de mettre createdAt dans chaque controller.

---

## 3. Les Enums — Pourquoi pas juste des strings ?

### Le probleme sans enum
Si tu stockes juste `'P1_URGENTE'` comme string, rien n'empeche d'ecrire `'P1_URGNTE'` (faute de frappe). Le code compile, mais la logique est cassee.

### La solution avec enum

```php
enum Priorite: string
{
    case P1_URGENTE = 'p1_URGENTE';
    case P2_HAUTE = 'p2_HAUTE';

    public function label(): string
    {
        return match ($this) {
            self::P1_URGENTE => 'P1 — Urgente',
            self::P2_HAUTE => 'P2 — Haute',
        };
    }
}
```

**Avantages :**
- **Type-safe** : `Priorite::P1_URGENTE` est le SEUL moyen de designer cette valeur
- **Autocompletion** : ton IDE te propose les valeurs possibles
- **Methode label()** : un seul endroit pour gerer l'affichage humain
- **Doctrine** : stocke `p1_URGENTE` en base, reconstruit l'objet PHP a la lecture

---

## 4. Le pattern Repository — Pourquoi pas de SQL dans le controller ?

### Regle d'or : JAMAIS de requete SQL dans un controller

**Mauvais :**
```php
// Dans le controller — NE FAIS JAMAIS CA
$demandes = $entityManager->createQuery('SELECT d FROM Demande d WHERE d.statut = :s')
    ->setParameter('s', 'EN_COURS')
    ->getResult();
```

**Bien :**
```php
// Dans le controller
$demandes = $demandeRepository->findByFilters($organisation, $site, $statut);

// Dans le repository
public function findByFilters(Organisation $org, ?Site $site, ?StatutDemande $statut): array
{
    $qb = $this->createQueryBuilder('d')
        ->andWhere('d.organisation = :org')
        ->setParameter('org', $org);

    if ($site !== null) {
        $qb->andWhere('d.site = :site')->setParameter('site', $site);
    }
    // ...
}
```

**Pourquoi :**
- Si tu changes la requete, tu changes UN fichier (le repository), pas 5 controllers
- Les filtres se construisent dynamiquement avec des `if`
- Le controller reste lisible : il delegue, il ne fait pas le travail

---

## 5. Les Services — La logique metier isolee

### Pourquoi InterventionService existe ?

Quand un technicien termine une intervention, il se passe BEAUCOUP de choses :
1. Verifier que l'intervention est bien EN_COURS
2. Verifier que le compte rendu n'est pas vide
3. Passer le statut a TERMINEE
4. Enregistrer la date de fin
5. Calculer la duree en minutes
6. Verifier si TOUTES les interventions de la demande sont terminees
7. Si oui, passer la demande a CLOTURE

Si tu mets tout ca dans le controller, il fait 50 lignes. Et si tu as besoin de cette logique ailleurs (API future, commande CLI), tu dupliques le code.

**Le service encapsule la logique :**

```php
public function terminerIntervention(Intervention $intervention, Demande $demande): void
{
    // 1. Guard : verifications
    if ($intervention->getStatut() !== StatutIntervention::EN_COURS) {
        throw new LogicException('Intervention non demarree.');
    }
    if (empty($intervention->getCompteRendu())) {
        throw new LogicException('Compte-rendu obligatoire.');
    }

    // 2. Transition
    $intervention->setStatut(StatutIntervention::TERMINEE);
    $intervention->setDateFin(new DateTime());

    // 3. Calcul duree
    $duree = ($intervention->getDateFin()->getTimestamp() - $intervention->getDateDebut()->getTimestamp()) / 60;
    $intervention->setDureeMinutes((int) $duree);

    // 4. Cascade vers la demande
    $toutesTerminees = true;
    foreach ($demande->getInterventions() as $inter) {
        if (!in_array($inter->getStatut(), [StatutIntervention::TERMINEE, StatutIntervention::VALIDEE])) {
            $toutesTerminees = false;
            break;
        }
    }
    if ($toutesTerminees) {
        $demande->setStatut(StatutDemande::CLOTURE);
    }

    $this->entityManager->flush();
}
```

**Le controller reste simple :**
```php
$this->interventionService->terminerIntervention($intervention, $demande);
```

---

## 6. La securite en 2 couches

### Couche 1 : access_control (security.yaml)

```yaml
access_control:
    - { path: ^/admin, roles: ROLE_ADMIN }
    - { path: ^/intervention, roles: [ROLE_PLANIFICATEUR, ROLE_ADMIN, ROLE_TECHNICIEN] }
```

**Ce que ca fait :** Avant meme d'entrer dans le controller, Symfony verifie le ROLE du user. Si un DEMANDEUR tape `/intervention`, il recoit 403 immediatement.

**Limite :** Ca ne verifie que le ROLE, pas "est-ce que cette intervention m'appartient ?"

### Couche 2 : Voters (verification fine)

```php
class InterventionVoter extends Voter
{
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        $intervention = $subject;

        // Admin/Planif : acces total dans leur org
        if (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_PLANIFICATEUR', $user->getRoles())) {
            return $intervention->getOrganisation() === $user->getOrganisation();
        }

        // Technicien : seulement SES interventions
        if (in_array('ROLE_TECHNICIEN', $user->getRoles())) {
            return $intervention->getTechnicien() === $user;
        }

        return false;
    }
}
```

**Utilisation dans le controller :**
```php
$this->denyAccessUnlessGranted('INTERVENTION_VIEW', $intervention);
```

**Pourquoi 2 couches ?**
- `access_control` = filtre GROSSIER (role sur la route)
- Voter = filtre FIN (ownership sur la ressource)
- Sans le voter, un technicien pourrait acceder a `/intervention/42` meme si l'intervention 42 appartient a un autre technicien. C'est ce qu'on appelle une faille IDOR.

---

## 7. Le multi-tenant — Comment on isole les donnees

### Le principe
Chaque entite metier a un champ `organisation_id`. Quand un user se connecte, on recupere SON organisation et on filtre TOUT par cette organisation.

### Dans les controllers

```php
$currentUser = $this->getUser();
$organisation = $currentUser->getOrganisation();
// On passe $organisation au repository
$demandes = $demandeRepository->findByFilters($organisation, ...);
```

### Dans les formulaires (dropdowns)

```php
->add('site', EntityType::class, [
    'query_builder' => function (EntityRepository $er) use ($organisation) {
        return $er->createQueryBuilder('s')
            ->andWhere('s.organisation = :org')
            ->setParameter('org', $organisation);
    },
])
```

**Pourquoi `query_builder` ?** Sans ca, le dropdown afficherait TOUS les sites de TOUTES les organisations. Le `query_builder` filtre les options disponibles.

---

## 8. Le systeme d'invitation — Flux complet

### Pourquoi pas un simple "creer un compte" ?
En GMAO, c'est l'ADMIN qui decide qui a acces. Un employe ne s'inscrit pas tout seul.

### Le flux
1. **Admin** remplit le formulaire (email, nom, prenom, role)
2. Le controller genere un **token cryptographique** : `bin2hex(random_bytes(32))`
3. Le user est cree en base avec `actif = false` et un mot de passe aleatoire (temporaire)
4. Un email est envoye avec un lien `/activation/{token}`
5. **L'employe** clique sur le lien, definit son mot de passe
6. Son compte passe a `actif = true`, le token est efface

### Pourquoi effacer le token ?
Si on le garde, quelqu'un qui intercepte l'email pourrait reutiliser le lien pour changer le mot de passe plus tard.

---

## 9. La numerotation — DEM-2026-0001

### Le service NumberingService

```php
public function generateNumero(string $prefix): string
{
    $year = (int) date('Y');
    // Cherche le dernier numero en base pour ce prefixe et cette annee
    $last = $this->demandeRepository->findLastNumeroForPrefixAndYear($prefix, $year);
    // Incremente
    $next = $last ? ((int) substr($last, -4)) + 1 : 1;
    return sprintf('%s-%d-%04d', $prefix, $year, $next);
}
```

**Pourquoi un service et pas dans l'entite ?**
- L'entite ne doit pas connaitre la base de donnees (pas d'injection de repository dans une entite)
- Le service a acces au repository pour chercher le dernier numero
- Le format peut changer sans toucher l'entite

---

## 10. L'upload de photos — Pourquoi pas dans public/ ?

### Le probleme
Si tu mets les photos dans `public/uploads/`, n'importe qui peut y acceder directement via l'URL `https://monsite.com/uploads/photo123.jpg` — meme sans etre connecte.

### La solution
1. Les photos sont stockees dans `var/uploads/photos/` (HORS du dossier public)
2. Un controller protege sert les fichiers :

```php
#[Route('/photo/{id}', name: 'app_intervention_photo_show')]
public function showPhoto(Photo $photo): BinaryFileResponse
{
    // Verification des droits avant de servir le fichier
    $filePath = $this->getParameter('upload_directory') . '/' . $photo->getFileName();
    return new BinaryFileResponse($filePath);
}
```

3. Le voter verifie que le user a le droit de voir cette photo

---

## 11. La pagination — Pourquoi KnpPaginator ?

### Le probleme sans pagination
Si tu as 1000 demandes et tu fais `findAll()`, Doctrine charge les 1000 objets en memoire. La page met 5 secondes a charger.

### La solution
KnpPaginator prend un QueryBuilder (pas les resultats) et ne charge QUE les 5 elements de la page courante :

```php
$qb = $repository->getQueryBuilderByFilters($org, $site, $statut);
$pagination = $paginator->paginate($qb, $page, 5); // 5 par page
```

**Point cle :** On passe le QueryBuilder, PAS `$qb->getQuery()->getResult()`. Sinon la pagination ne marche pas (tous les resultats seraient deja charges en memoire).

---

## 12. Les fixtures — A quoi ca sert ?

### Le probleme
Chaque fois que tu vides ta base pour tester, tu dois recreer manuellement des sites, des users, des demandes... Ca prend 20 minutes.

### La solution
Les fixtures sont des scripts PHP qui inserent des donnees automatiquement :

```bash
php bin/console doctrine:fixtures:load --append
```

### L'ordre de chargement
Les fixtures ont des dependances (un User a besoin d'une Organisation qui existe deja) :

```php
class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [OrganisationFixtures::class]; // Charge les orgs EN PREMIER
    }
}
```

### Pourquoi doctrine-fixtures-bundle et pas Foundry ?
Le CDC demandait Foundry (qui genere des donnees aleatoires avec Faker). Pour le MVP, on a utilise doctrine-fixtures-bundle avec des donnees STATIQUES (ecrites en dur). Avantage : les donnees sont previsibles et reproductibles. Inconvenient : moins flexible. Foundry serait utile en post-MVP pour generer 1000 demandes de test.

---

## 13. Le workflow de statuts — Comment ca cascade

### Le cycle de vie d'une demande

```
NOUVEAU → A_QUALIFIER → QUALIFIE → PLANIFIE → EN_COURS → CLOTURE
                                                          ↘ REJETEE
```

### Les transitions automatiques
- Quand on CREE une intervention liee a une demande → la demande passe a `PLANIFIE`
- Quand le technicien DEMARRE l'intervention → la demande passe a `EN_COURS`
- Quand TOUTES les interventions sont TERMINEE/VALIDEE → la demande passe a `CLOTURE`

### Ou est le code ?
Tout est dans `InterventionService.php`. C'est le service qui orchestre les transitions. Le controller ne fait qu'appeler le service.

---

## 14. Twig — Comment marchent les templates

### L'heritage de templates

```
base.html.twig           → HTML de base (head, body, scripts)
  └── layouts/app.html.twig  → Layout app (sidebar, header, contenu)
        └── demande/index.html.twig  → Page specifique
```

Chaque template definit des `blocks` que les enfants peuvent surcharger :

```twig
{# Dans app.html.twig #}
{% block page_content %}{% endblock %}

{# Dans demande/index.html.twig #}
{% block page_content %}
    <h1>Liste des demandes</h1>
    ...
{% endblock %}
```

### Les filtres Twig utiles
- `{{ date|date('d/m/Y') }}` → formate une date
- `{{ text|slice(0, 70) ~ '...' }}` → tronque un texte
- `{{ demande.priorite.label() }}` → appelle la methode label() de l'enum

---

## 15. Resume : les 5 patterns a retenir

| Pattern | Ou | Pourquoi |
|---------|-----|---------|
| **Repository** | Toute requete DB | Centralise les requetes, evite la duplication |
| **Service** | Logique metier | Isole la logique, reutilisable partout |
| **Voter** | Protection des ressources | Empeche l'acces non autorise (IDOR) |
| **Enum** | Valeurs fixes | Type-safety, pas de fautes de frappe |
| **DependentFixture** | Donnees de test | Ordre de chargement garanti |
