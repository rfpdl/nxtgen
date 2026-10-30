<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasDefault;
use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The Cards page's tabs (name cards issue 03): one per step still to do — Asked, Approved, Printed — and one for the
 * closed requests, Given or Refused. `?step=` in the address.
 */
#[TypeScript]
enum CardListTabEnum: string implements HasDefault, HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case Asked = 'asked';
    case Approved = 'approved';
    case Printed = 'printed';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Asked => 'Asked',
            self::Approved => 'Approved',
            self::Printed => 'Printed',
            self::Closed => 'Given or refused',
        };
    }

    /** Plain words, like the steps. */
    public function color(): string
    {
        return 'muted';
    }

    public static function default(): self
    {
        return self::Asked;
    }

    /** @return list<CardStepEnum> the steps this tab lists */
    public function steps(): array
    {
        return match ($this) {
            self::Asked => [CardStepEnum::Asked],
            self::Approved => [CardStepEnum::Approved],
            self::Printed => [CardStepEnum::Printed],
            self::Closed => [CardStepEnum::Given, CardStepEnum::Refused],
        };
    }
}
