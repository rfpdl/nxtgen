<?php

declare(strict_types=1);

use App\Data\Models\Church;
use App\Domains\Church\Jobs\GetChurchesJob;

use function Tests\Support\church;

/* Every church in the app, oldest first — for work done church by church from the terminal (the photo sweep). */

it('lists every church, oldest first', function (): void {
    $ccf = church(['slug' => 'ccf']);
    $other = church(['slug' => 'other']);

    expect(array_map(fn (Church $c): int => $c->id, (new GetChurchesJob)->handle()))->toBe([$ccf->id, $other->id]);
});

it('is empty when there is no church yet', function (): void {
    expect((new GetChurchesJob)->handle())->toBe([]);
});
