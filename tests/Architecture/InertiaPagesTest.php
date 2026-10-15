<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/**
 * Every page a Feature renders has to exist on disk.
 *
 * **Written because it happened.** `ShowTempCardsFeature` rendered `Teacher/TempCards` for a while
 * before that component existed. The Feature test passed — `assertInertia` checks the props, not
 * whether the frontend can resolve the name — and so did every PHP gate. The failure is client-side
 * and total: the page throws in the browser and renders nothing at all.
 *
 * The reverse direction is not guarded. An unused `.vue` page is dead code worth deleting, but it is
 * inert, and a page built one step ahead of the Feature that will render it is a reasonable order to
 * work in.
 */
/**
 * @return array<string, string>
 */
function renderedPages(): array
{
    $pages = [];

    foreach (Finder::create()->files()->in(base_path('app/Services'))->name('*Feature.php') as $file) {
        preg_match_all(
            "/Inertia::render\(\s*'([A-Za-z0-9\/]+)'/",
            $file->getContents(),
            $matches,
        );

        foreach ($matches[1] as $page) {
            $pages[$page] = str_replace(base_path().'/', '', (string) $file->getRealPath());
        }
    }

    return $pages;
}

it('finds the renders it is supposed to be guarding', function (): void {
    // The guard on the guard: a regex that matched nothing would pass forever.
    expect(array_keys(renderedPages()))->toContain('Public/Welcome');
});

it('renders only pages that exist', function (): void {
    $missing = [];

    foreach (renderedPages() as $page => $feature) {
        if (! file_exists(resource_path("js/pages/{$page}.vue"))) {
            $missing[$page] = $feature;
        }
    }

    expect($missing)->toBe(
        [],
        "These Features render pages with no component:\n".json_encode($missing, JSON_PRETTY_PRINT),
    );
});
