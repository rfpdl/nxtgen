<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;

/**
 * A spreadsheet row whose every cell is LITERAL TEXT. Row::fromValues turns "=…" into a live formula, and
 * these values come from public registration — a nickname of "=HYPERLINK(…)" would run in a staff member's
 * Excel (formula injection, Codex diff review). Every writer uses this, never Row::fromValues.
 */
final class TextRow
{
    /** @param list<string> $values */
    public static function of(array $values): Row
    {
        return new Row(array_map(static fn (string $v): StringCell => new StringCell($v), $values));
    }
}
