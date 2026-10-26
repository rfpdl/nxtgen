<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use App\Enums\WeekdayEnum;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Editing a class (Schedule): a name, and an optional day and start/end time (HH:MM). The end must come after
 * the start when both are given. Adding one also says where: AddScheduleInput.
 */
#[TypeScript]
final class ScheduleInput extends Data
{
    public function __construct(
        public string $name,
        public ?WeekdayEnum $weekday,
        public ?string $starts_at,
        public ?string $ends_at,
    ) {}

    /**
     * Shared with AddScheduleInput. With `date_format`, Laravel's `after:starts_at` compares the two times, and
     * passes when there is no start to come after.
     *
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'weekday' => ['nullable', Rule::enum(WeekdayEnum::class)],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'name.required' => 'Please type a name for the class.',
            'weekday.enum' => 'Please choose a day, or no set day.',
            'starts_at.date_format' => 'Please type the time as HH:MM, e.g. 13:00.',
            'ends_at.date_format' => 'Please type the time as HH:MM, e.g. 13:00.',
            'ends_at.after' => 'The class must end after it starts.',
        ];
    }
}
