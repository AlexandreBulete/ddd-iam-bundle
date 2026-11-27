<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Symfony\Form\Permission;

/**
 * Arranges permission names (`context.verb_object`) as a tree a role screen
 * can browse: context › object › permission.
 *
 * `blog.archive_post`, `blog.change_post_slug` and `blog.find_posts` all land
 * under blog › post: the object is the part after the verb, singular, cut
 * down to the shortest object of the same context it starts or ends with. An
 * object with a single permission, or named like its context (page › page),
 * is not worth a level: its permissions stay leaves of the context, as do names
 * without an object (`comment.moderate`). Contexts of three letters or less
 * read as acronyms (IAM, SEO).
 *
 * @phpstan-type Leaf array{value: string, label: string}
 * @phpstan-type Node array{key: string, label: string, children: list<array{key: string, label: string, children: list<mixed>, permissions: list<Leaf>}>, permissions: list<Leaf>}
 */
final class PermissionTree
{
    /**
     * @param list<string> $permissions
     *
     * @return list<Node>
     */
    public static function build(array $permissions): array
    {
        $byContext = [];
        foreach ($permissions as $permission) {
            $dot = strpos($permission, '.');
            $context = $dot === false ? $permission : substr($permission, 0, $dot);
            $byContext[$context][] = $permission;
        }
        ksort($byContext);

        $tree = [];
        foreach ($byContext as $context => $names) {
            $tree[] = self::context($context, $names);
        }

        return $tree;
    }

    /**
     * @param list<string> $names
     *
     * @return Node
     */
    private static function context(string $context, array $names): array
    {
        $objects = [];
        foreach ($names as $name) {
            $object = self::objectOf($name);
            if ($object !== null) {
                $objects[$name] = $object;
            }
        }

        $known = array_values(array_unique($objects));
        usort($known, static fn (string $a, string $b): int => [strlen($a), $a] <=> [strlen($b), $b]);

        $groups = [];
        $leaves = [];
        foreach ($names as $name) {
            $object = $objects[$name] ?? null;
            if ($object === null) {
                $leaves[] = self::leaf($name);
                continue;
            }
            $groups[self::subjectOf($object, $known)][] = $name;
        }
        ksort($groups);

        $children = [];
        foreach ($groups as $subject => $members) {
            // A lone object, or the context's own (page › page), is not worth a level.
            if (count($members) === 1 || $subject === $context) {
                array_push($leaves, ...array_map(self::leaf(...), $members));
                continue;
            }
            $children[] = [
                'key' => $context . '.' . $subject,
                'label' => self::humanize($subject),
                'children' => [],
                'permissions' => array_map(self::leaf(...), $members),
            ];
        }
        usort($leaves, static fn (array $a, array $b): int => strcmp($a['value'], $b['value']));

        // Short context names are acronyms (iam, seo).
        $label = strlen($context) <= 3 ? strtoupper($context) : $context;

        return ['key' => $context, 'label' => $label, 'children' => $children, 'permissions' => $leaves];
    }

    /**
     * The singular words after the verb, or null for a bare verb.
     */
    private static function objectOf(string $name): ?string
    {
        $dot = strpos($name, '.');
        $words = explode('_', $dot === false ? $name : substr($name, $dot + 1));
        array_shift($words);

        return $words === [] ? null : implode('_', array_map(self::singular(...), $words));
    }

    /**
     * @param list<string> $known shortest first
     */
    private static function subjectOf(string $object, array $known): string
    {
        foreach ($known as $candidate) {
            if ($object === $candidate
                || str_starts_with($object, $candidate . '_')
                || str_ends_with($object, '_' . $candidate)) {
                return $candidate;
            }
        }

        return $object;
    }

    private static function singular(string $word): string
    {
        if (str_ends_with($word, 'ies') && strlen($word) > 4) {
            return substr($word, 0, -3) . 'y';
        }
        if (str_ends_with($word, 's') && !str_ends_with($word, 'ss') && strlen($word) > 3) {
            return substr($word, 0, -1);
        }

        return $word;
    }

    /**
     * @return Leaf
     */
    private static function leaf(string $name): array
    {
        $dot = strpos($name, '.');

        return ['value' => $name, 'label' => self::humanize($dot === false ? $name : substr($name, $dot + 1))];
    }

    private static function humanize(string $words): string
    {
        return str_replace('_', ' ', $words);
    }
}
