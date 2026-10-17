<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The rows of the access matrix (plan §3.2) that Release 1 serves. Each staff or parent Feature declares one
 * with #[AccessRow] (AccessRowTest); AccessScope enforces it; Feature tests prove it. Release 2 adds the
 * class, event, check-in and report rows.
 */
#[TypeScript]
enum AccessRowEnum: string implements HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case StaffArea = 'staff_area';
    case DoorSearch = 'door_search';
    case RegisterFamily = 'register_family';
    case AddChild = 'add_child';
    case FamilyList = 'family_list';
    case ViewFamily = 'view_family';
    case EditFamily = 'edit_family';
    case ResendLink = 'resend_link';
    case CorrectContact = 'correct_contact';
    case SetChildStatus = 'set_child_status';
    case SetChildLevel = 'set_child_level';
    case ReviewDuplicates = 'review_duplicates';
    case GrantRoles = 'grant_roles';
    case OwnFamily = 'own_family';
    case ChildPhoto = 'child_photo';
    case SeeChildPhoto = 'see_child_photo';

    /** The rule, in the matrix's words: who may. */
    public function label(): string
    {
        return match ($this) {
            self::StaffArea => 'Any active role in this church',
            self::DoorSearch => 'Door search, church-wide lite view: any staff',
            self::RegisterFamily => 'Register a new family + child: any staff',
            self::AddChild => 'Add a child to a family found by search: any staff',
            self::FamilyList => 'Registration lists (families, children) + Excel: own satellites (church-wide roles: all)',
            self::ViewFamily => 'Open a family: staff who can see its home satellite',
            self::EditFamily => 'Edit family/guardian/child details: church admin; admin of the home satellite',
            self::ResendLink => 'Resend confirmation link: anyone with a staff role in the church',
            self::CorrectContact => 'Correct family contact (§4.4): church admin; home-satellite admin',
            self::SetChildStatus => 'Set child status: church admin; home-satellite admin',
            self::SetChildLevel => 'Correct a child\'s Level outside Moving-Up: church admin only',
            self::ReviewDuplicates => 'Review possible duplicates: admins, for families they may edit',
            self::GrantRoles => 'Grant roles: church admin any; satellite admin Lifeshaper in own satellite',
            self::ChildPhoto => 'Record the parent\'s in-person photo consent, and take or upload a child\'s photo once consent is recorded: staff who can see the family',
            self::SeeChildPhoto => 'See a child\'s photo: the child\'s own family, or staff who can see the family; only while consent stands',
            self::OwnFamily => 'The parent\'s own family: view, edit details, add/edit children (not status/Level)',
        };
    }

    /** Shown as plain text, never as a coloured badge. */
    public function color(): string
    {
        return 'muted';
    }
}
