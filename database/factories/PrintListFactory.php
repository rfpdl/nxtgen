<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\Models\Church;
use App\Data\Models\PrintList;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PrintList> */
final class PrintListFactory extends Factory
{
    protected $model = PrintList::class;

    /** @return array<model-property<PrintList>, mixed> */
    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'made_by' => null,
        ];
    }
}
