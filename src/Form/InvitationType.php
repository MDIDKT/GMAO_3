<?php

    namespace App\Form;


    use App\Entity\Organisation;
    use App\Entity\User;
    use Symfony\Bridge\Doctrine\Form\Type\EntityType;
    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
    use Symfony\Component\Form\Extension\Core\Type\EmailType;
    use Symfony\Component\Form\Extension\Core\Type\TextType;
    use Symfony\Component\Form\FormBuilderInterface;
    use Symfony\Component\OptionsResolver\OptionsResolver;
    use Symfony\Component\Validator\Constraints as Assert;

    class InvitationType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options): void
        {
            $builder
                ->add('email', EmailType::class, [
                    'label' => 'Adresse email',
//                    'constraints' => [
//                        new Assert\NotBlank(['message' => 'L\'email est obligatoire.']),
//                        new Assert\Email(['message' => 'Adresse email invalide.']),
//                    ],
                ])
                ->add('nom', TextType::class, [
                    'label' => 'Nom',
//                    'constraints' => [
//                        new Assert\NotBlank(['message' => 'Le nom est obligatoire.']),
//                    ],
                ])
                ->add('prenom', TextType::class, [
                    'label' => 'Prénom',
//                    'constraints' => [
//                        new Assert\NotBlank(['message' => 'Le prénom est obligatoire.']),
//                    ],
                ])
                ->add('roles', ChoiceType::class, [
                    'label' => 'Rôles',
                    'choices' => [
                        'Administrateur' => 'ROLE_ADMIN',
                        'Planificateur' => 'ROLE_PLANIFICATEUR',
                        'Technicien' => 'ROLE_TECHNICIEN',
                        'Demandeur' => 'ROLE_DEMANDEUR',
                    ],
                    'placeholder' => '-- Choisir un rôle --',
                    // Ce getter/setter gère la conversion string <-> array
                    // car en base roles est un JSON array ["ROLE_XXX"]
                    'getter' => function (User $user): ?string {
                        $roles = $user->getRoles();
                        // Retirer ROLE_USER (ajouté automatiquement par Symfony)
                        $filtered = array_filter($roles, fn($r) => $r !== 'ROLE_USER');
                        return $filtered ? reset($filtered) : null;
                    },
                    'setter' => function (User $user, ?string $role): void {
                        $user->setRoles($role ? [$role] : []);
                    },
                ])
                ->add('organisation', EntityType::class, [
                    'class' => Organisation::class,
                    'choice_label' => 'nom',
                    'label' => 'Organisation de rattachement',
                    'required' => false,
                    'placeholder' => '-- Aucun (toutes les organisations) --',
                ]);
        }

        public function configureOptions(OptionsResolver $resolver): void
        {
            $resolver->setDefaults([
                'data_class' => User::class,
            ]);
        }
    }
