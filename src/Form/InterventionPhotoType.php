<?php

namespace App\Form;

use App\Enum\TypePhoto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;

class InterventionPhotoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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
                            mimeTypesMessage: 'Seuls les formats JPEG, PNG et WebP sont acceptés.',
                        ),
                    ]),
                ],
            ])
        ->add('typePhoto', EnumType::class, [
            'class' => TypePhoto::class,
            'label' => 'Type de photo',
            'choices' => [TypePhoto::AVANT, TypePhoto::APRES, TypePhoto::CONSTAT],
            'choice_label' => fn($choice) => $choice->label(),
        ])
        ->add('legende', TextareaType::class, [
            'label' => 'Description (optionnel)',
            'required' => false,
            'mapped' => false,
            'attr' => [
                'rows' => 3,
                'maxlength' => 500,
                'placeholder' => 'Décrivez le constat observé...',
            ],
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
