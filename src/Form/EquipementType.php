<?php

namespace App\Form;

use App\Entity\Batiment;
use App\Entity\CategorieEquipement;
use App\Entity\Equipement;
use App\Entity\Organisation;
use App\Entity\Site;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EquipementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('marque')
            ->add('modele')
            ->add('numeroDeSerie')
            ->add('statut')
            ->add('actif')
            ->add('site', EntityType::class, [
                'class' => Site::class,
                'choice_label' => 'id',
            ])
            ->add('batiment', EntityType::class, [
                'class' => Batiment::class,
                'choice_label' => 'id',
            ])
            ->add('categorie', EntityType::class, [
                'class' => CategorieEquipement::class,
                'choice_label' => 'id',
            ])
            ->add('organisation', EntityType::class, [
                'class' => Organisation::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Equipement::class,
        ]);
    }
}
