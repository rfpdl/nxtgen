<?php

declare(strict_types=1);

namespace App\Foundation\Inputs;

use App\Domains\Family\DataObjects\GuardianDetailsData;
use App\Enums\RelationshipEnum;
use Spatie\LaravelData\Data;

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

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'first_name.required' => 'Please enter your first name.',
            'last_name.required' => 'Please enter your last name.',
            'phone.required' => 'Please enter your mobile number.',
            'email.required' => 'Please enter your email.',
            'email.email' => 'Please check your email address.',
        ];
    }

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'ministries' => ['array', 'max:10'],
            'ministries.*' => ['string', 'max:100'],
        ];
    }

    public function toDetails(): GuardianDetailsData
    {
        return new GuardianDetailsData(
            firstName: $this->first_name,
            lastName: $this->last_name,
            relationship: $this->relationship,
            phone: $this->phone,
            email: $this->email,
            ministries: $this->ministries,
        );
    }
}
