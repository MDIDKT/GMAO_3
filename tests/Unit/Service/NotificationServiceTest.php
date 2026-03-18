<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Demande;
use App\Entity\Intervention;
use App\Entity\User;
use App\Service\NotificationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[CoversClass(NotificationService::class)]
final class NotificationServiceTest extends TestCase
{
    private NotificationService $service;
    private MailerInterface $mailer;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->willReturnCallback(
                static fn (string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string => sprintf(
                    'https://example.test/%s/%s',
                    $route,
                    $parameters['id'] ?? 'index'
                )
            );

        $logger = $this->createStub(LoggerInterface::class);
        $this->service = new NotificationService($this->mailer, $urlGenerator, 'noreply@example.test', $logger);

    }

    public function testNotifyTechnicienAssigneEnvoieUnEmailAuTechnicien(): void
    {
        $demande = $this->createDemande();
        $intervention = (new Intervention())
            ->setNumero('INT-2026-0001')
            ->setDemande($demande)
            ->setTechnicien($this->createUser('tech@example.test', 'Tech'))
            ->setPlanificateur($this->createUser('planif@example.test', 'Planif'));
        $this->setEntityId($intervention, 11);

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(function ($email): bool {
                if (!$email instanceof TemplatedEmail) {
                    return false;
                }

                self::assertSame('Nouvelle intervention assignée : INT-2026-0001', $email->getSubject());
                self::assertSame('tech@example.test', $email->getTo()[0]->getAddress());
                self::assertSame('email/intervention_assignee.html.twig', $email->getHtmlTemplate());
                self::assertSame('https://example.test/app_intervention_show/11', $email->getContext()['interventionUrl']);

                return true;
            }));

        $this->service->notifyTechnicienAssigne($intervention);
    }

    public function testNotifyTechnicienAssigneIgnoreSiAucunTechnicien(): void
    {
        $intervention = (new Intervention())->setNumero('INT-2026-0002');

        $this->mailer->expects(self::never())->method('send');

        $this->service->notifyTechnicienAssigne($intervention);
    }

    public function testNotifyInterventionDemarreeEnvoieUnEmailAuPlanificateur(): void
    {
        $demande = $this->createDemande();
        $intervention = (new Intervention())
            ->setNumero('INT-2026-0003')
            ->setDemande($demande)
            ->setTechnicien($this->createUser('tech@example.test', 'Tech'))
            ->setPlanificateur($this->createUser('planif@example.test', 'Planif'));
        $this->setEntityId($intervention, 12);

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(function ($email): bool {
                if (!$email instanceof TemplatedEmail) {
                    return false;
                }

                self::assertSame('planif@example.test', $email->getTo()[0]->getAddress());
                self::assertSame('email/intervention_demarree.html.twig', $email->getHtmlTemplate());
                self::assertSame('https://example.test/app_intervention_show/12', $email->getContext()['interventionUrl']);

                return true;
            }));

        $this->service->notifyInterventionDemarree($intervention);
    }

    public function testNotifyInterventionTermineeEnvoieUnEmailAuPlanificateur(): void
    {
        $demande = $this->createDemande();
        $intervention = (new Intervention())
            ->setNumero('INT-2026-0004')
            ->setDemande($demande)
            ->setTechnicien($this->createUser('tech@example.test', 'Tech'))
            ->setPlanificateur($this->createUser('planif@example.test', 'Planif'));
        $this->setEntityId($intervention, 13);

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(function ($email): bool {
                if (!$email instanceof TemplatedEmail) {
                    return false;
                }

                self::assertSame('planif@example.test', $email->getTo()[0]->getAddress());
                self::assertSame('email/intervention_terminee.html.twig', $email->getHtmlTemplate());
                self::assertSame('https://example.test/app_intervention_show/13', $email->getContext()['interventionUrl']);

                return true;
            }));

        $this->service->notifyInterventionTerminee($intervention);
    }

    public function testNotifyDemandeRejeteeEnvoieUnEmailAuDemandeur(): void
    {
        $demande = $this->createDemande('demandeur@example.test', 'Alice');
        $this->setEntityId($demande, 14);

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(function ($email): bool {
                if (!$email instanceof TemplatedEmail) {
                    return false;
                }

                self::assertSame('demandeur@example.test', $email->getTo()[0]->getAddress());
                self::assertSame('email/demande_rejetee.html.twig', $email->getHtmlTemplate());
                self::assertSame('https://example.test/app_demande_show/14', $email->getContext()['demandeUrl']);

                return true;
            }));

        $this->service->notifyDemandeRejetee($demande);
    }

    private function createDemande(string $email = 'demandeur@example.test', string $prenom = 'Alice'): Demande
    {
        return (new Demande())
            ->setNumero('DEM-2026-0001')
            ->setTitre('Fuite')
            ->setDescription('Fuite detectee')
            ->setUser($this->createUser($email, $prenom));
    }

    private function createUser(string $email, string $prenom): User
    {
        return (new User())
            ->setEmail($email)
            ->setPrenom($prenom)
            ->setNom('Test')
            ->setPassword('hashed-password');
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionProperty($entity, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($entity, $id);
    }
}
