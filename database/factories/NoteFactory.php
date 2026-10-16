<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\Models\Church;
use App\Data\Models\Note;
use App\Enums\NoteKindEnum;
use App\Enums\NoteVisibilityEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A system note, as CreateSystemNoteJob writes one. Attach it with `->for($familyOrChild, 'noteable')`.
 *
 * @extends Factory<Note>
 */
final class NoteFactory extends Factory
{
    protected $model = Note::class;

    /** @return array<model-property<Note>, mixed> */
    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'author_id' => null,
            'kind' => NoteKindEnum::System,
            'body' => fake()->sentence(),
            'visibility' => NoteVisibilityEnum::default(),
        ];
    }
}
