# Procédure Tests PHPUnit — GMAO

> Objectif : écrire et lancer des tests automatisés fiables sur le projet Symfony 8.
> Suis chaque étape dans l'ordre. Ne passe pas à l'étape suivante si la précédente échoue.

---

## PHASE 1 — Préparer l'environnement de test

### Étape 1.1 — Vérifier que PHPUnit est installé

```bash
php bin/phpunit --version
```

**Résultat attendu :** `PHPUnit 13.x.x`

Si erreur → relance `composer install`

---

### Étape 1.2 — Créer le fichier phpunit.xml.dist

Ce fichier configure PHPUnit pour ton projet. Crée-le à la racine du projet (`/GMAO/`) :

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
>
    <php>
        <ini name="display_errors" value="1"/>
        <ini name="error_reporting" value="-1"/>
        <server name="APP_ENV" value="test" force="true"/>
        <server name="SHELL_VERBOSITY" value="-1"/>
        <server name="SYMFONY_PHPUNIT_REMOVE" value=""/>
        <server name="SYMFONY_PHPUNIT_VERSION" value="11.4"/>
    </php>

    <testsuites>
        <testsuite name="Project Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>

    <source>
        <include>
            <directory suffix=".php">src</directory>
        </include>
    </source>
</phpunit>
```

---

### Étape 1.3 — Créer le fichier .env.test

Ce fichier configure la BASE DE DONNÉES de test (séparée de ta base principale).

Crée `.env.test` à la racine :

```dotenv
APP_ENV=test
APP_SECRET=test_secret_gmao_phpunit_2026

# Base de données DÉDIÉE aux tests — ne jamais pointer vers gmao !
DATABASE_URL="mysql://root:@127.0.0.1:3306/gmao_test?serverVersion=8.0&charset=utf8mb4"

MAILER_DSN=null://null
```

> ⚠️ CRITIQUE : le nom de la base DOIT être différent de ta base principale (`gmao`).
> Les tests purgent la base à chaque exécution. Si tu pointes sur `gmao`, tu perds toutes tes données.

---

### Étape 1.4 — Créer la base de données de test

```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test --no-interaction
```

**Résultat attendu :** `[OK] Successfully executed 1 migration.` (ou le nombre de migrations)

Si erreur de connexion → vérifie les credentials dans `.env.test`

---

### Étape 1.5 — Vérifier le bootstrap.php

Ouvre `tests/bootstrap.php` et vérifie qu'il contient au minimum :

```php
<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/.env')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG'] ?? true) {
    umask(0000);
}
```

Si le fichier est vide ou différent → remplace-le par le contenu ci-dessus.

---

### Étape 1.6 — Premier lancement à vide (smoke test)

```bash
php bin/phpunit
```

**Résultat attendu :** `No tests executed!` ou `OK (0 tests, 0 assertions)`

Si tu vois une erreur PHP/Symfony → corrige avant d'écrire le moindre test.

---

## PHASE 2 — Comprendre la structure des tests

### Architecture des dossiers

```
tests/
├── bootstrap.php              ← déjà là, ne pas toucher
├── Unit/                      ← tests unitaires (sans base de données)
│   └── Service/
│       └── InterventionServiceTest.php
├── Functional/                ← tests fonctionnels (avec WebTestCase + BDD)
│   ├── Controller/
│   │   ├── DemandeControllerTest.php
│   │   └── InterventionControllerTest.php
│   └── Security/
│       └── AccessControlTest.php
```

Crée ces dossiers manuellement :

```bash
mkdir -p tests/Unit/Service
mkdir -p tests/Functional/Controller
mkdir -p tests/Functional/Security
```

### Les deux types de tests que tu vas écrire

| Type | Classe parente | Base de données | Vitesse |
|------|---------------|-----------------|---------|
| Unitaire | `TestCase` | Non | Rapide |
| Fonctionnel | `WebTestCase` | Oui (purgée) | Lent |

---

## PHASE 3 — Écrire les tests unitaires

Les tests unitaires testent une classe **isolée**, sans Symfony, sans BDD.

### Étape 3.1 — Test de InterventionService

Crée `tests/Unit/Service/InterventionServiceTest.php` :

```php
<?php

