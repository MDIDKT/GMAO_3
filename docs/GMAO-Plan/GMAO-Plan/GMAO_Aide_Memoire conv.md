GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026









Aide-memoire technique GMAO MVP · Symfony 8 · PHP 8.4 · Doctrine




A quoi sert ce document ?
C'est ta reference rapide pendant le developpement du MVP. Chaque section contient la syntaxe exacte, des exemples concrets et les pieges courants a eviter. Tu n'as plus besoin de naviguer dans la doc Symfony pour les operations courantes.



1. Commandes console essentielles
2. Entites Doctrine (creation + relations) 3. Enums PHP 8.4 + mapping Doctrine 4. Migrations
5. Repositories + QueryBuilder 6. Formulaires (FormType)
7. Security : firewall + access_control 8. Security : UserChecker
9. Security : Voters (ownership) 10. Upload de fichiers
11. Mailer (emails) 12. Twig essentials 13. KnpPaginator 14. Fixtures Foundry
15. Erreurs frequentes + solutions























Page 1
GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026


1. Commandes console essentielles

Les commandes que tu utiliseras tous les jours. A apprendre par coeur.


Projet

Demarrer le serveur

Vider le cache

Commande

symfony server:start

php bin/console cache:clear



Doctrine / Base de donnees

Creer la base

Supprimer la base

Creer une entite

Generer une migration

Executer les migrations

Voir le SQL d'une migration

Generateurs (make:)

Creer un controleur

Creer un formulaire

Creer un CRUD complet

Creer le systeme login

Creer une factory Foundry

Debug

Lister les routes

Voir la config securite

Voir les services

Hasher un mot de passe

Commande

php bin/console doctrine:database:create

php bin/console doctrine:database:drop --force

php bin/console make:entity NomEntite

php bin/console make:migration

php bin/console doctrine:migrations:migrate

php bin/console doctrine:migrations:migrate --dry-run

Commande

php bin/console make:controller NomController

php bin/console make:form NomType

php bin/console make:crud NomEntite

php bin/console make:security:form-login

php bin/console make:factory

Commande

php bin/console debug:router

php bin/console debug:config security

php bin/console debug:container NomService

php bin/console security:hash-password



























Page 2
GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026


2. Entites Doctrine (creation + relations)


2.1 Structure d'une entite

Une entite = une classe PHP qui represente une table en base de donnees.

src/Entity/Site.php

<?php
namespace App\Entity;

