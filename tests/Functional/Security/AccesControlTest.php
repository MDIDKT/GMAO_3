<?php

    declare(strict_types=1);

    namespace App\Tests\Functional\Security;

    use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

    class AccesControlTest extends WebTestCase
    {
        public function testRedirectionVersLoginSiNonConnecter(): void
        {
            $page = static::createClient();
            $page->request('GET', '/dashboard');
            $this->assertResponseRedirects('/login');
        }

        public function testRedirectionInterventionSiNonConnecter(): void
        {
            $page = static::createClient();
            $page->request('GET', '/intervention');
            $this->assertResponseRedirects('/login');
        }

        public function testLoginAvecBonsIdentifiants(): void
        {
            $page = static::createClient();
            $page->request('GET', '/login');
            $page->submitForm('Se connecter', [
                '_username' => 'admin@gmao.fr',
                '_password' => 'Test1234!',
            ]);
            $this->assertResponseRedirects('/');
        }

        public function testLoginAvecMauvaisIdentifiants(): void
        {
            $page = static::createClient();
            $page->request('GET', '/login');
            $page->submitForm('Se connecter', [
                '_username' => 'toto',
                '_password' => 'tata',
            ]);
            $this->assertResponseRedirects('/login');
        }

        public function testAccesDashboardNonAutorise(): void
        {
            $page = static::createClient();
            $page->request('GET', '/login');
            $page->submitForm('Se connecter', [
                '_username' => 'demandeur@gmao.fr',
                '_password' => 'Test1234!',
            ]);
            $page->followRedirects();
            $page->request('GET', '/dashboard');
            $this->assertResponseStatusCodeSame(403);
        }
    }