namespace App\Tests\Unit\Service;

use App\Entity\Demande;
use App\Entity\Intervention;
use App\Enum\StatutDemande;
use App\Enum\StatutIntervention;
use App\Service\InterventionService;
use App\Service\NumberingService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class InterventionServiceTest extends TestCase
{
    private InterventionService $service;

    protected function setUp(): void
    {
        // On simule les dépendances avec des "mocks"
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturn(null);
        $em->method('flush')->willReturn(null);

        $numbering = $this->createMock(NumberingService::class);
        $numbering->method('generateNumero')->willReturn('INT-2026-0001');

        $this->service = new InterventionService($em, $numbering);
    }

    // ─── TEST 1 : demarrerIntervention ───────────────────────────────────────

    public function testDemarrerInterventionPlanifiee(): void
    {
        $intervention = new Intervention();
        $intervention->setStatut(StatutIntervention::PLANIFIE);

        $demande = new Demande();

        $this->service->demarrerIntervention($intervention, $demande);

        $this->assertSame(StatutIntervention::EN_COURS, $intervention->getStatut());
        $this->assertSame(StatutDemande::EN_COURS, $demande->getStatut());
        $this->assertNotNull($intervention->getDateDebut());
    }

    public function testDemarrerInterventionNonPlanifieeLeveException(): void
    {
        $intervention = new Intervention();
        $intervention->setStatut(StatutIntervention::A_PLANIFIER); // mauvais statut

        $demande = new Demande();

        $this->expectException(\LogicException::class);
        $this->service->demarrerIntervention($intervention, $demande);
    }

    // ─── TEST 2 : terminerIntervention ───────────────────────────────────────

    public function testTerminerInterventionEnCours(): void
    {
        $intervention = new Intervention();
        $intervention->setStatut(StatutIntervention::EN_COURS);
        $intervention->setDateDebut(new \DateTime('-1 hour'));
        $intervention->setCompteRendu('Réparation effectuée.');

        $demande = new Demande();

        $this->service->terminerIntervention($intervention, $demande);

        $this->assertSame(StatutIntervention::TERMINEE, $intervention->getStatut());
        $this->assertNotNull($intervention->getDateFin());
        $this->assertGreaterThan(0, $intervention->getDureeMinutes());
    }

    public function testTerminerSansCompteRenduLeveException(): void
    {
        $intervention = new Intervention();
        $intervention->setStatut(StatutIntervention::EN_COURS);
        $intervention->setDateDebut(new \DateTime());
        // pas de compte rendu

        $demande = new Demande();

        $this->expectException(\LogicException::class);
        $this->service->terminerIntervention($intervention, $demande);
    }
}
```

### Étape 3.2 — Lancer uniquement les tests unitaires

```bash
php bin/phpunit tests/Unit
```

**Résultat attendu :** `OK (4 tests, X assertions)`

Si un test échoue → lis le message d'erreur, corrige le service ou le test.

---

## PHASE 4 — Écrire les tests fonctionnels

Les tests fonctionnels simulent un navigateur. Ils vérifient les pages, les redirections, les accès.

### ⚠️ Prérequis avant d'écrire un seul test fonctionnel

Chaque test fonctionnel qui touche la BDD **doit** :
1. Étendre `WebTestCase`
2. Utiliser le trait `ResetDatabase` ou purger la BDD avant chaque test
3. Recharger les fixtures avant chaque test (ou utiliser des factories)

Méthode la plus simple : **recharger les fixtures au début de chaque classe de test**.

---

### Étape 4.1 — Test des accès (sécurité)

Crée `tests/Functional/Security/AccessControlTest.php` :

```php
<?php

namespace App\Tests\Functional\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccessControlTest extends WebTestCase
{
    // ─── TEST 1 : anonyme redirigé vers login ────────────────────────────────