use App\Repository\SiteRepository; use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteRepository::class)] #[ORM\Table(name: 'site')]
class Site {
#[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column]
private ?int $id = null;

#[ORM\Column(length: 255)] private string $nom;

#[ORM\Column(type: 'boolean', options: ['default' => true])] private bool $actif = true;

#[ORM\Column(type: 'datetime_immutable')] private \DateTimeImmutable $createdAt;

// Getters + Setters (generes par make:entity) }



2.2 Types de colonnes les plus utilises


Type Doctrine	PHP

string	string

text	string

boolean	bool

integer	int

json	array

MySQL

VARCHAR(255)

LONGTEXT

TINYINT(1)

INT

JSON

Usage

Nom, email, titre

Description, CR

actif, visible

dureeMinutes, taille

roles []


datetime_immutable	DateTimeImmutable	DATETIME	createdAt, dateFin



2.3 Relations (les 3 que tu utiliseras)

ManyToOne — La plus courante (80% des cas). Plusieurs enfants pour 1 parent.

ManyToOne — le plus frequent

// Dans l'entite Site : plusieurs sites appartiennent a 1 organisation #[ORM\ManyToOne(targetEntity: Organisation::class)] #[ORM\JoinColumn(nullable: false)]
private Organisation $organisation;

// Nullable = le champ peut etre vide (ex: batiment optionnel) #[ORM\ManyToOne(targetEntity: Batiment::class)] #[ORM\JoinColumn(nullable: true)]
private ?Batiment $batiment = null;




Page 3
GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026


OneToMany — Le cote inverse (optionnel mais pratique pour lire les enfants).

OneToMany — cote inverse

// Dans l'entite Organisation : lire tous les sites de cette org use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\OneToMany(targetEntity: Site::class, mappedBy: 'organisation')] private Collection $sites;

public function __construct() {
$this->sites = new ArrayCollection(); }

public function getSites(): Collection {
return $this->sites; }


■ Piege : Le OneToMany n'est PAS obligatoire. Si tu n'as pas besoin de lire les enfants depuis le parent, ne le cree pas. Ca evite les problemes de performance (lazy loading / N+1).


2.4 Index (performance)

Index sur l'entite

#[ORM\Entity] #[ORM\Table(name: 'demande')]
#[ORM\Index(columns: ['organisation_id', 'statut'], name: 'idx_org_statut')] #[ORM\UniqueConstraint(columns: ['numero'], name: 'uq_numero')]
class Demande {
// ... }































Page 4
GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026


3. Enums PHP 8.4 + mapping Doctrine

Les enums remplacent les constantes. Symfony 8 / Doctrine 3 les supportent nativement.

src/Enum/StatutDemande.php

<?php
namespace App\Enum;

enum StatutDemande: string   // ': string' = string-backed enum {
case NOUVEAU = 'nouveau';
case A_QUALIFIER = 'a_qualifier'; case QUALIFIE = 'qualifie';
case PLANIFIE = 'planifie'; case EN_COURS = 'en_cours'; case CLOTURE = 'cloture'; case REJETEE = 'rejetee';

// Methode utile pour afficher un label propre en francais public function label(): string
{
return match($this) { self::NOUVEAU => 'Nouveau',
self::A_QUALIFIER => 'A qualifier', self::QUALIFIE => 'Qualifie', self::PLANIFIE => 'Planifie', self::EN_COURS => 'En cours', self::CLOTURE => 'Cloture', self::REJETEE => 'Rejetee',
}; }
}


Mapping dans l'entite Doctrine

Entite — colonne enum

// Dans l'entite Demande use App\Enum\StatutDemande;

#[ORM\Column(enumType: StatutDemande::class)]
private StatutDemande $statut = StatutDemande::NOUVEAU;


Dans un formulaire Symfony

FormType — champ enum

use Symfony\Component\Form\Extension\Core\Type\EnumType; use App\Enum\Priorite;

$builder->add('priorite', EnumType::class, [ 'class' => Priorite::class,
'choice_label' => fn(Priorite $p) => $p->label(), ]);


Dans Twig (affichage)


{{ demande.statut.label }}
{{ demande.statut.value }}

{# Affiche 'En cours' #}
{# Affiche 'en_cours' (brut) #}



Dans un QueryBuilder (filtre)

->andWhere('d.statut = :statut')
->setParameter('statut', StatutDemande::EN_COURS)




Page 5
GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026



✓ Astuce : Cree un fichier par enum dans src/Enum/. Ajoute toujours la methode label() — tu en auras besoin partout.






























































Page 6
GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026


4. Migrations

Une migration = un fichier SQL versionne qui modifie la structure de la base.

Workflow quotidien :

# 1. Tu modifies une entite (ajout champ, relation, index...) # 2. Tu generes la migration
php bin/console make:migration

# 3. Tu VERIFIES le fichier genere dans migrations/
#	-> Ouvre-le et lis le SQL. Si ca parait bizarre, ne l'execute pas.

# 4. Tu executes la migration
php bin/console doctrine:migrations:migrate


■ Piege : TOUJOURS verifier le fichier migration avant de l'executer. Doctrine peut generer un DROP TABLE si tu renommes un champ. En cas de doute, supprime le fichier migration et recommence.


Si la migration plante :

# Voir l'etat des migrations
php bin/console doctrine:migrations:status

# Marquer une migration comme executee (sans la jouer)
php bin/console doctrine:migrations:version --add 'DoctrineMigrations\Version20260101120000'

# Reset complet (dev uniquement !)
php bin/console doctrine:database:drop --force php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate php bin/console doctrine:fixtures:load



5. Repositories + QueryBuilder

Le repository = ta couche d'acces aux donnees. C'est LA qu'on ecrit les requetes, jamais dans le controleur.

5.1 Methodes simples (deja fournies)

// Recuperer par ID
$site = $siteRepository->find(42);

// Recuperer tout
$sites = $siteRepository->findAll();

// Recuperer avec criteres
$sites = $siteRepository->findBy(

['actif' => true, 'organisation' => $org], ['nom' => 'ASC'],
20, 0
);

// WHERE
// ORDER BY // LIMIT
// OFFSET


// Recuperer UN seul resultat
$site = $siteRepository->findOneBy(['nom' => 'Site Paris']);



5.2 QueryBuilder (requetes complexes)




Page 7
GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026


Utilise le QueryBuilder quand les filtres sont dynamiques (viennent d'un formulaire).

DemandeRepository.php — filtres dynamiques

// Dans DemandeRepository.php public function findByFilters(
Organisation $org, ?Site $site = null,
?StatutDemande $statut = null, ?Priorite $priorite = null, ?string $search = null
): QueryBuilder {
$qb = $this->createQueryBuilder('d')
->andWhere('d.organisation = :org') ->setParameter('org', $org)
->orderBy('d.createdAt', 'DESC');

if ($site !== null) {
$qb->andWhere('d.site = :site')
->setParameter('site', $site); }

if ($statut !== null) {
$qb->andWhere('d.statut = :statut')
->setParameter('statut', $statut); }

if ($priorite !== null) {
$qb->andWhere('d.priorite = :priorite')
->setParameter('priorite', $priorite); }

if ($search !== null && $search !== '') {
$qb->andWhere('d.titre LIKE :search OR d.description LIKE :search') ->setParameter('search', '%' . $search . '%');
}

return $qb;  // Retourne le QB, pas le resultat ! }


✓ Astuce : Retourne toujours le QueryBuilder, pas le resultat. Ca permet de le passer a KnpPaginator qui s'occupe du LIMIT/OFFSET.


5.3 JOIN FETCH (eviter N+1)

// Sans JOIN : 1 requete pour les demandes + 1 requete PAR demande pour le site = N+1 // Avec JOIN FETCH : 1 seule requete qui charge tout
$qb = $this->createQueryBuilder('d')
->leftJoin('d.site', 's')->addSelect('s')
->leftJoin('d.demandeur', 'u')->addSelect('u') ->andWhere('d.organisation = :org')
->setParameter('org', $org);



















Page 8
GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026


6. Formulaires (FormType)


6.1 Structure de base

src/Form/DemandeType.php

<?php
namespace App\Form;

use App\Entity\Demande; use App\Entity\Site; use App\Enum\Priorite;
use Symfony\Bridge\Doctrine\Form\Type\EntityType; use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType; use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DemandeType extends AbstractType {
public function buildForm(FormBuilderInterface $builder, array $options): void {
$builder
->add('titre', TextType::class, [ 'label' => 'Titre de la demande',
'attr' => ['placeholder' => 'Ex: Fuite eau bureau 302'], ])
->add('description', TextareaType::class, [ 'label' => 'Description detaillee', 'required' => false,
])
->add('site', EntityType::class, [ 'class' => Site::class, 'choice_label' => 'nom',
'placeholder' => '-- Choisir un site --', ])
->add('priorite', EnumType::class, [ 'class' => Priorite::class,
'choice_label' => fn(Priorite $p) => $p->label(), ]);
}

public function configureOptions(OptionsResolver $resolver): void {
$resolver->setDefaults([ 'data_class' => Demande::class,
]); }
}



6.2 Types de champs les plus utilises


Type

TextType

TextareaType

EntityType

EnumType

ChoiceType

FileType

RepeatedType

DateTimeType

Usage

Champ texte court

Texte long (description, CR)

Select lie a une entite (site, user...)

Select lie a un PHP enum

Select/radio/checkbox manuel

Upload fichier

Confirmation (mot de passe)

Date + heure

Import

...Type\TextType

...Type\TextareaType

Symfony\Bridge\Doctrine\Form\Type\EntityType

...Type\EnumType

...Type\ChoiceType

...Type\FileType

...Type\RepeatedType

...Type\DateTimeType




Page 9
GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026




6.3 Traitement dans le controleur

Controller — traitement formulaire

#[Route('/demandes/nouvelle', name: 'demande_new')]
public function new(Request $request, EntityManagerInterface $em): Response {
$demande = new Demande();
$form = $this->createForm(DemandeType::class, $demande); $form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) { $demande->setDemandeur($this->getUser());
$demande->setOrganisation($this->getUser()->getOrganisation()); $demande->setStatut(StatutDemande::A_QUALIFIER);
// ... appeler NumberingService, etc. $em->persist($demande);
$em->flush();

$this->addFlash('success', 'Demande creee avec succes.');
return $this->redirectToRoute('demande_show', ['id' => $demande->getId()]); }

return $this->render('demande/new.html.twig', [ 'form' => $form,
]); }


6.4 Upload fichier dans un formulaire

Champ upload (mapped: false)

// Dans le FormType (pas lie a l'entite !) $builder->add('photos', FileType::class, [
'label' => 'Photos (JPEG, PNG, max 5Mo)',

'multiple' => true, 'mapped' => false, 'required' => false, 'constraints' => [
new All([
new File([

// Permet plusieurs fichiers
// Le champ n'existe PAS dans l'entite

'maxSize' => '5M',
'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'], 'mimeTypesMessage' => 'Format non supporte.',
]) ])
], ]);





















