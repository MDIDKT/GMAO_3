<?php

namespace App\Entity;

use App\Enum\StatutEquipement;
use App\Repository\EquipementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EquipementRepository::class)]
class Equipement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $marque = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $modele = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numeroDeSerie = null;

    #[ORM\Column(enumType: StatutEquipement::class)]
    private ?StatutEquipement $statut = null;

    #[ORM\Column]
    private ?bool $actif = null;

    #[ORM\ManyToOne(inversedBy: 'equipements')]
    private ?Site $site = null;

    #[ORM\ManyToOne(inversedBy: 'equipements')]
    private ?Batiment $batiment = null;

    #[ORM\ManyToOne(inversedBy: 'equipements')]
    private ?CategorieEquipement $categorie = null;

    #[ORM\ManyToOne(inversedBy: 'equipements')]
    private ?Organisation $organisation = null;

    /**
     * @var Collection<int, Demande>
     */
    #[ORM\OneToMany(targetEntity: Demande::class, mappedBy: 'equipement')]
    private Collection $demandes;

    public function __construct()
    {
        $this->demandes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMarque(): ?string
    {
        return $this->marque;
    }

    public function setMarque(?string $marque): static
    {
        $this->marque = $marque;

        return $this;
    }

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(?string $modele): static
    {
        $this->modele = $modele;

        return $this;
    }

    public function getNumeroDeSerie(): ?string
    {
        return $this->numeroDeSerie;
    }

    public function setNumeroDeSerie(?string $numeroDeSerie): static
    {
        $this->numeroDeSerie = $numeroDeSerie;

        return $this;
    }

    public function getStatut(): ?StatutEquipement
    {
        return $this->statut;
    }

    public function setStatut(StatutEquipement $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

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

    public function getCategorie(): ?CategorieEquipement
    {
        return $this->categorie;
    }

    public function setCategorie(?CategorieEquipement $categorie): static
    {
        $this->categorie = $categorie;

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
            $demande->setEquipement($this);
        }

        return $this;
    }

    public function removeDemande(Demande $demande): static
    {
        if ($this->demandes->removeElement($demande)) {
            // set the owning side to null (unless already changed)
            if ($demande->getEquipement() === $this) {
                $demande->setEquipement(null);
            }
        }

        return $this;
    }
}
