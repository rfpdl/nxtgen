<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use App\Domains\Church\DataObjects\RoleScope;
use App\Enums\RoleEnum;
use App\Enums\ScopeTypeEnum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** "Add someone": who, and which role where. Whether the viewer may give that role is AccessScope::canGrant. */
#[TypeScript]
final class AddStaffInput extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public RoleEnum $role,
        public ScopeTypeEnum $scope_type,
        public int $scope_id,
    ) {}

    /** Where the role is to be given — the shape AccessScope::canGrant and StaffRoleData::label read. */
    public function scope(): RoleScope
    {
        return new RoleScope($this->scope_type, $this->scope_id);
    }

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
