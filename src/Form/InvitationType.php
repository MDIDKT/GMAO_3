<?php

    namespace App\Form;

    use App\Entity\User;
    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
    use Symfony\Component\Form\Extension\Core\Type\EmailType;
    use Symfony\Component\Form\Extension\Core\Type\TextType;
    use Symfony\Component\Form\FormBuilderInterface;
    use Symfony\Component\OptionsResolver\OptionsResolver;
    use Symfony\Component\Validator\Constraints\Email;
    use Symfony\Component\Validator\Constraints\NotBlank;

    class InvitationType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options): void
        {
            $builder
                ->add('email', EmailType::class, [
                    'label' => 'Adresse email',
                    'constraints' => [
                        new NotBlank(message: 'L\'email est obligatoire.'),
                        new Email(message: 'Adresse email invalide.'),
                    ],
                    'attr' => ['placeholder' => 'Ex: utilisateur@entreprise.com'],
                ])
                ->add('nom', TextType::class, [
                    'label' => 'Nom',
                    'constraints' => [
                        new NotBlank(message: 'Le nom est obligatoire.'),
                    ],
                    'attr' => ['maxlength' => 100, 'minlength' => 2, 'placeholder' => 'Ex: Dupont'],
                ])
                ->add('prenom', TextType::class, [
                    'label' => 'Prénom',
                    'constraints' => [
                        new NotBlank(message: 'Le prénom est obligatoire.'),
                    ],
                    'attr' => ['maxlength' => 100, 'minlength' => 2, 'placeholder' => 'Ex: Jean'],
                ])
                ->add('roles', ChoiceType::class, options: [
                    'label' => 'Rôles',
                    'choices' => [
                        'Administrateur' => 'ROLE_ADMIN',
                        'Planificateur' => 'ROLE_PLANIFICATEUR',
                        'Technicien' => 'ROLE_TECHNICIEN',
                        'Demandeur' => 'ROLE_DEMANDEUR',
                    ],
                    'placeholder' => '-- Choisir un rôle --',
                    'getter' => function (User $user): ?string {
                        $roles = $user->getRoles();
                        $filtered = array_filter($roles, fn($r) => $r !== 'ROLE_USER');
                        return $filtered ? reset($filtered) : null;
                    },
                    'setter' => function (User $user, ?string $role): void {
                        $user->setRoles($role ? [$role] : []);
                    },
                ]);
        }

        public function configureOptions(OptionsResolver $resolver): void
        {
            $resolver->setDefaults([
                'data_class' => User::class,
            ]);
        }
    }
