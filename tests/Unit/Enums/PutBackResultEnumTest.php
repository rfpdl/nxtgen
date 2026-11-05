<?php

declare(strict_types=1);

use App\Enums\PutBackResultEnum;

/* Putting one child back from the last Moving-Up: back, already back, or left alone because someone changed them. */

it('has the outcomes of putting one child back', function (): void {
    expect(array_column(PutBackResultEnum::cases(), 'value'))->toBe(['put_back', 'already_put_back', 'changed_since']);
});

it('says what happened after the child\'s name, in plain words', function (): void {
    expect(PutBackResultEnum::AlreadyPutBack->label())->toBe('was already put back')
        ->and(PutBackResultEnum::ChangedSince->label())->toBe('has a different Level or status since Moving-Up, so was left as they are')
        ->and([PutBackResultEnum::PutBack->color(), PutBackResultEnum::ChangedSince->color()])->toBe(['success', 'danger']);
});
