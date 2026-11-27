<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Symfony\Form\Permission;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * What a role grants, at a glance: the ticked permissions of a sibling
 * PermissionTreeType counted per context, kept up to date as boxes change.
 * Shows only; nothing is submitted.
 */
final class PermissionSummaryType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['tree_field'] = $options['tree_field'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'required' => false,
            'label' => false,
            'tree_field' => 'permissions',
        ]);
        $resolver->setAllowedTypes('tree_field', 'string');
    }

    public function getBlockPrefix(): string
    {
        return 'ddd_iam_permission_summary';
    }
}
