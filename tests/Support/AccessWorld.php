<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\DuplicateCandidate;
use App\Data\Models\Family;
use App\Data\Models\Note;
use App\Data\Models\RoleAssignment;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Domains\Family\Jobs\CreateDuplicateCandidatesJob;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Storage;

/**
 * The church the access matrix and the response allowlists ask about (TODO #18): church "ccf" with satellites
 * "Home" and "Away"; one family at Home whose child Amara has a photo on file (consent given) and whose parent's
 * email is not yet confirmed, with a staff note; another family at Home; a possible duplicate of the first family;
 * a Home Lifeshaper's role to remove; another church "other" — and one user per Actor. Fake the photos disk before making it.
 */
final readonly class AccessWorld
{
    /** @param array<string, User> $users by Actor value; Anonymous has none */
    private function __construct(
        public Church $church,
        public Satellite $home,
        public Satellite $away,
        public Church $otherChurch,
        public Family $family,
        public Child $child,
        public RoleAssignment $assignment,
        public DuplicateCandidate $pair,
        private array $users,
    ) {}

    public static function make(): self
    {
        $church = church(['slug' => 'ccf']);
        $home = satelliteOf($church, ['name' => 'Home']);
        $away = satelliteOf($church, ['name' => 'Away']);

        $family = familyAt($home, ['area_text' => 'Deira', 'notes' => 'Prefers Tagalog']);
        $parent = User::factory()->unverified()->create(['email' => 'janice@example.com']);
        guardianOf($family, $parent, ['first_name' => 'Janice', 'phone' => '0501112222']);
        $child = childOf($family, ['nickname' => 'Amara', 'gender' => 'girl', 'level' => 2, 'allergies' => 'Peanuts', 'illnesses' => 'Asthma']);
        Storage::disk('photos')->put('children/amara.jpg', 'jpeg');
        $child->forceFill(['photo_path' => 'children/amara.jpg', 'photo_consent_at' => now(), 'photo_consent_by' => $parent->id])->save();
        Note::system($family, 'Registered at the door.', null);

        $otherParent = User::factory()->create();
        $otherFamily = familyAt($home);
        guardianOf($otherFamily, $otherParent, ['first_name' => 'Carla']);
        childOf($otherFamily, ['nickname' => 'Bea']);

        $bothHats = staffMember(RoleEnum::Lifeshaper, atSatellite($home));
        $theirs = familyAt($away);
        guardianOf($theirs, $bothHats, ['first_name' => 'Rhea']);
        childOf($theirs, ['nickname' => 'Cody']);

        $homeParentLifeshaper = staffMember(RoleEnum::Lifeshaper, atSatellite($home));
        $hanas = familyAt($home);
        guardianOf($hanas, $homeParentLifeshaper, ['first_name' => 'Hana']);
        childOf($hanas, ['nickname' => 'Hugo']);

        $revoked = User::factory()->create();
        grantRole($revoked, RoleEnum::Admin, atChurch($church), active: false);
        $revokedSatelliteAdmin = User::factory()->create();
        grantRole($revokedSatelliteAdmin, RoleEnum::Admin, atSatellite($home), active: false);
        $revokedParent = User::factory()->create();
        grantRole($revokedParent, RoleEnum::Lifeshaper, atSatellite($home), active: false);
        $rosas = familyAt($home);
        guardianOf($rosas, $revokedParent, ['first_name' => 'Rosa']);
        childOf($rosas, ['nickname' => 'Rio']);

        // Another church, whose admin is also a parent there — so they can try ccf's ids from their own addresses.
        $otherChurch = church(['slug' => 'other']);
        $otherAdmin = staffMember(RoleEnum::Admin, atChurch($otherChurch));
        guardianOf(familyAt(satelliteOf($otherChurch)), $otherAdmin, ['first_name' => 'Olga']);

        // A new family at Home with the same phone: its possible-duplicate pair is the one answered.
        $newcomer = familyAt($home);
        guardianOf($newcomer, User::factory()->create(), ['first_name' => 'Mary', 'phone' => '0501112222']);
        (new CreateDuplicateCandidatesJob(family: $newcomer))->handle();
        childOf($newcomer, ['nickname' => 'Dina']);

        // A Home Lifeshaper who is no Actor: People removes THEIR role, so no actor loses theirs mid-request.
        $volunteer = staffMember(RoleEnum::Lifeshaper, atSatellite($home));

        return new self(
            church: $church,
            home: $home,
            away: $away,
            otherChurch: $otherChurch,
            family: $family,
            child: $child,
            assignment: RoleAssignment::query()->where('user_id', $volunteer->id)->sole(),
            pair: DuplicateCandidate::query()->sole(),
            users: [
                Actor::Parent->value => $parent,
                Actor::OtherParent->value => $otherParent,
                Actor::ChurchAdmin->value => staffMember(RoleEnum::Admin, atChurch($church)),
                Actor::HomeAdmin->value => staffMember(RoleEnum::Admin, atSatellite($home)),
                Actor::ChurchLifeshaper->value => staffMember(RoleEnum::Lifeshaper, atChurch($church)),
                Actor::HomeLifeshaper->value => staffMember(RoleEnum::Lifeshaper, atSatellite($home)),
                Actor::BothHats->value => $bothHats,
                Actor::HomeParentLifeshaper->value => $homeParentLifeshaper,
                Actor::Revoked->value => $revoked,
                Actor::RevokedSatelliteAdmin->value => $revokedSatelliteAdmin,
                Actor::RevokedParent->value => $revokedParent,
                Actor::AwayAdmin->value => staffMember(RoleEnum::Admin, atSatellite($away)),
                Actor::AwayLifeshaper->value => staffMember(RoleEnum::Lifeshaper, atSatellite($away)),
                Actor::OtherChurch->value => $otherAdmin,
            ],
        );
    }

    /** The actor's login; null for Anonymous. */
    public function user(Actor $actor): ?User
    {
        return $this->users[$actor->value] ?? null;
    }
}
