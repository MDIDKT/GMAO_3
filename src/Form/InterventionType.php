<?php

namespace App\Form;

use App\Entity\Demande;
use App\Entity\Intervention;
use App\Entity\Organisation;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InterventionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $organisation = $options['organisation'];

        $builder
            ->add('datePlanifiee')
            ->add('dateDebut')
            ->add('dateFin')
            ->add('compteRendu')
            ->add('dureeMinutes')
            ->add('notes')
            ->add('statut')
            ->add('demande', EntityType::class, [
                'class' => Demande::class,
                'choice_label' => 'numero',
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) use ($organisation) {
                    $qb = $er->createQueryBuilder('d');
                    if ($organisation) {
                        $qb->andWhere('d.organisation = :organisation')
                            ->setParameter('organisation', $organisation);
                    }
                    return $qb;
                },
            ])
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
                    return $qb;
                },
            ])
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
                    return $qb;
                },
            ])
            ->add('organisation', EntityType::class, [
                'class' => Organisation::class,
                'choice_label' => 'nom',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Intervention::class,
            'organisation' => null,
        ]);
    }
}
