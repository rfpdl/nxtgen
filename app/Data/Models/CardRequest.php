<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Enums\CardFeeEnum;
use App\Enums\CardReasonEnum;
use App\Enums\CardStepEnum;
use Carbon\CarbonImmutable;
use Database\Factories\CardRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One ask for a new Name card for one Child (GLOSSARY "Card request"), moving Asked → Approved → Printed → Given, or
 * Refused. At most one OPEN request per Child: the database's `open_child_id` (worked out from the step, unique)
 * holds it — it is never written by the app.
 *
 * @property int $id
 * @property int $church_id
 * @property int $child_id
 * @property CardReasonEnum $reason
 * @property string|null $note
 * @property CardStepEnum $step
 * @property int $fee_minor
 * @property CarbonImmutable|null $paid_at
 * @property int|null $paid_by
 * @property CarbonImmutable|null $approved_at
 * @property int|null $approved_by
 * @property CarbonImmutable|null $printed_at
 * @property int|null $printed_by
 * @property CarbonImmutable|null $given_at
 * @property int|null $given_by
 * @property CarbonImmutable|null $refused_at
 * @property int|null $refused_by
 * @property string|null $refused_reason
 * @property int|null $created_by
 * @property-read int|null $open_child_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Child $child
 */
final class CardRequest extends Model
{
    /** @use HasFactory<CardRequestFactory> */
    use HasFactory;

    protected static function newFactory(): CardRequestFactory
    {
        return CardRequestFactory::new();
    }

    protected $guarded = ['open_child_id'];

    protected function casts(): array
    {
        return [
            'reason' => CardReasonEnum::class,
            'step' => CardStepEnum::class,
            'fee_minor' => 'integer',
            'paid_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'printed_at' => 'immutable_datetime',
            'given_at' => 'immutable_datetime',
            'refused_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Child, $this> */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /** Where the fee stands: free (a first card), paid, or not paid. */
    public function fee(): CardFeeEnum
    {
        return CardFeeEnum::of($this->fee_minor, $this->paid_at);
    }
}
