<?php

declare(strict_types=1);

use App\Foundation\Support\LevelChart;

/* Moving-Up (GLOSSARY): each Child goes to the next Level; from the top Level they leave NxtGen (Graduated). */

it('moves a child to the next Level at Moving-Up', function (int $level, int $next): void {
    expect(LevelChart::movedUp($level))->toBe($next);
})->with([[1, 2], [2, 3], [3, 4]]);

it('has no next Level from the top one (4): the child graduates', function (): void {
    expect(LevelChart::movedUp(4))->toBeNull();
});
