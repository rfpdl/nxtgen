<?php

declare(strict_types=1);

use App\Foundation\Support\SearchWords;

/*
 * A typed search read as words (issue 04): at least two characters each, at most five; a phone-shaped part — a run of
 * digit-ish words — pulled out as one phone number when it has four digits or more.
 */

it('splits on any spaces, a non-breaking one too, skipping one-letter words', function (): void {
    expect(SearchWords::of("  Ana \u{00A0}Reyes\tR  ")->words)->toBe(['Ana', 'Reyes'])
        ->and(SearchWords::of("\u{00A0}Ana\u{00A0}")->words)->toBe(['Ana'])
        ->and(SearchWords::of(' a ')->isEmpty())->toBeTrue()
        ->and(SearchWords::of('')->isEmpty())->toBeTrue();
});

it('reads at most five words', function (): void {
    expect(SearchWords::of('one two three four five six seven')->words)->toBe(['one', 'two', 'three', 'four', 'five']);
});

it('pulls a phone-shaped part out as one number, and keeps the name words around it', function (string $term, array $words, ?string $phone): void {
    $search = SearchWords::of($term);

    expect([$search->words, $search->phone])->toBe([$words, $phone]);
})->with([
    'spaced phone' => ['050 444 5566', [], '0504445566'],
    'name + spaced phone' => ['Reyes 050 444 5566', ['Reyes'], '0504445566'],
    'name + phone in one' => ['Reyes 0504445566', ['Reyes'], '0504445566'],
    'with + dashes and brackets' => ['+971 (50) 444-5566 Ana', ['Ana'], '971504445566'],
    'too few digits to be a phone' => ['Reyes 050', ['Reyes', '050'], null],
    'digits inside an email are not a phone' => ['roy1985@example.com', ['roy1985@example.com'], null],
]);

it('says whether a word is digit-ish (so only such a word is compared with phones)', function (): void {
    expect(SearchWords::digitish('050-444'))->toBeTrue()
        ->and(SearchWords::digitish('roy1985@x'))->toBeFalse()
        ->and(SearchWords::digitish('---'))->toBeFalse();
});

it('reads %, _ and \\ in a word as letters', function (): void {
    expect(SearchWords::like('50%_off\\x'))->toBe('%50\\%\\_off\\\\x%');
});
