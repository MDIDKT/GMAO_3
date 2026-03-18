<?php

namespace App\Form;

use App\Entity\Batiment;
use App\Entity\Site;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BatimentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $organisation = $options['organisation'] ?? null;

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['maxlength' => 150, 'minlength' => 2, 'placeholder' => 'Ex: Bâtiment A'],
            ])
            ->add('etage', TextType::class, [
                'label' => 'Étage',
                'required' => false,
                'attr' => ['maxlength' => 50, 'placeholder' => 'Ex: RDC, 1er étage'],
            ])
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Batiment::class,
            'organisation' => null,
        ]);
    }
}
