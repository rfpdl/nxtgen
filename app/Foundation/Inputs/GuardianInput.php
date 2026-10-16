<?php

declare(strict_types=1);

namespace App\Foundation\Inputs;

use App\Domains\Family\DataObjects\GuardianDetailsData;
use App\Enums\RelationshipEnum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The parent on a registration form: their details (GuardianDetailsInput's rules) and the email. */
#[TypeScript]
final class GuardianInput extends Data
{
    /** @param list<string> $ministries */
    public function __construct(
        public string $first_name,
        public string $last_name,
        public RelationshipEnum $relationship,
        public string $phone,
        public string $email,
        public array $ministries = [],
    ) {}

    /**
     * GuardianDetailsInput's messages, plus the email.
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            ...GuardianDetailsInput::messages(),
            'email.required' => 'Please enter your email.',
            'email.email' => 'Please check your email address.',
        ];
    }

    /**
     * GuardianDetailsInput's rules, plus the email.
     *
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            ...GuardianDetailsInput::rules(),
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    public function toDetails(): GuardianDetailsData
    {
        return new GuardianDetailsData(
            first_name: $this->first_name,
            last_name: $this->last_name,
            relationship: $this->relationship,
            phone: $this->phone,
            email: $this->email,
            ministries: $this->ministries,
        );
    }
}
