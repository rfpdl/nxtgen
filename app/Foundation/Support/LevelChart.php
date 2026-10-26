<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use App\Enums\GenderEnum;
use Illuminate\Support\Str;

/**
 * The church-wide Levels (GLOSSARY "Level"), read from `config('nxtgen.levels')` — CCF's age-level chart. The one
 * place that knows the chart's shape: which Levels exist, what each is called, which ages it suggests, and the
 * Squads every Satellite splits it into (by gender from Level 2 up).
 */
final class LevelChart
{
    /** @return list<int> the Levels, lowest first */
    public static function levels(): array
    {
        $levels = array_map('intval', array_keys(self::chart()));
        sort($levels);

        return $levels;
    }

    public static function exists(int $level): bool
    {
        return array_key_exists($level, self::chart());
    }

    /** "Level 2" — the Level's name on every screen and sheet. */
    public static function name(int $level): string
    {
        return "Level {$level}";
    }

    /** The Level whose ages include `$age` (completed years), or null when the chart has none. */
    public static function forAge(int $age): ?int
    {
        foreach (self::levels() as $level) {
            $band = self::chart()[$level];
            if ($age >= $band['min_age'] && $age <= $band['max_age']) {
                return $level;
            }
        }

        return null;
    }

    /**
     * The Level a person wrote down — "Level 2", "Level 2 Boys", "level 2", "2" — or null when it names none of the
     * chart's Levels. A boys'/girls' word is ignored: the Squad follows from the child's gender.
     */
    public static function fromText(string $text): ?int
    {
        if (preg_match('/^\s*(?:level\s*)?(\d+)(?:\s+(?:boys?|girls?|mixed))?\s*$/i', Str::squish($text), $m) !== 1) {
            return null;
        }

        $level = (int) $m[1];

        return self::exists($level) ? $level : null;
    }

    /**
     * The Squads one Satellite has: each Level once (mixed, gender null) or split into boys and girls.
     *
     * @return list<array{level: int, gender: GenderEnum|null}>
     */
    public static function squads(): array
    {
        $squads = [];

        foreach (self::levels() as $level) {
            foreach (self::chart()[$level]['by_gender'] ? [GenderEnum::Boy, GenderEnum::Girl] : [null] as $gender) {
                $squads[] = ['level' => $level, 'gender' => $gender];
            }
        }

        return $squads;
    }

    /**
     * The gender of the Squad a child of this gender joins at this Level: theirs where the Level is split, null
     * (mixed) where it is not — Level 1.
     */
    public static function squadGender(int $level, GenderEnum $gender): ?GenderEnum
    {
        return (self::chart()[$level]['by_gender'] ?? false) ? $gender : null;
    }

    /** "Level 2 Boys", "Level 2 Girls", or "Level 1" for a mixed squad. */
    public static function squadName(int $level, ?GenderEnum $gender): string
    {
        return match ($gender) {
            null => self::name($level),
            GenderEnum::Boy => self::name($level).' Boys',
            GenderEnum::Girl => self::name($level).' Girls',
        };
    }

    /** @return array<int, array{min_age: int, max_age: int, by_gender: bool}> */
    private static function chart(): array
    {
        /** @var array<int, array{min_age: int, max_age: int, by_gender: bool}> $chart */
        $chart = config('nxtgen.levels', []);

        return $chart;
    }
}
