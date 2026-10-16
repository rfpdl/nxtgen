<?php

declare(strict_types=1);

namespace App\Domains\Registry\Values;

/**
 * Where each answer sits in the Registry tab export — by POSITION, because the sheet has two columns named
 * "Complete Name". Private to the import's parser: it never reaches a page, so it is not one of `app/Enums`
 * (which are generated to TypeScript and carry labels).
 */
enum RegistryCsvColumn: int
{
    case FirstName = 0;
    case LastName = 1;
    case Nickname = 2;
    case Gender = 3;
    case BirthYear = 4;
    case BirthMonth = 5;
    case BirthDay = 6;
    case Allergies = 8;
    case Dislikes = 9;
    case Fears = 10;
    case Notes = 11;
    case WantsFbGroup = 12;
    case FbAccount = 13;
    case Guardian = 14;
    case Relationship = 15;
    case Phone = 16;
    case Email = 17;
    case Area = 18;
    case InDgroup = 19;
    case DgroupArea = 20;
    case DgroupLeader = 21;
    case Ministry = 22;
    case WantsDgroup = 23;
    case Lifestage = 24;
    case Privacy = 25;
    case DgroupLeaderPhone = 26;
    case Schedule = 27;
    case Status = 33;

    /** How many cells a record must have: up to and including the last column read. */
    public static function width(): int
    {
        return self::Status->value + 1;
    }
}
