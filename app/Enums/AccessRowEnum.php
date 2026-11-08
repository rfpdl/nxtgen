<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The rows of the access matrix (plan §3.2) that Release 1 serves. Each staff or parent Feature declares one
 * with #[AccessRow] (AccessRowTest); AccessScope enforces it; Feature tests prove it. Release 2 adds the
 * class (ManageClasses), check-in and report (Attendance) rows; name cards add CardRequest and Cards.
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
    case ManageClasses = 'manage_classes';
    case CheckIn = 'check_in';
    case Attendance = 'attendance';
    case CardRequest = 'card_request';
    case Cards = 'cards';
    case MovingUp = 'moving_up';
    case ManageLinks = 'manage_links';
    case DeleteChildPhoto = 'delete_child_photo';

    /** The rule, in the matrix's words: who may. */
    public function label(): string
    {
        return match ($this) {
            self::StaffArea => 'Any active role in this church',
            self::DoorSearch => 'Door search, church-wide lite view: any staff',
            self::RegisterFamily => 'Register a new family + child: any staff',
            self::AddChild => 'Add a child to a family found by search: any staff',
            self::FamilyList => 'Registration lists (families, children) + Excel: the families of the satellite in the address, for anyone with a Role there',
            self::ViewFamily => 'Open a family: staff who can see its home satellite',
            self::EditFamily => 'Edit family/guardian/child details: staff who can open the family (the home satellite\'s admins and Lifeshapers); moving it to another satellite, admins only',
            self::ResendLink => 'Resend confirmation link: anyone with a staff role, from their own satellite\'s address (a visiting family too)',
            self::CorrectContact => 'Correct family contact (§4.4): an admin of the home satellite',
            self::SetChildStatus => 'Set child status: an admin of the home satellite',
            self::SetChildLevel => 'Correct a child\'s Level outside Moving-Up: an admin of the child\'s satellite',
            self::ReviewDuplicates => 'Review possible duplicates: admins, for families they may edit',
            self::GrantRoles => 'Grant roles: an admin gives any Role at their satellite (Admin at its classes); never the last admin of a satellite removed',
            self::ChildPhoto => 'Record the parent\'s in-person photo consent, and take or upload a child\'s photo once consent is recorded: staff who can see the family',
            self::SeeChildPhoto => 'See a child\'s photo: the child\'s own family, or staff who can see the family; only while consent stands',
            self::ManageClasses => 'Classes (Schedules): see, add, edit and retire — satellite admin at their own; a class admin their class',
            self::CheckIn => 'Check children in at the door, and undo: anyone with a Role at that satellite; any child of the church',
            self::Attendance => 'Attendance (a Session\'s children by Squad) + Excel: satellite admin at their own; a class admin their class',
            self::CardRequest => 'File a Card request (a lost or damaged name card) for a child, and mark a printed card given at the desk: staff who can see the family',
            self::Cards => 'Cards (name card requests): the page — an admin of the satellite in the address, its families; mark paid, approve, refuse, mark printed — an admin of the family\'s home satellite',
            self::MovingUp => 'Moving-Up (every child to the next Level) and putting the last one back: per satellite, its admins',
            self::ManageLinks => 'Links (a satellite\'s own addresses on its QR poster): add, change, order, remove — satellite admin at their own',
            self::DeleteChildPhoto => 'Delete a child\'s photo for good (a parent asking for erasure; it cannot be undone): an admin who may edit the family, in Admin',
            self::OwnFamily => 'The parent\'s own family: view, edit details, add/edit children (not status/Level)',
        };
    }

    /** Shown as plain text, never as a coloured badge. */
    public function color(): string
    {
        return 'muted';
    }
}
