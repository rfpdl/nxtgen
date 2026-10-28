<?php

declare(strict_types=1);

namespace App\Domains\CheckIn\DataObjects;

use App\Data\Models\CheckIn;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A child checked in to a Session, on the Attendance page and — row for row — in its Excel: names, when (the church's
 * time) and by whom, and whether they were marked present at the roll call. Their Level is the Squad heading's. No
 * health detail, phone or photo. Needs the Check-in's child and checkedInBy loaded.
 */
#[TypeScript]
final class AttendanceChildData extends Data
{
    public function __construct(
        /** The Check-in's id — the row's key. */
        public int $id,
        public string $nickname,
        public string $full_name,
        /** "13:05", in the church's timezone */
        public string $checked_in_at,
        /** The Staff member's name; "" if their login has gone. */
        public string $checked_in_by,
        /** Marked present at the roll call (DECISIONS 2026-10-28); false = not marked. */
        public bool $present,
    ) {}

    public static function fromCheckIn(CheckIn $checkIn, string $timezone): self
    {
        return new self(
            id: $checkIn->id,
            nickname: $checkIn->child->nickname,
            full_name: $checkIn->child->fullName(),
            checked_in_at: $checkIn->checked_in_at->setTimezone($timezone)->format('H:i'),
            checked_in_by: $checkIn->checkedInBy->name ?? '',
            present: $checkIn->isPresent(),
        );
    }
}
