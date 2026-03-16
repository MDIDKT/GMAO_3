<?php

namespace App\Tests\Unit\Service;

use App\Entity\Demande;
use App\Entity\Intervention;
use App\Enum\StatutIntervention;
use App\Service\InterventionService;
use App\Service\NotificationService;
use App\Service\NumberingService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(InterventionService::class)]
final class InterventionServiceTest extends TestCase
{
    private InterventionService $service;

    public function testDemarrerPasseLeStatutEnCours(): void
    {
        $intervention = new Intervention();
        $demande = new Demande();
        $intervention->setStatut(StatutIntervention::PLANIFIE);

        $this->service->demarrerIntervention($intervention, $demande);

        $this->assertSame(StatutIntervention::EN_COURS, $intervention->getStatut(), 'votre statut est bien en cours');
    }

    public function testDemarrerLeveExceptionSiStatutInvalide(): void
    {
        $intervention = new Intervention();
        $demande = new Demande();
        $intervention->setStatut(StatutIntervention::A_PLANIFIER);

        $this->expectException(LogicException::class);
        $this->service->demarrerIntervention($intervention, $demande);
    }

    public function testTerminerVerifiePasseTerminer(): void
    {
        $intervention = new Intervention();
        $demande = new Demande();
        $demande->addIntervention($intervention);
        $intervention->setStatut(StatutIntervention::EN_COURS);
        $intervention->setDateDebut(new DateTimeImmutable('-1 hour'));
        $intervention->setCompteRendu('test');

        $this->service->terminerIntervention($intervention, $demande);

        $this->assertSame(StatutIntervention::TERMINEE, $intervention->getStatut(), 'votre statut est bien terminé');
    }

    public function testTerminerLeveExceptionSiStatutInvalide(): void
    {
        $intervention = new Intervention();
        $demande = new Demande();
        $intervention->setStatut(StatutIntervention::A_PLANIFIER);

        $this->expectException(LogicException::class);
        $this->service->terminerIntervention($intervention, $demande);
    }

    public function testTerminerLeveExceptionSiCompteRenduVide(): void
    {
        $intervention = new Intervention();
        $demande = new Demande();
        $intervention->setStatut(StatutIntervention::EN_COURS);
        $intervention->setCompteRendu('');

        $this->expectException(LogicException::class);
        $this->service->terminerIntervention($intervention, $demande);
    }

    protected function setUp(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $numbering = $this->createMock(NumberingService::class);
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->willReturn('https://example.test/fallback');
        $logger = $this->createStub(LoggerInterface::class);
        $notificationService = new NotificationService($mailer, $urlGenerator, 'noreply@example.test', $logger);
        $this->service = new InterventionService($em, $numbering, $notificationService);
    }
}