    public function testAnonymeRedirigeSurDashboard(): void
    {
        $client = static::createClient();
        $client->request('GET', '/dashboard');

        $this->assertResponseRedirects('/login');
    }

    public function testAnonymeRedirigeSurDemandes(): void
    {
        $client = static::createClient();
        $client->request('GET', '/demande');

        $this->assertResponseRedirects('/login');
    }

    // ─── TEST 2 : login fonctionne ───────────────────────────────────────────

    public function testLoginAvecBonsIdentifiants(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $client->submitForm('Se connecter', [
            '_username' => 'admin@gmao.fr',
            '_password' => 'Test1234!',
        ]);

        // Après login réussi → redirection
        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testLoginAvecMauvaisMotDePasse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $client->submitForm('Se connecter', [
            '_username' => 'admin@gmao.fr',
            '_password' => 'mauvais_mdp',
        ]);

        // Reste sur /login avec erreur
        $this->assertRouteSame('app_login');
    }

    // ─── TEST 3 : ROLE_DEMANDEUR n'accède pas au dashboard ───────────────────

    public function testDemandeurNePeutPasVoirDashboard(): void
    {
        $client = static::createClient();

        // Connexion en tant que demandeur
        $client->request('GET', '/login');
        $client->submitForm('Se connecter', [
            '_username' => 'demandeur@gmao.fr',
            '_password' => 'Test1234!',
        ]);
        $client->followRedirect();

        // Tentative d'accès au dashboard
        $client->request('GET', '/dashboard');
        $this->assertResponseStatusCodeSame(403);
    }
}
```

> ⚠️ CRITIQUE : Pour que les tests fonctionnels trouvent les utilisateurs, la base de test
> doit contenir les fixtures. Charge-les avant de lancer :
> ```bash
> php bin/console doctrine:fixtures:load --env=test --no-interaction
> ```

---

### Étape 4.2 — Test du controller Demande

Crée `tests/Functional/Controller/DemandeControllerTest.php` :

```php
<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DemandeControllerTest extends WebTestCase
{
    private function loginAs(string $email): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $client->request('GET', '/login');
        $client->submitForm('Se connecter', [
            '_username' => $email,
            '_password' => 'Test1234!',
        ]);
        $client->followRedirects();
        return $client;
    }

    // ─── TEST 1 : liste des demandes accessible à l'admin ────────────────────

    public function testListeDemandesAdmin(): void
    {
        $client = $this->loginAs('admin@gmao.fr');
        $client->request('GET', '/demande');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('table'); // le tableau est affiché
    }

    // ─── TEST 2 : liste des demandes accessible au planificateur ─────────────

    public function testListeDemandesPlanificateur(): void
    {
        $client = $this->loginAs('planificateur@gmao.fr');
        $client->request('GET', '/demande');

        $this->assertResponseIsSuccessful();
    }

    // ─── TEST 3 : ROLE_TECHNICIEN redirigé vers ses interventions ────────────

    public function testTechnicienRedirigeSurMesInterventions(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');
        $client->submitForm('Se connecter', [
            '_username' => 'tech1@gmao.fr',
            '_password' => 'Test1234!',
        ]);
        $location = $client->getResponse()->headers->get('Location');

        // Le technicien doit être redirigé vers /mes-interventions
        $this->assertStringContainsString('mes-interventions', $location ?? '');
    }

    // ─── TEST 4 : isolation multi-tenant ─────────────────────────────────────
    // Un admin de org A ne doit pas voir les demandes de org B

    public function testAdminNePeutPasVoirDemandeAutreOrg(): void
    {
        // Connexion en tant qu'admin de "Maintenance Sud"
        $client = $this->loginAs('admin@maintenance-sud.fr');

        // Tente d'accéder à une demande de "GMAO Industries" (id=1)
        // Le voter doit refuser → 403
        $client->request('GET', '/demande/1');

        // Soit 403, soit redirect (selon voter)
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [403, 302]);
    }

    // ─── TEST 5 : création d'une demande ─────────────────────────────────────

    public function testCreerUneNouvelleDemandeAdmin(): void
    {
        $client = $this->loginAs('admin@gmao.fr');
        $client->request('GET', '/demande/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');

        // Soumet le formulaire
        $client->submitForm('Enregistrer', [
            'demande[titre]'      => 'Test PHPUnit',
            'demande[description]'=> 'Description test',
            'demande[priorite]'   => 'P2',
            // Ajoute les champs requis selon ton formulaire DemandeType
        ]);

        // Après soumission valide → redirect vers show ou index
        $this->assertResponseRedirects();
    }
}
```

---

### Étape 4.3 — Test du workflow intervention

Crée `tests/Functional/Controller/InterventionControllerTest.php` :

```php
<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class InterventionControllerTest extends WebTestCase
{
    private function loginAs(string $email): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $client->request('GET', '/login');
        $client->submitForm('Se connecter', [
            '_username' => $email,
            '_password' => 'Test1234!',
        ]);
        $client->followRedirects();
        return $client;
    }

    // ─── TEST 1 : liste des interventions ────────────────────────────────────

    public function testListeInterventionsAdmin(): void
    {
        $client = $this->loginAs('admin@gmao.fr');
        $client->request('GET', '/intervention');

        $this->assertResponseIsSuccessful();
    }

    // ─── TEST 2 : technicien accède à ses interventions ──────────────────────

    public function testTechnicienAccedeMesInterventions(): void
    {
        $client = $this->loginAs('tech1@gmao.fr');
        $client->request('GET', '/mes-interventions');

        $this->assertResponseIsSuccessful();
    }

    // ─── TEST 3 : demandeur ne peut pas lister toutes les interventions ───────

    public function testDemandeurNePeutPasVoirListeInterventions(): void
    {
        $client = $this->loginAs('demandeur@gmao.fr');
        $client->request('GET', '/intervention');

        $this->assertResponseStatusCodeSame(403);
    }

    // ─── TEST 4 : demarrer une intervention nécessite CSRF valide ────────────

    public function testDemarrerInterventionSansCsrfEchoue(): void
    {
        $client = $this->loginAs('tech1@gmao.fr');

        // Trouve une intervention PLANIFIEE dans les fixtures
        // (adapte l'ID selon tes fixtures)
        $client->request('POST', '/intervention/1/demarrer', [
            '_token' => 'token_invalide',
        ]);

        // Doit refuser (403 ou redirect avec erreur)
        $this->assertNotSame(200, $client->getResponse()->getStatusCode());
    }
}
```

---

## PHASE 5 — Les pièges à ne pas rater (CRITIQUE)

### ⚠️ Piège 1 — Mauvaise base de données

Vérifie TOUJOURS que `DATABASE_URL` dans `.env.test` pointe sur `gmao_test` et non `gmao`.

```bash
php bin/console doctrine:database:drop --env=test --force 2>/dev/null; \
php bin/console doctrine:database:create --env=test && \
php bin/console doctrine:migrations:migrate --env=test --no-interaction && \
php bin/console doctrine:fixtures:load --env=test --no-interaction
```

Lance cette commande **avant chaque session de tests** pour repartir d'une base propre.

---

### ⚠️ Piège 2 — Nom du bouton submit incorrect

Dans `$client->submitForm('Se connecter', [...])`, le premier argument doit correspondre
**exactement** au texte du bouton dans le template HTML.

Pour vérifier le vrai texte :

```bash
grep -r "type=\"submit\"" templates/security/
```

Si le bouton s'appelle "Connexion" → `submitForm('Connexion', [...])`.

---

### ⚠️ Piège 3 — Noms des champs du formulaire

Dans `submitForm`, les clés doivent suivre le format Symfony : `nomduform[nomduchampp]`.

Pour trouver le bon nom :

```bash
# Cherche le nom du form type
grep "class.*Type" src/Form/DemandeType.php
# Dans le template généré, inspecte le HTML ou cherche :
grep "form\[" templates/demande/
```

Exemple correct : `'demande[titre]'`, `'demande[priorite]'`.

---

### ⚠️ Piège 4 — CSRF dans les formulaires POST

Symfony génère un token CSRF automatiquement. Dans les tests fonctionnels avec `submitForm`,
le token est géré automatiquement. Mais si tu fais un `request('POST', ...)` manuel,
tu dois désactiver le CSRF dans l'env test ou récupérer le token depuis le HTML.

Pour désactiver le CSRF uniquement en test, ajoute dans `config/packages/framework.yaml` :

```yaml
when@test:
    framework:
        test: true
        session:
            storage_factory_id: session.storage.factory.mock_file
