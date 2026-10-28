<?php

declare(strict_types=1);

use App\Domains\Church\Jobs\GetChurchBySlugJob;

use function Tests\Support\church;

/* The church a setting names (`nxtgen.church.slug`) — or none, so a page can leave its link out instead of failing. */

it('finds the church by its slug', function (): void {
    $ccf = church(['slug' => 'ccf']);

    expect((new GetChurchBySlugJob(slug: 'ccf'))->handle()?->id)->toBe($ccf->id);
});

it('reads a slug typed in capitals or with spaces as the lowercase one', function (): void {
    $ccf = church(['slug' => 'ccf']);

    expect((new GetChurchBySlugJob(slug: ' CCF '))->handle()?->id)->toBe($ccf->id);
});

it('finds nothing for a church that is not there, or an empty slug', function (): void {
    church(['slug' => 'ccf']);

    expect((new GetChurchBySlugJob(slug: 'other'))->handle())->toBeNull()
        ->and((new GetChurchBySlugJob(slug: ''))->handle())->toBeNull();
});
