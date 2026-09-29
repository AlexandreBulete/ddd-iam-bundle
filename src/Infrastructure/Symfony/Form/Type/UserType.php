<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Symfony\Form\Type;

use AlexandreBulete\DddIamBundle\Domain\Enum\UserStatusEnum;
use AlexandreBulete\DddIamBundle\Domain\Repository\RoleDefinitionRepositoryInterface;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\UserResource;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The back-office user form.
 *
 * The role choices are the role definitions: a role defined in the back
 * office shows up here with no code change.
 */
final class UserType extends AbstractType
{
    public function __construct(
        private readonly RoleDefinitionRepositoryInterface $roles,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $resource = $options['data'] ?? null;
        $isCreation = !$resource instanceof UserResource || $resource->id === null;

        $builder
            ->add('email', EmailType::class, [
                'label' => 'iam.user.email',
                'required' => true,
            ])
            ->add('password', PasswordType::class, [
                'label' => 'iam.user.password',
                'required' => $isCreation,
                // Never echo a hash back into the input. On update the field
                // starts empty and an empty submission means "keep it".
                'always_empty' => true,
                'help' => $isCreation ? null : 'iam.user.password_help',
            ])
            ->add('firstName', TextType::class, [
                'label' => 'iam.user.first_name',
                'required' => false,
            ])
            ->add('lastName', TextType::class, [
                'label' => 'iam.user.last_name',
                'required' => false,
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'iam.user.roles',
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => $this->roleChoices(),
                'choice_translation_domain' => false,
            ])
            ->add('status', EnumType::class, [
                'class' => UserStatusEnum::class,
                'label' => 'iam.user.status',
                'required' => true,
                'choice_label' => static fn (UserStatusEnum $status): string => 'iam.user.status_' . $status->value,
            ])
        ;
    }

    /**
     * @return array<string, string> role label => role name (`chef_de_projet`)
     */
    private function roleChoices(): array
    {
        $choices = [];
        foreach ($this->roles->all() as $definition) {
            $choices[$definition->label] = $definition->role->name();
        }

        return $choices;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserResource::class,
        ]);
    }
}
