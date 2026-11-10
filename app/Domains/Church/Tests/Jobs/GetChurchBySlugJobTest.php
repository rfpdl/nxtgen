<?php

declare(strict_types=1);

use App\Domains\Church\Jobs\GetChurchBySlugJob;

use function Tests\Support\church;

/* The church a terminal command names by its slug (`--church=ccf`), or none. */

it('finds the church a slug names, and nothing for a slug nobody has', function (): void {
    $ccf = church(['slug' => 'ccf']);
    church(['slug' => 'other']);

    expect((new GetChurchBySlugJob(slug: 'ccf'))->handle()?->id)->toBe($ccf->id)
        ->and((new GetChurchBySlugJob(slug: 'nope'))->handle())->toBeNull();
});
