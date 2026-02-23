<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\Index(name: 'IDX_USER_INVITATION_TOKEN', columns: ['invitation_token'])]
#[ORM\Index(name: 'IDX_USER_ORG_ACTIF', columns: ['organisation_id', 'actif'])]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;


    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $actif = false;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organisation $organisation = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?DateTimeImmutable $createdAt = null;
    #[ORM\Column(type: 'datetime_immutable')]
    private ?DateTimeImmutable $updatedAt = null;
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $invitationToken = null;
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $tokenExpiresAt = null;

    /**
     * @var Collection<int, Demande>
     */
    #[ORM\OneToMany(targetEntity: Demande::class, mappedBy: 'user')]
    private Collection $demandes;

    /**
     * @var Collection<int, Photo>
     */
    #[ORM\OneToMany(targetEntity: Photo::class, mappedBy: 'uploadPar', orphanRemoval: true)]
    private Collection $photos;

    public function __construct()
    {
        $this->demandes = new ArrayCollection();
        $this->photos = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $now = new DateTimeImmutable();

        if ($this->createdAt === null) {
            $this->createdAt = $now;
        }

        if ($this->updatedAt === null) {
            $this->updatedAt = $now;
        }
    }

    #[ORM\PreUpdate]
    public function refreshUpdatedAt(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getOrganisation(): ?Organisation
    {
        return $this->organisation;
    }

    public function setOrganisation(Organisation $organisation): static
    {
        if ($this->organisation === $organisation) {
            return $this;
        }

        $previousOrganisation = $this->organisation;
        $this->organisation = $organisation;

        if ($previousOrganisation !== null) {
            $previousOrganisation->getUsers()->removeElement($this);
        }

        if (!$organisation->getUsers()->contains($this)) {
            $organisation->getUsers()->add($this);
        }

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
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

    public function getInvitationToken(): ?string
    {
        return $this->invitationToken;
    }

    public function setInvitationToken(?string $invitationToken): static
    {
        $this->invitationToken = $invitationToken;

        return $this;
    }

    public function getTokenExpiresAt(): ?DateTimeImmutable
    {
        return $this->tokenExpiresAt;
    }

    public function setTokenExpiresAt(?DateTimeImmutable $tokenExpiresAt): static
    {
        $this->tokenExpiresAt = $tokenExpiresAt;

        return $this;
    }

    /**
     * @return Collection<int, Demande>
     */
    public function getDemandes(): Collection
    {
        return $this->demandes;
    }

    public function addDemande(Demande $demande): static
    {
        if (!$this->demandes->contains($demande)) {
            $this->demandes->add($demande);
            $demande->setUser($this);
        }

        return $this;
    }

    public function removeDemande(Demande $demande): static
    {
        if ($this->demandes->removeElement($demande)) {
            // set the owning side to null (unless already changed)
            if ($demande->getUser() === $this) {
                $demande->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Photo>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(Photo $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setUploadPar($this);
        }

        return $this;
    }

    public function removePhoto(Photo $photo): static
    {
        if ($this->photos->removeElement($photo)) {
            // set the owning side to null (unless already changed)
            if ($photo->getUploadPar() === $this) {
                $photo->setUploadPar(null);
            }
        }

        return $this;
    }
}
