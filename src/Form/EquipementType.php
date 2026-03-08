<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Batiment;
use App\Entity\CategorieEquipement;
use App\Entity\Equipement;
use App\Entity\Site;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EquipementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $organisation = $options['organisation'] ?? null;

        $builder
            ->add('nom')
            ->add('marque')
            ->add('modele')
            ->add('numeroDeSerie')
            ->add('statut')
            ->add('actif')
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
            ->add('categorie', EntityType::class, [
                'class' => CategorieEquipement::class,
                'choice_label' => 'nom',
                'required' => false,
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) use ($organisation) {
                    $qb = $er->createQueryBuilder('c');
                    if ($organisation) {
                        $qb->andWhere('c.organisation = :organisation')
                            ->setParameter('organisation', $organisation);
                    }
                    return $qb;
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Equipement::class,
            'organisation' => null,
        ]);
    }
}
