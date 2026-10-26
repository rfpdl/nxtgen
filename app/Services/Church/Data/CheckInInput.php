<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use App\Foundation\Support\LevelChart;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** "Check in N children": the children of one family ticked at the door, each with its answers. */
#[TypeScript]
final class CheckInInput extends Data
{
    /** @param list<CheckInChildInput> $children */
    public function __construct(
        #[DataCollectionOf(CheckInChildInput::class)]
        public array $children,
    ) {}

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        return [
            'children' => ['required', 'array', 'min:1', 'max:20'],
            'children.*.child_id' => ['required', 'integer'],
            'children.*.level' => ['nullable', 'integer', 'in:'.implode(',', LevelChart::levels())],
            'children.*.make_active' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'children.required' => 'Please tick at least one child.',
            'children.min' => 'Please tick at least one child.',
            'children.*.level.integer' => 'Please choose one of the church\'s Levels.',
            'children.*.level.in' => 'Please choose one of the church\'s Levels.',
        ];
    }
}