Page 10
GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026


7. Security : firewall + access_control

Le fichier security.yaml est le coeur de la securite Symfony. Tu le touches les jours 2-3.

config/packages/security.yaml

# config/packages/security.yaml security:
password_hashers: Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'

providers: app_user_provider:
entity:
class: App\Entity\User
property: email	# Le champ utilise pour le login

firewalls: dev:
pattern: ^/(_(profiler|wdt))/
security: false	# Pas de securite pour le profiler

main:
lazy: true
provider: app_user_provider
user_checker: App\Security\UserChecker   # <-- Jour 3 form_login:
login_path: app_login check_path: app_login default_target_path: / enable_csrf: true
logout:
path: app_logout

# COUCHE 1 : protection par ROLE sur les ROUTES access_control:
- { path: ^/login, roles: PUBLIC_ACCESS }
- { path: ^/activation, roles: PUBLIC_ACCESS } - { path: ^/admin, roles: ROLE_ADMIN }
- { path: ^/reporting, roles: [ROLE_ADMIN, ROLE_PLANIFICATEUR] } - { path: ^/, roles: ROLE_USER } # Tout le reste : connecte


Ordre des regles access_control : Symfony teste les regles de haut en bas et s'arrete a la premiere qui matche. Donc ^/admin AVANT ^/, sinon /admin serait accessible a tous les connectes.


