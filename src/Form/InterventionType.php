<?php

namespace App\Form;

use App\Entity\Intervention;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;

class InterventionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $organisation = $options['organisation'];

        $builder
            ->add('technicien', EntityType::class, [
                'class' => User::class,
                'required' => false,
                'choice_label' => function ($user) {
                    return $user->getNom() . ' ' . $user->getPrenom();
                },
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) use ($organisation) {
                    $qb = $er->createQueryBuilder('u');
                    if ($organisation) {
                        $qb->andWhere('u.organisation = :organisation')
                            ->setParameter('organisation', $organisation);
                    }
                    $qb->andWhere('u.roles LIKE :role')
                        ->setParameter('role', '%"ROLE_TECHNICIEN"%');
                    return $qb;
                },
            ])
            ->add('datePlanifiee')
            ->add('planificateur', EntityType::class, [
                'class' => User::class,
                'required' => false,
                'choice_label' => function ($user) {
                    return $user->getNom() . ' ' . $user->getPrenom();
                },
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) use ($organisation) {
                    $qb = $er->createQueryBuilder('u');
                    if ($organisation) {
                        $qb->andWhere('u.organisation = :organisation')
                            ->setParameter('organisation', $organisation);
                    }
                    $qb->andWhere('u.roles LIKE :role')
                        ->setParameter('role', '%"ROLE_PLANIFICATEUR"%');
                    return $qb;
                },
            ])
            ->add('compteRendu', TextareaType::class, [
                'required' => false,
                'label' => 'Compte rendu',
            ])
            ->add('photos', FileType::class, [
                'label' => 'Photos (JPEG, PNG, max 5Mo)',
                'multiple' => true,
                'choiceType' => 'image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new All([
                        new File(
                            maxSize: '5M',
                            mimeTypes: [
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ],
                            mimeTypesMessage: 'Seuls les formats JPEG, PNG et WebP sont acceptes.',
                        ),
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Intervention::class,
            'organisation' => null,
        ]);
    }
}
