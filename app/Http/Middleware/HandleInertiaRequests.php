<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Data\Models\User;
use App\Domains\Church\Jobs\GetLandingOptionsJob;
use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * Props every page receives.
 *
 * Every flash key here must also be READ by a screen — the FlashContractTest enforces both legs, because a
 * key flashed and shared but read by nothing is a message nobody ever sees (PoC lesson).
 */
final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user instanceof User ? [
                    'id' => $user->id,
                    'name' => $user->name,
                ] : null,
                // More than one place to go (a parent who serves, or two churches) → the "Switch" link.
                'canSwitch' => fn (): bool => $user instanceof User && count((new GetLandingOptionsJob(user: $user))->handle()) > 1,
            ],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
