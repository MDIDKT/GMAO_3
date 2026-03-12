<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class InterventionControllerTest extends WebTestCase
{
    public function testIndexRedirigeVersLoginSansAuthentification(): void
    {
        $client = static::createClient();
        $client->request('GET', '/intervention');

        $this->assertResponseRedirects('/login');
    }
}
