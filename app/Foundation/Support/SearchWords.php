<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A typed search, read as words (issue 04: "Reyes" is common, so "Ana Reyes" must narrow it). The ONE reading behind
 * the door, the Families and Children lists and staff registration:
 *
 * - words are split on any Unicode space (a pasted non-breaking one too); a word under two characters says too little
 *   and is skipped; only the first five are read, so a long paste cannot make a query of dozens of joins;
 * - the first run of digit-ish words ("050 444 5566", "+971 (50) 444-5566", "0504445566") with four digits or more is
 *   pulled out as ONE phone number (`phone`, its digits); the name words around it narrow the search further.
 *
 * `apply()` puts it on a query: every word must satisfy the caller's per-word rule, AND the phone the caller's phone
 * rule — or nothing matches when nothing is left to match.
 */
final readonly class SearchWords
{
    public const int MAX = 5;

    /** @param list<string> $words */
    private function __construct(
        public array $words,
        public ?string $phone,
    ) {}

    public static function of(string $term): self
    {
        $tokens = preg_split('/(*UCP)[\s\p{Z}]+/u', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        [$phone, $rest] = self::phoneIn($tokens);
        $words = array_values(array_filter($rest, fn (string $w): bool => mb_strlen($w) >= 2));

        return new self(array_slice($words, 0, self::MAX), $phone);
    }

    public function isEmpty(): bool
    {
        return $this->words === [] && $this->phone === null;
    }

    /**
     * Narrow `$query`: each word through `$word` (its own group, AND'ed), and the phone through `$phone`.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  Closure(Builder<TModel>, string): mixed  $word
     * @param  Closure(Builder<TModel>, string): mixed  $phone
     */
    public function apply(Builder $query, Closure $word, Closure $phone): void
    {
        if ($this->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $all) use ($word, $phone): void {
            foreach ($this->words as $one) {
                $all->where(fn (Builder $w) => $word($w, $one));
            }
            if ($this->phone !== null) {
                $all->where(fn (Builder $p) => $phone($p, (string) $this->phone));
            }
        });
    }

    /** Only digits and + - ( ) . — with at least one digit: a word that can be (part of) a phone number. */
    public static function digitish(string $word): bool
    {
        return preg_match('/^[\d+\-().]+$/', $word) === 1 && preg_match('/\d/', $word) === 1;
    }

    /** The word's digits, when it is digit-ish and has four or more; else null. */
    public static function phoneDigitsOf(string $word): ?string
    {
        $digits = (string) preg_replace('/\D+/', '', $word);

        return self::digitish($word) && strlen($digits) >= 4 ? $digits : null;
    }

    /** A LIKE pattern for a word: `%word%`, with \, % and _ read as letters. */
    public static function like(string $word): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $word).'%';
    }

    /**
     * The first run of digit-ish tokens with four digits or more, as its digits, and the tokens left around it.
     *
     * @param  list<string>  $tokens
     * @return array{0: string|null, 1: list<string>}
     */
    private static function phoneIn(array $tokens): array
    {
        $count = count($tokens);
        for ($start = 0; $start < $count; $start++) {
            $end = $start;
            while ($end < $count && self::digitish($tokens[$end])) {
                $end++;
            }
            $digits = (string) preg_replace('/\D+/', '', implode('', array_slice($tokens, $start, $end - $start)));
            if (strlen($digits) >= 4) {
                return [$digits, [...array_slice($tokens, 0, $start), ...array_slice($tokens, $end)]];
            }
        }

        return [null, $tokens];
    }
}
