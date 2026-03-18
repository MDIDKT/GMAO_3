<?php

namespace App\Entity;

use App\Enum\TypePhoto;
use App\Repository\PhotoRepository;
use App\Service\FileUploadService;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PhotoRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Photo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $fileName = null;

    #[ORM\Column(length: 255)]
    private ?string $originalName = null;

    #[ORM\Column(length: 255)]
    private ?string $mimeType = null;

    #[ORM\Column]
    private ?int $taille = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $legende = null;

    #[ORM\ManyToOne(inversedBy: 'photos')]
    private ?Demande $demande = null;

    #[ORM\ManyToOne(inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $uploadPar = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(enumType: TypePhoto::class)]
    private ?TypePhoto $typePhoto = null;

    #[ORM\ManyToOne(inversedBy: 'photos')]
    private ?Intervention $intervention = null;

    private ?FileUploadService $fileUploadService = null;

    public function __construct(?FileUploadService $fileUploadService = null)
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->fileUploadService = $fileUploadService;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): static
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): static
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getTaille(): ?int
    {
        return $this->taille;
    }

    public function setTaille(int $taille): static
    {
        $this->taille = $taille;

        return $this;
    }

    public function getLegende(): ?string
    {
        return $this->legende;
    }

    public function setLegende(?string $legende): static
    {
        $this->legende = $legende;

        return $this;
    }

    public function getDemande(): ?Demande
    {
        return $this->demande;
    }

    public function setDemande(?Demande $demande): static
    {
        $this->demande = $demande;

        return $this;
    }

    public function getUploadPar(): ?User
    {
        return $this->uploadPar;
    }

    public function setUploadPar(?User $uploadPar): static
    {
        $this->uploadPar = $uploadPar;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }

        if ($this->demande !== null && $this->intervention !== null) {
            throw new \LogicException('Une photo ne peut appartenir qu\'à une demande OU une intervention, pas les deux.');
        }
        if ($this->demande === null && $this->intervention === null) {
            throw new \LogicException('Une photo doit appartenir à une demande ou une intervention.');
        }
    }

    #[ORM\PreRemove]
    public function onPreRemove(): void
    {
        if ($this->fileUploadService !== null && $this->fileName !== null) {
            $this->fileUploadService->delete($this->fileName);
        }
    }

    public function getTypePhoto(): ?TypePhoto
    {
        return $this->typePhoto;
    }

    public function setTypePhoto(TypePhoto $typePhoto): static
    {
        $this->typePhoto = $typePhoto;

        return $this;
    }

    public function getIntervention(): ?Intervention
    {
        return $this->intervention;
    }

    public function setIntervention(?Intervention $intervention): static
    {
        $this->intervention = $intervention;

        return $this;
    }
}
