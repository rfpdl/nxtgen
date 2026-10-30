<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The moves a Card request makes (name cards 03, DECISIONS 2026-10-29) — the ONE home of which move may start from
 * which step (`fromSteps()`) and what the fee must be (`allowedFor()`). The screens get the allowed moves from here
 * (CardRequestRowData, CardRequestData) and never work them out themselves.
 *
 * Each move's Job (UpdateCardRequestAs…Job) is one conditional UPDATE on the request's id and the steps it starts from
 * (plus the fee where it matters), so two people — or two taps — cannot make one move twice: the one that lands second
 * changes nothing and is told `false`. It also names the request's church, as every query on a church's rows does
 * (TenancyArchTest); what keeps another church's request out is the caller, which loaded it within its own church
 * (GetCardRequestJob).
 */
#[TypeScript]
enum CardMoveEnum: string implements HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case MarkPaid = 'mark_paid';
    case Approve = 'approve';
    case Refuse = 'refuse';
    case MarkPrinted = 'mark_printed';
    case Give = 'give';

    public function label(): string
    {
        return match ($this) {
            self::MarkPaid => 'Mark paid',
            self::Approve => 'Approve',
            self::Refuse => 'Refuse…',
            self::MarkPrinted => 'Mark printed',
            self::Give => 'Given',
        };
    }

    /** Plain words: buttons pick their own look. */
    public function color(): string
    {
        return 'muted';
    }

    /** @return list<CardStepEnum> the steps this move may start from */
    public function fromSteps(): array
    {
        return match ($this) {
            self::MarkPaid => CardStepEnum::openSteps(),
            self::Approve => [CardStepEnum::Asked],
            self::Refuse => [CardStepEnum::Asked, CardStepEnum::Approved],
            self::MarkPrinted => [CardStepEnum::Approved],
            self::Give => [CardStepEnum::Printed],
        };
    }

    /** @return list<string> the stored values of fromSteps(), for a query */
    public function fromStepValues(): array
    {
        return array_map(fn (CardStepEnum $step): string => $step->value, $this->fromSteps());
    }

    /** Whether a request at `$step` with `$fee` may make this move: Mark paid only while not paid; Approve only once paid or free. */
    public function allows(CardStepEnum $step, CardFeeEnum $fee): bool
    {
        return in_array($step, $this->fromSteps(), true) && match ($this) {
            self::MarkPaid => $fee === CardFeeEnum::NotPaid,
            self::Approve => $fee !== CardFeeEnum::NotPaid,
            default => true,
        };
    }

    /** @return list<self> every move a request at `$step` with `$fee` may make, in this order */
    public static function allowedFor(CardStepEnum $step, CardFeeEnum $fee): array
    {
        return array_values(array_filter(self::cases(), fn (self $move): bool => $move->allows($step, $fee)));
    }
}