Verifier un role dans un controleur

// Methode 1 : attribut PHP 8 (prefere)
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PLANIFICATEUR')] #[Route('/reporting', name: 'reporting')]
public function reporting(): Response { /* ... */ }

// Methode 2 : dans le code
$this->denyAccessUnlessGranted('ROLE_ADMIN');














Page 11
GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026


8. Security : UserChecker

Le UserChecker permet de bloquer un user AVANT qu'il ne soit connecte (ex: compte inactif).

src/Security/UserChecker.php

<?php
namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException; use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface {
public function checkPreAuth(UserInterface $user): void {
if (!$user instanceof User) { return;
}

if (!$user->isActif()) {
throw new CustomUserMessageAccountStatusException(
'Votre compte est desactive. Contactez votre administrateur.' );
} }

public function checkPostAuth(UserInterface $user): void {
// Rien pour le MVP }
}


■ Piege : Le UserChecker doit etre declare dans le firewall (user_checker: App\Security\UserChecker), PAS dans le provider. C'est l'erreur #1.




9. Security : Voters (ownership)

Le Voter = couche 2 de securite. Il verifie que le user a le droit d'agir sur cette ressource precise.
























Page 12
GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026



src/Security/Voter/InterventionVoter.php

<?php
namespace App\Security\Voter;

use App\Entity\Intervention; use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface; use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class InterventionVoter extends Voter {
public const VIEW = 'INTERVENTION_VIEW'; public const EDIT = 'INTERVENTION_EDIT';
public const DEMARRER = 'INTERVENTION_DEMARRER'; public const CLOTURER = 'INTERVENTION_CLOTURER';

protected function supports(string $attribute, mixed $subject): bool {
return in_array($attribute, [self::VIEW, self::EDIT, self::DEMARRER, self::CLOTURER]) && $subject instanceof Intervention;
}

protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool {
$user = $token->getUser();
if (!$user instanceof User) { return false;
}

/** @var Intervention $intervention */ $intervention = $subject;

// Verif multi-tenant : meme organisation
if ($intervention->getOrganisation() !== $user->getOrganisation()) { return false;
}

// Admin et Planificateur : acces total dans leur org if (in_array('ROLE_ADMIN', $user->getRoles())
|| in_array('ROLE_PLANIFICATEUR', $user->getRoles())) { return true;
}

// Technicien : seulement SI il est assigne a cette intervention return $intervention->getTechnicien() === $user;
} }


