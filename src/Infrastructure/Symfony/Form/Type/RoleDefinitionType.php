<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Symfony\Form\Type;

use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\RoleDefinitionResource;
use AlexandreBulete\DddSymfonyBundle\Messenger\Authorization\PermissionRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The permission choices are every permission of the application, grouped by
 * context — discovered from its use cases: a new use case appears here with
 * no code change (ADR 0008).
 */
final class RoleDefinitionType extends AbstractType
{
    public function __construct(
        private readonly PermissionRegistry $permissions,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $resource = $options['data'] ?? null;
        $isCreation = !$resource instanceof RoleDefinitionResource || $resource->id === null;
        $isSystem = $resource instanceof RoleDefinitionResource && $resource->system;

        $builder
            ->add('label', TextType::class, [
                'label' => 'iam.role.label',
            ])
            ->add('name', TextType::class, [
                'label' => 'iam.role.name',
                'help' => 'iam.role.name_help',
                'disabled' => !$isCreation,
            ]);

        if (!$isSystem) {
            $builder->add('permissions', ChoiceType::class, [
                'label' => 'iam.role.permissions',
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => $this->permissionChoices(),
                'choice_translation_domain' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RoleDefinitionResource::class,
        ]);
    }

    /**
     * @return array<string, array<string, string>> context => [permission => permission]
     */
    private function permissionChoices(): array
    {
        $choices = [];
        foreach ($this->permissions->byContext() as $context => $permissions) {
            $choices[$context] = array_combine($permissions, $permissions);
        }

        return $choices;
    }
}
