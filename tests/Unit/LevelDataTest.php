<?php

declare(strict_types=1);

use App\Foundation\DataObjects\LevelData;

describe('LevelData', function (): void {
    it('carries the number and the name a person reads', function (): void {
        $level = LevelData::fromNumber(2);

        expect([$level?->number, $level?->name])->toBe([2, 'Level 2']);
    });

    it('is made for a Squad, whose Level is never empty', function (): void {
        expect(LevelData::of(4)->name)->toBe('Level 4');
    });

    it('is null for a child with no Level yet', function (): void {
        expect(LevelData::fromNumber(null))->toBeNull();
    });
});
