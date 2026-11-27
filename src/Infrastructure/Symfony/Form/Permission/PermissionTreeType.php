<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Symfony\Form\Permission;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The permissions of a role, as a tree (PermissionTree): a box at each level
 * ticks everything below it. Still a plain multiple choice underneath — what
 * is submitted is the list of ticked permissions.
 */
final class PermissionTreeType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var array<string, string> $choices */
        $choices = $options['choices'];
        $view->vars['tree'] = PermissionTree::build(array_values($choices));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'choice_translation_domain' => false,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'ddd_iam_permission_tree';
    }
}
