<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasDefault;
use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Where a Card request is (GLOSSARY "Card request", DECISIONS 2026-10-29): Asked → Approved → Printed → Given, or
 * Refused. Given and Refused close it; a Child has at most one open request.
 */
#[TypeScript]
enum CardStepEnum: string implements HasDefault, HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case Asked = 'asked';
    case Approved = 'approved';
    case Printed = 'printed';
    case Given = 'given';
    case Refused = 'refused';

    public function label(): string
    {
        return match ($this) {
            self::Asked => 'Asked',
            self::Approved => 'Approved',
            self::Printed => 'Printed',
            self::Given => 'Given',
            self::Refused => 'Refused',
        };
    }

    /** Plain words: a Card request's colour is its fee's (CardFeeEnum), so there is one colour rule. */
    public function color(): string
    {
        return 'muted';
    }

    public static function default(): self
    {
        return self::Asked;
    }

    public function isOpen(): bool
    {
        return $this !== self::Given && $this !== self::Refused;
    }

    /**
     * The open steps: the ones the database holds to one request per Child (the card_requests migration's
     * `open_child_id`, pinned to this by CardRequestInvariantsTest).
     *
     * @return list<self>
     */
    public static function openSteps(): array
    {
        return array_values(array_filter(self::cases(), fn (self $step): bool => $step->isOpen()));
    }
}
