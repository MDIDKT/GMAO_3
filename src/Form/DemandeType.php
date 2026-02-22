<?php

namespace App\Form;

use App\Entity\Batiment;
use App\Entity\Demande;
use App\Entity\Equipement;
use App\Entity\Organisation;
use App\Entity\Site;
use App\Entity\User;
use App\Enum\StatutDemande;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DemandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class)
            ->add('description', TextareaType::class)
            ->add('priorite')
            ->add('statut', EnumType::class, ['class' => StatutDemande::class])
            ->add('site', EntityType::class, [
                'class' => Site::class,
                'choice_label' => 'id',
            ])
            ->add('batiment', EntityType::class, [
                'class' => Batiment::class,
                'choice_label' => 'id',
            ])
            ->add('equipement', EntityType::class, [
                'class' => Equipement::class,
                'choice_label' => 'id',
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
            ->add('organisation', EntityType::class, [
                'class' => Organisation::class,
                'choice_label' => 'id',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Demande::class,
        ]);
    }
}
