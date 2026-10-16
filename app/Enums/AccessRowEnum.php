<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The rows of the access matrix (plan §3.2) that Release 1 serves. Each staff or parent Feature declares one
 * with #[AccessRow] (AccessRowTest); AccessScope enforces it; Feature tests prove it. Release 2 adds the
 * class, event, check-in and report rows.
 */
enum AccessRowEnum: string
{
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
    case SetChildGroup = 'set_child_group';
    case ReviewDuplicates = 'review_duplicates';
    case GrantRoles = 'grant_roles';
    case OwnFamily = 'own_family';
    case ChildPhoto = 'child_photo';
    case SeeChildPhoto = 'see_child_photo';

    /** The rule, in the matrix's words: who may. */
    public function rule(): string
    {
        return match ($this) {
            self::StaffArea => 'Any active role in this church',
            self::DoorSearch => 'Door search, church-wide lite view: any staff',
            self::RegisterFamily => 'Register a new family + child: any staff',
            self::AddChild => 'Add a child to a family found by search: any staff',
            self::FamilyList => 'Registration lists (families, children) + Excel: own satellites (church-wide roles: all)',
            self::ViewFamily => 'Open a family: staff who can see its home satellite',
            self::EditFamily => 'Edit family/guardian/child details: church admin; admin of the home satellite',
            self::ResendLink => 'Resend confirmation link: church admin; home-satellite admin; any Lifeshaper',
            self::CorrectContact => 'Correct family contact (§4.4): church admin; home-satellite admin',
            self::SetChildStatus => 'Set child status: church admin; home-satellite admin',
            self::SetChildGroup => 'Set child group outside Moving-Up: church admin only',
            self::ReviewDuplicates => 'Review possible duplicates: admins, for families they may edit',
            self::GrantRoles => 'Grant roles: church admin any; satellite admin Lifeshaper in own satellite',
            self::ChildPhoto => 'Record the parent\'s in-person photo consent, and take or upload a child\'s photo once consent is recorded: staff who can see the family',
            self::SeeChildPhoto => 'See a child\'s photo: the child\'s own family, or staff who can see the family; only while consent stands',
            self::OwnFamily => 'The parent\'s own family: view, edit details, add/edit children (not status/group)',
        };
    }
}
