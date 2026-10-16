<?php

declare(strict_types=1);

use App\Enums\AccessRowEnum;
use App\Foundation\Attributes\AccessRow;
use Symfony\Component\Finder\Finder;

/**
 * Every staff and parent Feature declares which row of the access matrix it serves (plan §3.2, §10:
 * "every Feature declares its access-matrix row"). The row names the rule; AccessScope enforces it; the
 * Feature tests prove it. A Feature with no row is a screen nobody decided who may use.
 */

/** @return list<class-string> concrete Features of the services that need a signed-in, scoped user */
function scopedFeatures(): array
{
    $classes = [];
    // Org + Family: every Feature. Auth/Features/Photo: the photo route decides who may see a child (review 3).
    foreach (['Org' => 'Features', 'Family' => 'Features', 'Auth' => 'Features/Photo'] as $service => $dir) {
        foreach (Finder::create()->files()->in(base_path("app/Services/{$service}/{$dir}"))->name('*Feature.php') as $file) {
            $relative = str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            $namespace = str_replace('/', '\\', $dir);
            $class = "App\\Services\\{$service}\\{$namespace}\\{$relative}";
            if (class_exists($class) && ! (new ReflectionClass($class))->isAbstract()) {
                $classes[] = $class;
            }
        }
    }
    sort($classes);

    return $classes;
}

/**
 * @param  class-string  $class
 * @return list<AccessRowEnum>
 */
function declaredRows(string $class): array
{
    return array_map(
        fn (ReflectionAttribute $a): AccessRowEnum => $a->newInstance()->row,
        (new ReflectionClass($class))->getAttributes(AccessRow::class),
    );
}

it('finds the Features it is supposed to be guarding', function (): void {
    expect(count(scopedFeatures()))->toBeGreaterThan(15);
});

it('has every staff and parent Feature declare exactly one access-matrix row', function (): void {
    $missing = array_values(array_filter(scopedFeatures(), fn (string $c): bool => count(declaredRows($c)) !== 1));

    expect($missing)->toBe([], "Declare #[AccessRow(AccessRowEnum::…)] on:\n".implode("\n", $missing));
});

it('has no matrix row that no Feature serves', function (): void {
    $used = [];
    foreach (scopedFeatures() as $class) {
        foreach (declaredRows($class) as $row) {
            $used[$row->value] = true;
        }
    }
    $unused = array_values(array_filter(AccessRowEnum::cases(), fn (AccessRowEnum $r): bool => ! isset($used[$r->value])));

    expect(array_map(fn (AccessRowEnum $r): string => $r->name, $unused))->toBe([]);
});
