<?php

declare(strict_types=1);

namespace App\Data\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PrintListFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/** The approved cards an admin put on one list to print (GLOSSARY "Print list") — exactly those, kept when it was made:
 * downloaded as Excel and marked printed together.
 *
 * @property int $id
 * @property int $church_id
 * @property int|null $made_by
 * @property CarbonImmutable $created_at
 * @property-read User|null $maker
 * @property-read int|null $cards_count
 * @property-read int|null $to_print_count
 */
final class PrintList extends Model
{
    /** @use HasFactory<PrintListFactory> */
    use HasFactory;

    protected static function newFactory(): PrintListFactory
    {
        return PrintListFactory::new();
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Church, $this> */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /**
     * The Card requests on it, as they were when it was made.
     *
     * @return BelongsToMany<CardRequest, $this>
     */
    public function cards(): BelongsToMany
    {
        return $this->belongsToMany(CardRequest::class, 'print_list_cards');
    }

    /**
     * Its cards' ids, as a sub-query on the membership table itself — so an UPDATE of card_requests may use it (MySQL
     * refuses a sub-query on the table being updated).
     */
    public function cardIds(): QueryBuilder
    {
        return DB::table('print_list_cards')->where('print_list_id', $this->id)->select('card_request_id');
    }

    /**
     * The admin who made it (null if their login has since gone).
     *
     * @return BelongsTo<User, $this>
     */
    public function maker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'made_by');
    }
}
