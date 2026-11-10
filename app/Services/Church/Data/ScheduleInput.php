<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use App\Enums\WeekdayEnum;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Adding or editing a class (Schedule; spec item 14): a name; weekly on a day, or one-time on a date (`one_time`
 * says which — the other is not kept); a start and an end time (HH:MM), the end after the start. Adding one adds it
 * at the satellite in the address.
 */
#[TypeScript]
final class ScheduleInput extends Data
{
    public function __construct(
        public string $name,
        public bool $one_time,
        public ?WeekdayEnum $weekday,
        public ?string $date,
        public string $starts_at,
        public string $ends_at,
    ) {}

    /** The day a weekly class meets; null for a one-time class, whatever was sent. */
    public function weekly(): ?WeekdayEnum
    {
        return $this->one_time ? null : $this->weekday;
    }

    /** A one-time class's date; null for a weekly class, whatever was sent. */
    public function oneTimeDate(): ?string
    {
        return $this->one_time ? $this->date : null;
    }

    /**
     * With `date_format`, Laravel's `after:starts_at` compares the two times.
     *
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'one_time' => ['boolean'],
            // Only the one `one_time` says is read: a leftover in the other never blocks the save.
            'weekday' => ['exclude_if:one_time,true,1', 'required', Rule::enum(WeekdayEnum::class)],
            'date' => ['exclude_unless:one_time,true,1', 'required', 'date_format:Y-m-d'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'name.required' => 'Please type a name for the class.',
            'weekday.required' => 'Please choose the day it meets.',
            'weekday.enum' => 'Please choose the day it meets.',
            'date.required' => 'Please choose the date.',
            'date.date_format' => 'Please choose the date.',
            'starts_at.required' => 'Please type when it starts, e.g. 13:00.',
            'ends_at.required' => 'Please type when it ends, e.g. 15:00.',
            'starts_at.date_format' => 'Please type the time as HH:MM, e.g. 13:00.',
            'ends_at.date_format' => 'Please type the time as HH:MM, e.g. 13:00.',
            'ends_at.after' => 'The class must end after it starts.',
        ];
    }
}
