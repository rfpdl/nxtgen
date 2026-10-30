<?php

declare(strict_types=1);

use App\Data\Models\PrintList;
use App\Domains\Card\Jobs\GetPrintListJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Tests\Support\church;

/* A Print list by id, only within this church — an id from a URL never reaches another church's list. */

it('finds this church\'s print list', function (): void {
    $church = church();
    $list = PrintList::factory()->for($church)->create();

    expect((new GetPrintListJob(church: $church, id: $list->id))->handle()->is($list))->toBeTrue();
});

it('refuses another church\'s print list', function (): void {
    $other = PrintList::factory()->for(church())->create();

    (new GetPrintListJob(church: church(), id: $other->id))->handle();
})->throws(ModelNotFoundException::class);
