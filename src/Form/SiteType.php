<?php

namespace App\Form;

use App\Entity\Site;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SiteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['maxlength' => 150, 'minlength' => 2, 'placeholder' => 'Ex: Siège social'],
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'attr' => ['maxlength' => 250, 'minlength' => 2, 'placeholder' => 'Ex: 12 rue de la Paix'],
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['pattern' => '[0-9]{5}', 'maxlength' => 5, 'placeholder' => 'Ex: 75001', 'title' => '5 chiffres'],
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'attr' => ['maxlength' => 100, 'minlength' => 2, 'placeholder' => 'Ex: Paris'],
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['maxlength' => 20, 'placeholder' => 'Ex: 01 23 45 67 89'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: contact@site.fr'],
            ])
            ->add('actif')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Site::class,
        ]);
    }
}
