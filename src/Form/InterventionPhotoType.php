<?php

namespace App\Form;

use App\Enum\TypePhoto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
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
                            mimeTypesMessage: 'Seuls les formats JPEG, PNG et WebP sont acceptes.',
                        ),
                    ]),
                ],
            ])
        ->add('typePhoto', EnumType::class,[
            'class' => TypePhoto::class,
                'choice_label' => 'label',
                'expanded' => true,
                'multiple' => true,
                'required' => true,
                'label' => 'Type de photo',
            ]
    )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
