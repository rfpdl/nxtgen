<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Symfony\Component\Finder\Finder;

/*
 * A page's props are ONE generated contract (TODO #43, Codex): the Feature renders a `…PageData` (#[TypeScript]), and
 * the page declares `defineProps<…Page>()` — that same type, generated into `resources/js/types/generated.ts`. So a
 * field renamed or dropped in PHP is a type error on the page, never a page quietly showing nothing. A page with no
 * props renders none and declares none.
 */

it('renders every Inertia page from a …PageData, or with no props at all', function (): void {
    $offenders = [];

    foreach (Finder::create()->files()->in(base_path('app/Services'))->name('*Feature.php') as $file) {
        preg_match_all("/Inertia::render\\('([^']+)'(\\s*,\\s*(new [A-Za-z]+PageData\\()?)?/", $file->getContents(), $renders, PREG_SET_ORDER);
        foreach ($renders as $render) {
            $hasProps = ($render[2] ?? '') !== '';
            $isContract = ($render[3] ?? '') !== '';
            if ($hasProps && ! $isContract) {
                $offenders[] = $render[1];
            }
        }
    }

    expectOnlyKnownGaps(array_values(array_unique($offenders)), KnownGaps::PAGE_PROPS_NOT_PAGE_DATA, 'Render the page from a new …PageData(…)');
});

it('declares every page\'s props as its generated …Page type, never a shape of its own', function (): void {
    $offenders = [];

    foreach (Finder::create()->files()->in(base_path('resources/js/pages'))->name('*.vue') as $file) {
        $source = $file->getContents();
        if (! str_contains($source, 'defineProps')) {
            continue;
        }
        if (preg_match('/defineProps<[A-Za-z]+Page>\(\)/', $source) !== 1) {
            $offenders[] = str_replace(base_path('resources/js/pages').'/', '', (string) $file->getRealPath());
        }
    }

    sort($offenders);
    expectOnlyKnownGaps($offenders, KnownGaps::PAGE_PROPS_HAND_TYPED, 'Declare the props as defineProps<XPage>() — the Feature\'s …PageData');
});
