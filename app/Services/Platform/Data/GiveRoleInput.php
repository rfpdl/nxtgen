<?php

declare(strict_types=1);

namespace App\Services\Platform\Data;

use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Give a Role on the platform's People (spec item 12): who, and which Role where — any satellite or class of the church. */
#[TypeScript]
final class GiveRoleInput extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public RoleEnum $role,
        public ScopeTypeEnum $scope_type,
        public int $scope_id,
    ) {}

    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'name.required' => 'Please type their name.',
            'email.email' => 'Please type a real email address.',
        ];
    }
}
