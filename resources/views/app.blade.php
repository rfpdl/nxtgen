<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
<head>
    <meta charset="utf-8">

    {{--
        `maximum-scale` and `user-scalable=no` are deliberately ABSENT.

        Locking zoom is the standard thing to do on a "web app" and it would be actively harmful here:
        the users are volunteers, some of them older, and pinch-to-zoom is the accessibility affordance
        their phone already gives them. The in-app text-size control (plan §5.10) is there because it
        scales hit areas too, not because zoom is being taken away.

        `viewport-fit=cover` plus the safe-area padding below keeps the door's controls clear of a home
        indicator on a phone held one-handed.
    --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <title inertia>{{ config('app.name') }}</title>

    @routes
    @vite(['resources/js/app.ts'])
    @inertiaHead
</head>
<body class="min-h-dvh bg-surface text-text">
    @inertia
</body>
</html>
