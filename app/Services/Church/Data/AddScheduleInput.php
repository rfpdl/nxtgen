<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use App\Enums\WeekdayEnum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Adding a class (Schedule): which satellite, then the same fields and rules as editing one (ScheduleInput).
 * Whether the viewer may add there is AccessScope::canManageClassesAt.
 */
#[TypeScript]
final class AddScheduleInput extends Data
{
    public function __construct(
        public int $satellite_id,
        public string $name,
        public ?WeekdayEnum $weekday,
        public ?string $starts_at,
        public ?string $ends_at,
    ) {}

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        return ['satellite_id' => ['required', 'integer'], ...ScheduleInput::rules()];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return ScheduleInput::messages();
    }
}
