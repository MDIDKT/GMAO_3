<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Demande;
use App\Entity\Intervention;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

final class NotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $fromAddress,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function notifyTechnicienAssigne(Intervention $intervention): void
    {
        $technicien = $intervention->getTechnicien();
        if (!$technicien instanceof User) {
            return;
        }

        $this->sendToUser(
            $technicien,
            'Nouvelle intervention assignee : ' . ($intervention->getNumero() ?? 'sans numero'),
            'email/intervention_assignee.html.twig',
            [
                'technicien' => $technicien,
                'intervention' => $intervention,
                'demande' => $intervention->getDemande(),
                'interventionUrl' => $this->generateInterventionUrl($intervention),
            ]
        );
    }

    public function notifyInterventionDemarree(Intervention $intervention): void
    {
        $planificateur = $intervention->getPlanificateur();
        if (!$planificateur instanceof User) {
            return;
        }

        $technicien = $intervention->getTechnicien();

        $this->sendToUser(
            $planificateur,
            'Intervention demarree : ' . ($intervention->getNumero() ?? 'sans numero'),
            'email/intervention_demarree.html.twig',
            [
                'planificateur' => $planificateur,
                'technicien' => $technicien,
                'intervention' => $intervention,
                'demande' => $intervention->getDemande(),
                'interventionUrl' => $this->generateInterventionUrl($intervention),
            ]
        );
    }

    public function notifyInterventionTerminee(Intervention $intervention): void
    {
        $planificateur = $intervention->getPlanificateur();
        if (!$planificateur instanceof User) {
            return;
        }

        $technicien = $intervention->getTechnicien();

        $this->sendToUser(
            $planificateur,
            'Intervention terminee : ' . ($intervention->getNumero() ?? 'sans numero'),
            'email/intervention_terminee.html.twig',
            [
                'planificateur' => $planificateur,
                'technicien' => $technicien,
                'intervention' => $intervention,
                'demande' => $intervention->getDemande(),
                'interventionUrl' => $this->generateInterventionUrl($intervention),
            ]
        );
    }

    public function notifyDemandeQualifiee(Demande $demande): void
    {
        $demandeur = $demande->getUser();
        if (!$demandeur instanceof User) {
            return;
        }

        $this->sendToUser(
            $demandeur,
            'Votre demande a ete qualifiee : ' . ($demande->getNumero() ?? 'sans numero'),
            'email/demande_qualifiee.html.twig',
            [
                'demandeur' => $demandeur,
                'demande' => $demande,
                'demandeUrl' => $this->generateDemandeUrl($demande),
            ]
        );
    }

    public function notifyDemandeCloturee(Demande $demande): void
    {
        $demandeur = $demande->getUser();
        if (!$demandeur instanceof User) {
            return;
        }

        $this->sendToUser(
            $demandeur,
            'Votre demande a ete traitee : ' . ($demande->getNumero() ?? 'sans numero'),
            'email/demande_cloturee.html.twig',
            [
                'demandeur' => $demandeur,
                'demande' => $demande,
                'demandeUrl' => $this->generateDemandeUrl($demande),
            ]
        );
    }

    public function notifyDemandeRejetee(Demande $demande): void
    {
        $demandeur = $demande->getUser();
        if (!$demandeur instanceof User) {
            return;
        }

        $this->sendToUser(
            $demandeur,
            'Votre demande a ete rejetee : ' . ($demande->getNumero() ?? 'sans numero'),
            'email/demande_rejetee.html.twig',
            [
                'demandeur' => $demandeur,
                'demande' => $demande,
                'demandeUrl' => $this->generateDemandeUrl($demande),
            ]
        );
    }

    private function sendToUser(User $user, string $subject, string $template, array $context): void
    {
        $emailAddress = trim((string)$user->getEmail());
        if ($emailAddress === '') {
            return;
        }
        try {
            $email = (new TemplatedEmail())
                ->from($this->fromAddress)
                ->to($emailAddress)
                ->subject($subject)
                ->htmlTemplate($template)
                ->context($context);

            $this->mailer->send($email);
        } catch (Throwable $e) {
            $this->logger->error('Email non envoyé à ' . $emailAddress . ' : ' . $e->getMessage());
        }
    }

    private function generateInterventionUrl(Intervention $intervention): string
    {
        $id = $intervention->getId();

        if ($id === null) {
            return $this->urlGenerator->generate('app_intervention_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return $this->urlGenerator->generate('app_intervention_show', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function generateDemandeUrl(Demande $demande): string
    {
        $id = $demande->getId();

        if ($id === null) {
            return $this->urlGenerator->generate('app_demande_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return $this->urlGenerator->generate('app_demande_show', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
