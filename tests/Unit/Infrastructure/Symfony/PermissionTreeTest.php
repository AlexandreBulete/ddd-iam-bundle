<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Tests\Unit\Infrastructure\Symfony;

use AlexandreBulete\DddIamBundle\Infrastructure\Symfony\Form\Permission\PermissionTree;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PermissionTreeTest extends TestCase
{
    #[Test]
    public function permissionsGatherUnderTheirContextAndObject(): void
    {
        $tree = PermissionTree::build([
            'blog.archive_post', 'blog.change_post_slug', 'blog.find_posts',
            'blog.create_category', 'blog.find_categories',
            'blog.find_tag',
            'comment.moderate',
        ]);

        self::assertSame(['blog', 'comment'], array_column($tree, 'key'));
        self::assertSame(['blog.category', 'blog.post'], array_column($tree[0]['children'], 'key'));
        self::assertSame(
            ['blog.archive_post', 'blog.change_post_slug', 'blog.find_posts'],
            array_column($tree[0]['children'][1]['permissions'], 'value'),
        );
        self::assertSame(['blog.find_tag'], array_column($tree[0]['permissions'], 'value'), 'a lone object stays a leaf');
        self::assertSame(['comment.moderate'], array_column($tree[1]['permissions'], 'value'), 'a bare verb is a leaf');
        self::assertSame('change post slug', $tree[0]['children'][1]['permissions'][1]['label']);
    }

    #[Test]
    public function anObjectNamedLikeItsContextIsNotALevel(): void
    {
        $tree = PermissionTree::build(['page.create_page', 'page.find_pages', 'seo.edit']);

        self::assertSame([], $tree[0]['children']);
        self::assertSame(['page.create_page', 'page.find_pages'], array_column($tree[0]['permissions'], 'value'));
        self::assertSame('SEO', $tree[1]['label']);
    }

    #[Test]
    public function anObjectEndingWithAKnownOneJoinsIt(): void
    {
        $tree = PermissionTree::build([
            'contact.count_new_contact_messages', 'contact.delete_contact_message', 'contact.find_contact_messages',
        ]);

        self::assertSame(['contact.contact_message'], array_column($tree[0]['children'], 'key'));
        self::assertCount(3, $tree[0]['children'][0]['permissions']);
        self::assertSame('contact message', $tree[0]['children'][0]['label']);
    }
}
