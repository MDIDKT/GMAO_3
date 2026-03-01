<?php

namespace App\Form;

use App\Entity\Batiment;
use App\Entity\Demande;
use App\Entity\Equipement;
use App\Entity\Site;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;

class DemandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $organisation = $options['organisation'] ?? null;

        $builder
            ->add('titre', TextType::class)
            ->add('description', TextareaType::class)
            ->add('priorite')
            ->add('site', EntityType::class, [
                'class' => Site::class,
                'choice_label' => 'nom',
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) use ($organisation) {
                    $qb = $er->createQueryBuilder('s');
                    if ($organisation) {
                        $qb->andWhere('s.organisation = :organisation')
                            ->setParameter('organisation', $organisation);
                    }
                    $qb->andWhere('s.actif = :actif')
                        ->setParameter('actif', true);
                    return $qb;
                },
            ])
            ->add('batiment', EntityType::class, [
                'class' => Batiment::class,
                'choice_label' => 'nom',
                'required' => false,
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) use ($organisation) {
                    $qb = $er->createQueryBuilder('b');
                    $qb->join('b.site', 's');
                    if ($organisation) {
                        $qb->andWhere('s.organisation = :organisation')
                            ->setParameter('organisation', $organisation);
                    }
                    $qb->andWhere('b.actif = :actif')
                        ->setParameter('actif', true);
                    return $qb;
                },
            ])
            ->add('equipement', EntityType::class, [
                'class' => Equipement::class,
                'choice_label' => 'nom',
                'required' => false,
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) use ($organisation) {
                    $qb = $er->createQueryBuilder('e');
                    if ($organisation) {
                        $qb->andWhere('e.organisation = :organisation')
                            ->setParameter('organisation', $organisation);
                    }
                    $qb->andWhere('e.actif = :actif')
                        ->setParameter('actif', true);
                    return $qb;
                },
            ])
            ->add('photos', FileType::class, [
                'label' => 'Photos (JPEG, PNG, max 5Mo)',
                'multiple' => true,
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
            'data_class' => Demande::class,
            'organisation' => null,
        ]);
    }
}
