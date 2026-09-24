<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Symfony\Form\Type;

use AlexandreBulete\DddIamBundle\Domain\Enum\UserStatusEnum;
use AlexandreBulete\DddIamBundle\Domain\Service\RoleCatalogInterface;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Role;
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
 * The role choices come from {@see RoleCatalogInterface}, so a role added
 * under `iam.roles` shows up here with no code change — that is the whole
 * point of the catalogue.
 *
 * @extends AbstractType<UserResource>
 */
final class UserType extends AbstractType
{
    public function __construct(
        private readonly RoleCatalogInterface $roleCatalog,
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
                'choice_translation_domain' => 'messages',
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
     * @return array<string, string> label key => role configuration name
     */
    private function roleChoices(): array
    {
        $choices = [];

        /** @var Role $role */
        foreach ($this->roleCatalog->all() as $role) {
            $choices[$role->labelKey()] = $role->name();
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