Utilisation dans le controleur :

// TOUJOURS verifier avant d'afficher ou de modifier
$this->denyAccessUnlessGranted('INTERVENTION_VIEW', $intervention);

// Pour demarrer
$this->denyAccessUnlessGranted('INTERVENTION_DEMARRER', $intervention);


Dans Twig (masquer un bouton) :

{% if is_granted('INTERVENTION_DEMARRER', intervention) %}
<a href="{{ path('intervention_demarrer', {id: intervention.id}) }}">Demarrer</a> {% endif %}


■ Piege : Masquer un bouton en Twig ne suffit PAS. Le user peut taper l'URL directement. Toujours verifier dans le controleur avec denyAccessUnlessGranted().







Page 13
GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026


10. Upload de fichiers


10.1 Service d'upload

src/Service/FileUploadService.php

<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploadService {
public function __construct(
private string $uploadDirectory  // Injecte via services.yaml ) {}

public function upload(UploadedFile $file): string {
// Generer un nom unique pour eviter les collisions $newFilename = uniqid() . '.' . $file->guessExtension();

// Deplacer dans le dossier d'upload
$file->move($this->uploadDirectory, $newFilename);

return $newFilename; }
}



10.2 Configuration du service

services.yaml

# config/services.yaml parameters:
upload_directory: '%kernel.project_dir%/var/uploads/photos'

services: App\Service\FileUploadService:
arguments:
$uploadDirectory: '%upload_directory%'



10.3 Traitement dans le controleur

Controller — traitement upload

// Recuperer les fichiers uploades (champ 'mapped: false') $photoFiles = $form->get('photos')->getData();

if ($photoFiles) {
foreach ($photoFiles as $photoFile) {
$filename = $fileUploadService->upload($photoFile);

$photo = new Photo();
$photo->setFilename($filename);
$photo->setOriginalName($photoFile->getClientOriginalName()); $photo->setMimeType($photoFile->getMimeType());
$photo->setTaille($photoFile->getSize()); $photo->setType(TypePhoto::SIGNALEMENT); $photo->setDemande($demande);
$photo->setUploadePar($this->getUser());

$em->persist($photo); }
}





Page 14
GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026


10.4 Servir un fichier protege

Controller — servir une photo protegee

#[Route('/photos/{id}', name: 'photo_show')]
public function showPhoto(Photo $photo): BinaryFileResponse {
// Verifier les droits via le Voter
$this->denyAccessUnlessGranted('PHOTO_VIEW', $photo);

$filePath = $this->getParameter('upload_directory') . '/' . $photo->getFilename();

return new BinaryFileResponse($filePath); }




















































Page 15
GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026


11. Mailer (emails)


Envoi d'email avec template Twig

<?php
// Dans le controleur d'invitation
use Symfony\Component\Mailer\MailerInterface; use Symfony\Bridge\Twig\Mime\TemplatedEmail;

public function invite(MailerInterface $mailer, /* ... */): Response {
// ... creer le user avec token ...

$email = (new TemplatedEmail()) ->from('noreply@gmao.dev') ->to($user->getEmail())
->subject('Invitation a rejoindre la GMAO') ->htmlTemplate('email/invitation.html.twig') ->context([
'user' => $user,
'activationUrl' => $this->generateUrl('app_activation', [ 'token' => $user->getInvitationToken()
], UrlGeneratorInterface::ABSOLUTE_URL), ]);

$mailer->send($email); }


Configuration dev :

# .env.local MAILER_DSN=null://null
# Les emails ne sont PAS envoyes. Ils sont visibles dans le profiler Symfony # (barre de debug > icone enveloppe).


✓ Astuce : En dev, ouvre le profiler Symfony (barre noire en bas). L'icone enveloppe te montre tous les emails envoyes sans qu'ils partent reellement.




12. Twig essentials


12.1 Syntaxe de base

{{ variable }}	{# Afficher une valeur #} {% if condition %}...{% endif %} {# Condition #}
{% for item in items %}...{% endfor %}  {# Boucle #} {# Ceci est un commentaire Twig #}


12.2 Heritage de templates (layout)













Page 16
GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026



templates/base.html.twig

{# templates/base.html.twig #} <!DOCTYPE html>
<html> <head>
<script src="https://cdn.tailwindcss.com"></script> <title>{% block title %}GMAO{% endblock %}</title>
</head>
<body class="bg-gray-50">
{% include '_navbar.html.twig' %}

<main class="max-w-7xl mx-auto px-4 py-8">
{% for message in app.flashes('success') %}
<div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ message }}</div> {% endfor %}

{% block body %}{% endblock %} </main>
</body> </html>


templates/demande/index.html.twig

{# templates/demande/index.html.twig #} {% extends 'base.html.twig' %}

{% block title %}Demandes{% endblock %}

{% block body %}
<h1 class="text-2xl font-bold mb-6">Liste des demandes</h1>

{% for demande in demandes %}
<div class="bg-white shadow rounded p-4 mb-3">
<span class="font-mono text-sm text-blue-600">{{ demande.numero }}</span> <span class="font-semibold">{{ demande.titre }}</span>
<span class="text-sm text-gray-500">{{ demande.statut.label }}</span> </div>
{% else %}
<p class="text-gray-500">Aucune demande.</p> {% endfor %}
{% endblock %}



12.3 Fonctions Twig utiles


Fonction

path()

app.user

is_granted()

app.flashes()

|date()

Usage

Generer une URL

User connecte

Verifier un role/droit

Messages flash

Formater une date

Exemple

path('demande_show', {id: d.id})

{{ app.user.nom }}

is_granted('ROLE_ADMIN')

app.flashes('success')

d.createdAt|date('d/m/Y H:i')



12.4 Afficher un formulaire

{{ form_start(form, {'attr': {'class': 'space-y-4'}}) }} <div>
{{ form_label(form.titre, null, {'label_attr': {'class': 'block font-medium text-sm'}}) }} {{ form_widget(form.titre, {'attr': {'class': 'w-full border rounded p-2'}}) }}
{{ form_errors(form.titre) }} </div>
{# ... autres champs ... #}
<button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Creer</button> {{ form_end(form) }}



Page 17
GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026


13. KnpPaginator


Installation :

composer require knplabs/knp-paginator-bundle


Dans le controleur :

Controller — pagination

use Knp\Component\Pager\PaginatorInterface;

#[Route('/demandes', name: 'demande_index')] public function index(
Request $request, DemandeRepository $repo, PaginatorInterface $paginator
): Response {
// Recuperer le QueryBuilder (pas le resultat !) $qb = $repo->findByFilters(
org: $this->getUser()->getOrganisation(), site: $request->query->get('site'),
// ... autres filtres );

// Paginer : le paginator ajoute LIMIT/OFFSET automatiquement $pagination = $paginator->paginate(

$qb,
$request->query->getInt('page', 1), 20
);

// Le QueryBuilder // Page courante
// Nombre par page


return $this->render('demande/index.html.twig', [ 'pagination' => $pagination,
]); }


Dans Twig :

{% for demande in pagination %}
{# ... afficher la demande ... #} {% endfor %}

{# Navigation pages #}
<div class="mt-6 flex justify-center">
{{ knp_pagination_render(pagination) }} </div>




14. Fixtures Foundry


composer require --dev zenstruck/foundry


Creer une Factory :










Page 18
GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026



src/Factory/SiteFactory.php

<?php
namespace App\Factory;

use App\Entity\Site;
use App\Entity\Organisation;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
* @extends PersistentProxyObjectFactory<Site> */
final class SiteFactory extends PersistentProxyObjectFactory {
public static function class(): string {
return Site::class; }

protected function defaults(): array|callable {
return [
'nom' => self::faker()->company() . ' - Site ' . self::faker()->city(), 'adresse' => self::faker()->address(),
'actif' => true,
// organisation sera passe a la creation ];
} }


Dans les DataFixtures :

src/DataFixtures/AppFixtures.php

<?php
namespace App\DataFixtures;

use App\Factory\OrganisationFactory; use App\Factory\SiteFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture; use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture {
public function load(ObjectManager $manager): void {
// 1 organisation
$org = OrganisationFactory::createOne(['nom' => 'ACME Corp']);

// 3 sites
$sites = SiteFactory::createMany(3, ['organisation' => $org]);

// 1 admin UserFactory::createOne([
'email' => 'admin@gmao.dev', 'roles' => ['ROLE_ADMIN'], 'organisation' => $org, 'actif' => true,
]);

// 2 techniciens UserFactory::createMany(2, [
'roles' => ['ROLE_TECHNICIEN'], 'organisation' => $org,
'site' => fn() => $sites[array_rand($sites)], ]);
} }


# Charger les fixtures
php bin/console doctrine:fixtures:load



Page 19
GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026


15. Erreurs frequentes + solutions


An exception occurred in the driver: SQLSTATE[HY000] [2002] Connection refused

Cause : MySQL n'est pas demarre ou les identifiants dans .env.local sont incorrects.

Solution : Verifier que MySQL tourne. Verifier DATABASE_URL dans .env.local.


Class 'App\Entity\xxx' is not a valid entity or mapped super class

Cause : L'entite n'a pas l'attribut #[ORM\Entity] ou le namespace est incorrect.

Solution : Verifier que la classe a bien #[ORM\Entity(repositoryClass: ...)] en haut.


The file could not be found / Upload error

Cause : Le dossier d'upload n'existe pas.

Solution : Creer le dossier : mkdir -p var/uploads/photos


Access Denied (403) alors que le role est bon

Cause : L'ordre des regles access_control est mauvais, ou le Voter refuse.

Solution : Verifier l'ordre dans security.yaml. Tester avec ROLE_ADMIN d'abord.


Typed property must not be accessed before initialization

Cause : Un champ required n'a pas de valeur par defaut et n'est pas rempli.

Solution : Soit donner un default (= true, = '', etc.), soit rendre nullable (?type = null).


N+1 queries detected (Doctrine profiler)

Cause : Tu accedes a une relation dans une boucle Twig sans JOIN FETCH.

Solution : Ajouter ->leftJoin('d.site', 's')->addSelect('s') dans le QueryBuilder.


The token storage contains no authentication token

Cause : Tu essaies d'acceder a getUser() sur une route publique.

Solution : Verifier que la route est bien derriere le firewall (pas dans pattern: dev).


Cannot autowire argument $xxx

Cause : Un service/parametre n'est pas configure dans services.yaml.

Solution : Verifier le services.yaml. Pour les parametres scalaires, les declarer explicitement.




References documentation officielle



Sujet

Symfony 8.0 — Security

URL

https://symfony.com/doc/8.0/security.html



Symfony 8.0 — Voters

Symfony 8.0 — UserChecker

Symfony 8.0 — Upload

Symfony 8.0 — Mailer


https://symfony.com/doc/8.0/security/voters.html

https://symfony.com/doc/8.0/security/user_checkers.html

https://symfony.com/doc/8.0/controller/upload_file.html

https://symfony.com/doc/8.0/mailer.html





Page 20
GMAO MVP — Aide-memoire technique · Symfony 8.0 · PHP 8.4	v1.0 — 12/02/2026



Symfony 8.0 — Forms

Symfony 8.0 — Doctrine

Foundry

KnpPaginator

Tailwind CSS

PHP Enums


https://symfony.com/doc/8.0/forms.html

https://symfony.com/doc/8.0/doctrine.html

https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html

https://github.com/KnpLabs/KnpPaginatorBundle

https://tailwindcss.com/docs

https://www.php.net/manual/fr/language.enumerations.php
























































Page 21