```

---

### ⚠️ Piège 5 — followRedirects vs followRedirect

- `$client->followRedirects()` (avec **s**) → active le suivi automatique de TOUTES les redirections
- `$client->followRedirect()` (sans **s**) → suit UNE seule redirection manuellement

Pour tester qu'une redirection a bien eu lieu, ne pas activer `followRedirects()`.
Pour naviguer jusqu'à la page finale, utilise `followRedirects()`.

---

### ⚠️ Piège 6 — Les IDs des fixtures ne sont pas fixes

Les IDs en base varient selon l'ordre de chargement. Ne jamais coder en dur `/demande/1`
sans vérifier. Préfère récupérer l'entité via le repository dans le test :

```php
$demande = static::getContainer()->get(DemandeRepository::class)
    ->findOneBy(['organisation' => /* ... */]);
$client->request('GET', '/demande/' . $demande->getId());
```

---

### ⚠️ Piège 7 — Environnement test vs dev

Symfony charge des configs différentes selon l'environnement. Si un service se comporte
différemment en test, vérifie `config/packages/` pour des surcharges `when@test`.

---

## PHASE 6 — Lancer les tests et lire les résultats

### Commandes utiles

```bash
# Lancer TOUS les tests
php bin/phpunit

# Lancer seulement les tests unitaires
php bin/phpunit tests/Unit

