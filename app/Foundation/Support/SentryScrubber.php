<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\UserDataBag;

/**
 * Sentry's `before_send`: no child or parent data leaves the server in an error report (plan §1). Keeps the
 * shape of the failure — exception, stack, route, method, breadcrumb categories, the login id — and drops
 * everything a person typed or a query carried: request body, query string, cookies, headers (bar the
 * user-agent), the login's email/name/IP, breadcrumb messages and data (SQL bindings, log lines), extras —
 * and from every exception MESSAGE the SQL (Laravel puts the bound values — names, emails — in it), emails and
 * long digit runs (phones). Review 3 found the messages were sent untouched.
 */
final class SentryScrubber
{
    /** The static form `config/sentry.php` can hold (config caching cannot store a closure or an object). */
    public static function beforeSend(Event $event, ?EventHint $hint = null): Event
    {
        return (new self)($event, $hint);
    }

    public function __invoke(Event $event, ?EventHint $hint = null): Event
    {
        $request = $event->getRequest();
        if ($request !== []) {
            $url = $request['url'] ?? null;
            $headers = $request['headers'] ?? [];
            $event->setRequest(array_filter([
                'url' => is_string($url) ? strtok($url, '?') : null,
                'method' => $request['method'] ?? null,
                'headers' => is_array($headers) && isset($headers['user-agent']) ? ['user-agent' => $headers['user-agent']] : null,
            ], fn (mixed $v): bool => $v !== null && $v !== false));
        }

        $user = $event->getUser();
        $event->setUser($user?->getId() !== null ? UserDataBag::createFromUserIdentifier($user->getId()) : null);

        $event->setBreadcrumb(array_map(
            fn (Breadcrumb $b): Breadcrumb => new Breadcrumb($b->getLevel(), $b->getType(), $b->getCategory(), null, [], $b->getTimestamp()),
            $event->getBreadcrumbs(),
        ));
        $event->setExtra([]);

        foreach ($event->getExceptions() as $exception) {
            $exception->setValue(self::clean($exception->getValue()));
        }

        return $event;
    }

    /** An error message without data: SQL text (with its values), emails, phone-like digit runs. */
    public static function clean(string $message): string
    {
        $message = (string) preg_replace('/SQL: .*\)$/s', 'SQL: [removed])', $message);
        $message = (string) preg_replace('/[^\s@()<>,;:"\']+@[^\s@()<>,;:"\']+\.[A-Za-z]{2,}/', '[email]', $message);

        return (string) preg_replace('/\+?\d[\d\s\-]{6,}\d/', '[number]', $message);
    }
}
