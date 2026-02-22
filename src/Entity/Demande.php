<?php

namespace App\Entity;

use App\Enum\Priorite;
use App\Enum\StatutDemande;
use App\Repository\DemandeRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DemandeRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_DEMANDE_NUMERO', fields: ['numero'])]
#[ORM\HasLifecycleCallbacks]
class Demande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $description = null;

    #[ORM\Column(enumType: Priorite::class)]
    private ?Priorite $priorite = null;

    #[ORM\Column(enumType: StatutDemande::class)]
    private ?StatutDemande $statut = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'demandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Site $site = null;

    #[ORM\ManyToOne(inversedBy: 'demandes')]
    private ?Batiment $batiment = null;

    #[ORM\ManyToOne(inversedBy: 'demandes')]
    private ?Equipement $equipement = null;

    #[ORM\ManyToOne(inversedBy: 'demandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'demandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organisation $organisation = null;

    #[ORM\Column(length: 255)]
    private ?string $numero = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPriorite(): ?Priorite
    {
        return $this->priorite;
    }

    public function setPriorite(Priorite $priorite): static
    {
        $this->priorite = $priorite;

        return $this;
    }

    public function getStatut(): ?StatutDemande
    {
        return $this->statut;
    }

    public function setStatut(StatutDemande $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): static
    {
        $this->site = $site;

        return $this;
    }

    public function getBatiment(): ?Batiment
    {
        return $this->batiment;
    }

    public function setBatiment(?Batiment $batiment): static
    {
        $this->batiment = $batiment;

        return $this;
    }

    public function getEquipement(): ?Equipement
    {
        return $this->equipement;
    }

    public function setEquipement(?Equipement $equipement): static
    {
        $this->equipement = $equipement;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getOrganisation(): ?Organisation
    {
        return $this->organisation;
    }

    public function setOrganisation(?Organisation $organisation): static
    {
        $this->organisation = $organisation;

        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
