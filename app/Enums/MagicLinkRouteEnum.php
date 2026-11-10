<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasEnumMetadata;
use App\Enums\Concerns\ProvidesEnumMetadata;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * What an emailed link is for, as its address says it: /magic/{login|verify|claim}/{token}. The ONE mapping
 * between a link's stored purpose (MagicLinkPurposeEnum), its address and the route its Continue button posts
 * to — the email, the routes and the confirm page all read it here.
 */
#[TypeScript]
enum MagicLinkRouteEnum: string implements HasEnumMetadata
{
    use ProvidesEnumMetadata;

    case Login = 'login';
    case Verify = 'verify';
    case Claim = 'claim';

    public static function fromPurpose(MagicLinkPurposeEnum $purpose): self
    {
        return match ($purpose) {
            MagicLinkPurposeEnum::Login => self::Login,
            MagicLinkPurposeEnum::VerifyEmail => self::Verify,
            MagicLinkPurposeEnum::ClaimFamily => self::Claim,
        };
    }

    /** @return list<string> the address segments, for the route's `whereIn` */
    public static function segments(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }

    /** The named route the confirm page's button posts to. */
    public function routeName(): string
    {
        return match ($this) {
            self::Login => 'auth.magic.login',
            self::Verify => 'auth.magic.verify',
            self::Claim => 'auth.magic.claim',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Login => 'Sign in',
            self::Verify => 'Confirm your email',
            self::Claim => 'Connect your family',
        };
    }

    /** What pressing the confirm page's button does, under its title (label()). */
    public function prompt(): string
    {
        return match ($this) {
            self::Login => 'Press the button to finish signing in.',
            self::Verify => 'Press the button to tell us this email is yours.',
            self::Claim => 'Press the button to connect your family to this email.',
        };
    }

    /** The confirm page's one button. */
    public function button(): string
    {
        return match ($this) {
            self::Login => 'Continue to sign in',
            self::Verify => 'Yes, this is my email',
            self::Claim => 'Connect my family',
        };
    }

    /** Shown as plain text, never as a coloured badge. */
    public function color(): string
    {
        return 'muted';
    }
}
