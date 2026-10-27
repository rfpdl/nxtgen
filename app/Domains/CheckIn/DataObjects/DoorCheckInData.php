<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\CheckIn;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A child checked in to the chosen class today, on the door's list (DECISIONS 2026-10-27): nickname, this
 * satellite's Squad and when — no photo, no phone, no health detail. `id` is the Check-in's, for Undo. Needs the
 * Check-in's child and squad loaded (GetCheckedInChildrenJob does).
 */
#[TypeScript]
final class DoorCheckInData extends Data
{
    public function __construct(
        /** The Check-in's id — the row's key and what Undo undoes. */
        public int $id,
        public string $nickname,
        public string $squad,
        /** "13:05", in the church's timezone */
        public string $checked_in_at,
    ) {}

    public static function fromCheckIn(CheckIn $checkIn, string $timezone): self
    {
        return new self(
            id: $checkIn->id,
            nickname: $checkIn->child->displayName(),
            squad: $checkIn->squad->name(),
            checked_in_at: $checkIn->checked_in_at->setTimezone($timezone)->format('H:i'),
        );
    }
}
