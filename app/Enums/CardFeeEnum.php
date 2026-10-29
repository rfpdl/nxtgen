<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Carbon\CarbonImmutable;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Where a Card request's fee stands (DECISIONS 2026-10-29): free (a first card), paid (marked at the desk), or not
 * paid. Worked out from the request, never stored. Its colour is the Card request's Pill colour — the one colour rule.
 */
#[TypeScript]
enum CardFeeEnum: string implements HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case Free = 'free';
    case Paid = 'paid';
    case NotPaid = 'not_paid';

    public static function of(int $feeMinor, ?CarbonImmutable $paidAt): self
    {
        return match (true) {
            $feeMinor === 0 => self::Free,
            $paidAt !== null => self::Paid,
            default => self::NotPaid,
        };
    }

    /** Lower case: it follows the step, "Asked · not paid". */
    public function label(): string
    {
        return match ($this) {
            self::Free => 'free',
            self::Paid => 'paid',
            self::NotPaid => 'not paid',
        };
    }

    public function color(): string
    {
        return $this === self::NotPaid ? 'warning' : 'info';
    }
}
