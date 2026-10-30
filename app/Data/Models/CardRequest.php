<?php

declare(strict_types=1);

namespace App\Data\Models;

use App\Enums\CardFeeEnum;
use App\Enums\CardMoveEnum;
use App\Enums\CardReasonEnum;
use App\Enums\CardStepEnum;
use App\Foundation\Support\SearchWords;
use Carbon\CarbonImmutable;
use Database\Factories\CardRequestFactory;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Only requests whose child's family lives at one of `$satelliteIds` — null = every satellite (a church admin):
     * the requests an admin sees on Cards, and marks printed all at once.
     *
     * @param  Builder<self>  $query
     * @param  list<int>|null  $satelliteIds
     */
    public function scopeOfFamiliesAt(Builder $query, ?array $satelliteIds): void
    {
        $query->when($satelliteIds !== null, fn (Builder $q) => $q->whereHas('child.family', fn (Builder $f) => $f->whereIn('home_satellite_id', $satelliteIds ?? [])));
    }

    /**
     * Only requests for a child whose names hold every word of `$search` (SearchWords, as the children list reads a
     * search; a phone number matches no child) — null = no search. Cards' list, its print list and "Mark these
     * printed" all narrow with this, so they are always the same set.
     *
     * @param  Builder<self>  $query
     */
    public function scopeForChildNamed(Builder $query, ?string $search): void
    {
        $query->when($search !== null, fn (Builder $q) => SearchWords::of((string) $search)->apply(
            $q,
            fn (Builder $one, string $word) => $one->whereHas('child', fn (Builder $c) => $c
                ->where('first_name', 'like', SearchWords::like($word))
                ->orWhere('middle_name', 'like', SearchWords::like($word))
                ->orWhere('last_name', 'like', SearchWords::like($word))
                ->orWhere('nickname', 'like', SearchWords::like($word))),
            fn (Builder $one) => $one->whereRaw('1 = 0'),
        ));
    }

    /** Where the fee stands: free (a first card), paid, or not paid. */
    public function fee(): CardFeeEnum
    {
        return CardFeeEnum::of($this->fee_minor, $this->paid_at);
    }

    /** @return list<CardMoveEnum> the moves it may make now (CardMoveEnum is their one rule) */
    public function moves(): array
    {
        return CardMoveEnum::allowedFor($this->step, $this->fee());
    }
}
