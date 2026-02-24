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

class DemandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class)
            ->add('description', TextareaType::class)
            ->add('priorite')
            ->add('site', EntityType::class, [
                'class' => Site::class,
                'choice_label' => 'nom',
            ])
            ->add('batiment', EntityType::class, [
                'class' => Batiment::class,
                'choice_label' => 'nom',
                'required' => false,
            ])
            ->add('equipement', EntityType::class, [
                'class' => Equipement::class,
                'choice_label' => 'nom',
                'required' => false,
            ])
            ->add('photos', FileType::class, [
                'label' => 'Photos (JPEG, PNG, max 5Mo)',
                'multiple' => true,
                'mapped' => false,
                'required' => false,
                'constraints' => [],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Demande::class,
        ]);
    }
}
