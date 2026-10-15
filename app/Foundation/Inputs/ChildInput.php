<?php

declare(strict_types=1);

namespace App\Foundation\Inputs;

use App\Domains\Family\DataObjects\ChildDetailsData;
use App\Enums\GenderEnum;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

final class ChildInput extends Data
{
    public function __construct(
        public string $first_name,
        public ?string $middle_name,
        public string $last_name,
        public ?string $nickname,
        public GenderEnum $gender,
        public string $date_of_birth,
        public ?string $allergies,
        public ?string $illnesses = null,
        public ?string $dislikes = null,
        public ?string $fears = null,
        public ?string $parent_notes = null,
    ) {}

    /**
     * Plain words for parents.
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'first_name.required' => 'Please enter the first name.',
            'last_name.required' => 'Please enter the last name.',
            'gender.required' => 'Please choose boy or girl.',
            'gender.enum' => 'Please choose boy or girl.',
            'date_of_birth.required' => 'Please enter the birthday.',
            'date_of_birth.date' => 'Please enter the birthday.',
            'date_of_birth.before' => 'The birthday must be in the past.',
        ];
    }

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::enum(GenderEnum::class)],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'nickname' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['required', 'date', 'before:today', 'after:1990-01-01'],
            'allergies' => ['nullable', 'string', 'max:500'],
            'illnesses' => ['nullable', 'string', 'max:500'],
            'dislikes' => ['nullable', 'string', 'max:500'],
            'fears' => ['nullable', 'string', 'max:500'],
            'parent_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function toDetails(): ChildDetailsData
    {
        return new ChildDetailsData(
            firstName: $this->first_name,
            middleName: $this->middle_name,
            lastName: $this->last_name,
            nickname: (string) $this->nickname,
            gender: $this->gender,
            dateOfBirth: $this->date_of_birth,
            allergies: $this->allergies,
            illnesses: $this->illnesses,
            dislikes: $this->dislikes,
            fears: $this->fears,
            parentNotes: $this->parent_notes,
        );
    }
}
