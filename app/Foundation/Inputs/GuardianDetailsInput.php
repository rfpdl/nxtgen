<?php

declare(strict_types=1);

namespace App\Foundation\Inputs;

use App\Domains\Family\DataObjects\GuardianEditData;
use App\Enums\RelationshipEnum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A parent's own details, without the email — the email changes only through the confirm-by-link flow (plan
 * §4.4). The ONE owner of these rules: GuardianInput (registration) adds the email on top of them.
 */
#[TypeScript]
final class GuardianDetailsInput extends Data
{
    /** @param list<string> $ministries */
    public function __construct(
        public string $first_name,
        public string $last_name,
        public RelationshipEnum $relationship,
        public string $phone,
        public array $ministries = [],
    ) {}

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'first_name.required' => 'Please enter your first name.',
            'last_name.required' => 'Please enter your last name.',
            'phone.required' => 'Please enter your mobile number.',
        ];
    }

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'ministries' => ['array', 'max:10'],
            'ministries.*' => ['string', 'max:100'],
        ];
    }

    public function toEdit(): GuardianEditData
    {
        return new GuardianEditData(
            first_name: trim($this->first_name),
            last_name: trim($this->last_name),
            relationship: $this->relationship,
            phone: trim($this->phone),
            ministries: array_values(array_filter(array_map('trim', $this->ministries), fn (string $m): bool => $m !== '')),
        );
    }
}
