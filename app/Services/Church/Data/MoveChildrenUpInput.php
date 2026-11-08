<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use App\Foundation\Support\LevelChart;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** "Move up N children": the children the satellite admin left ticked on the Moving-Up page. */
#[TypeScript]
final class MoveChildrenUpInput extends Data
{
    /** @param list<MoveChildUpInput> $children */
    public function __construct(
        #[DataCollectionOf(MoveChildUpInput::class)]
        public array $children,
    ) {}

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        return [
            'children' => ['required', 'array', 'min:1', 'max:5000'],
            'children.*.child_id' => ['required', 'integer', 'distinct'],
            'children.*.level' => ['required', 'integer', 'in:'.implode(',', LevelChart::levels())],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'children.required' => 'Please tick at least one child to move up.',
            'children.min' => 'Please tick at least one child to move up.',
        ];
    }

    /** @return array<int, int> child id => the Level the page showed */
    public function expected(): array
    {
        $expected = [];
        foreach ($this->children as $child) {
            $expected[$child->child_id] = $child->level;
        }

        return $expected;
    }
}
