<?php

declare(strict_types=1);

namespace App\Data\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Which sheet row became which child (plan §7) — reruns of the import skip mapped rows.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $source_child_key
 * @property string $approved_family_key
 * @property int $child_id
 * @property int $family_id
 */
final class RegistryImportMapping extends Model
{
    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['imported_at' => 'immutable_datetime'];
    }
}
