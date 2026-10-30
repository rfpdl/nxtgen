<?php

declare(strict_types=1);

namespace App\Services\Church\Data;

use App\Enums\CardListTabEnum;
use App\Foundation\Support\SearchWords;
use Illuminate\Http\Request;

/**
 * Cards' address (`?tab=&q=&page=`), read leniently like the families and children lists: an unknown tab is Asked, a
 * bad page is the first, the search is tidied (SearchWords::tidy) — never an error.
 */
final readonly class CardListQuery
{
    private function __construct(
        public CardListTabEnum $tab,
        public ?string $search,
        public int $page,
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            tab: CardListTabEnum::tryFrom($request->string('tab')->value()) ?? CardListTabEnum::default(),
            search: SearchWords::tidy($request->string('q')->value()),
            page: max(1, $request->integer('page', 1)),
        );
    }
}