# Lancer seulement les tests fonctionnels
php bin/phpunit tests/Functional

# Lancer un fichier précis
php bin/phpunit tests/Unit/Service/InterventionServiceTest.php

# Lancer une méthode précise
php bin/phpunit --filter testDemarrerInterventionPlanifiee

# Afficher plus de détails
php bin/phpunit --testdox
```

### Lire les résultats

```
OK (12 tests, 34 assertions)   ← tout passe
FAILURES!                       ← au moins un test échoue
ERRORS!                         ← erreur PHP dans le test (pas une assertion)
```

Exemple d'échec :
```
FAILED: testDemarrerInterventionPlanifiee
Expected: App\Enum\StatutIntervention::EN_COURS
Actual:   App\Enum\StatutIntervention::PLANIFIE
```
→ Le service n'a pas changé le statut → bug dans `demarrerIntervention()`.

---

## PHASE 7 — Checklist finale avant de valider

Coche chaque point avant de considérer les tests terminés :

- [ ] `php bin/phpunit --version` affiche PHPUnit 13.x
- [ ] `.env.test` pointe sur `gmao_test` (base différente de `gmao`)
- [ ] La base de test existe et les migrations sont appliquées
- [ ] Les fixtures sont chargées en base test
- [ ] `php bin/phpunit tests/Unit` → 0 erreur, 0 failure
- [ ] `php bin/phpunit tests/Functional` → 0 erreur, 0 failure
- [ ] Le test d'isolation multi-tenant passe (org A ne voit pas org B)
- [ ] Le test de login avec mauvais mot de passe reste sur `/login`
- [ ] Le test de CSRF invalide retourne 403 ou erreur
- [ ] `php bin/phpunit` (tous les tests) → résultat vert

---

## Résumé des commandes dans l'ordre

```bash
# 1. Préparer la base de test
php bin/console doctrine:database:drop --env=test --force 2>/dev/null
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test --no-interaction
php bin/console doctrine:fixtures:load --env=test --no-interaction

# 2. Lancer les tests unitaires
php bin/phpunit tests/Unit

# 3. Lancer les tests fonctionnels
php bin/phpunit tests/Functional

# 4. Lancer tout
php bin/phpunit --testdox
```
