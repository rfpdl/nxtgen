<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use App\Foundation\Support\LevelChart;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A church admin's Level choice for one child — always one of the church-wide chart's Levels. */
#[TypeScript]
final class UpdateChildLevelInput extends Data
{
    public function __construct(public int $level) {}

    /** @return array<string, list<string|In>> */
    public static function rules(): array
    {
        return ['level' => ['required', 'integer', Rule::in(LevelChart::levels())]];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        $message = 'Please choose one of the church\'s Levels.';

        return ['level.required' => $message, 'level.integer' => $message, 'level.in' => $message];
    }
}
