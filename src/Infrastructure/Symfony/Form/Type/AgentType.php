<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Symfony\Form\Type;

use AlexandreBulete\DddIamBundle\Domain\Enum\UserStatusEnum;
use AlexandreBulete\DddIamBundle\Domain\Repository\RoleDefinitionRepositoryInterface;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\AgentResource;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The back-office agent form: the same role choices as for a person.
 */
final class AgentType extends AbstractType
{
    public function __construct(
        private readonly RoleDefinitionRepositoryInterface $roles,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $resource = $options['data'] ?? null;
        $isCreation = !$resource instanceof AgentResource || $resource->id === null;

        $builder
            ->add('name', TextType::class, [
                'label' => 'iam.agent.name',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'iam.agent.description',
                'required' => false,
                'help' => 'iam.agent.description_help',
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'iam.agent.roles',
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => $this->roleChoices(),
                'choice_translation_domain' => false,
            ])
        ;

        // A new agent starts active; its lifecycle is an edit.
        if (!$isCreation) {
            $builder->add('status', EnumType::class, [
                'class' => UserStatusEnum::class,
                'label' => 'iam.agent.status',
                'choice_label' => static fn (UserStatusEnum $status): string => 'iam.user.status_' . $status->value,
            ]);
        }
    }

    /**
     * @return array<string, string> role label => role name
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
            'data_class' => AgentResource::class,
        ]);
    }
}
