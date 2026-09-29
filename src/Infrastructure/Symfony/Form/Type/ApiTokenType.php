<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Symfony\Form\Type;

use AlexandreBulete\DddIamBundle\Domain\Repository\AgentRepositoryInterface;
use AlexandreBulete\DddIamBundle\Infrastructure\Sylius\Resource\ApiTokenResource;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Issuing a token: for which agent, what it is for, how long it lives
 * (90 days unless said otherwise, a year at most — ADR 0011).
 */
final class ApiTokenType extends AbstractType
{
    public function __construct(
        private readonly AgentRepositoryInterface $agents,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('agentId', ChoiceType::class, [
                'label' => 'iam.api_token.agent',
                'choices' => $this->agentChoices(),
                'choice_translation_domain' => false,
            ])
            ->add('label', TextType::class, [
                'label' => 'iam.api_token.label',
                'help' => 'iam.api_token.label_help',
            ])
            ->add('lifetimeDays', ChoiceType::class, [
                'label' => 'iam.api_token.lifetime',
                'choices' => [
                    'iam.api_token.lifetime_30' => 30,
                    'iam.api_token.lifetime_90' => 90,
                    'iam.api_token.lifetime_180' => 180,
                    'iam.api_token.lifetime_365' => 365,
                ],
            ])
        ;
    }

    /**
     * @return array<string, string> agent name => id; active agents only
     */
    private function agentChoices(): array
    {
        $choices = [];
        foreach ($this->agents->active() as $agent) {
            $choices[$agent->name] = (string) $agent->id;
        }

        return $choices;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ApiTokenResource::class,
        ]);
    }
}
