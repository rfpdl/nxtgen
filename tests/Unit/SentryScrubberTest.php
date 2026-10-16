<?php

declare(strict_types=1);

use App\Foundation\Support\SentryScrubber;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\ExceptionDataBag;
use Sentry\UserDataBag;

/*
 * No child or parent data in an error report (plan §1: "Sentry with a PII scrubber"). Whatever the request
 * carried — a registration form full of names, allergies and phone numbers — only the shape of the failure
 * leaves the server.
 */

it('strips the request body, query, cookies and headers but keeps where it happened', function (): void {
    $event = Event::createEvent();
    $event->setRequest([
        'url' => 'https://app.test/ccf/register?email=janice@example.com',
        'method' => 'POST',
        'query_string' => 'email=janice@example.com',
        'data' => ['guardian' => ['email' => 'janice@example.com'], 'children' => [['allergies' => 'Peanuts']]],
        'cookies' => ['nxtgen_session' => 'secret'],
        'headers' => ['cookie' => 'secret', 'user-agent' => 'iPhone'],
    ]);

    $request = (new SentryScrubber)($event)->getRequest();
    $sent = (string) json_encode($request);

    expect($request)->toBe(['url' => 'https://app.test/ccf/register', 'method' => 'POST', 'headers' => ['user-agent' => 'iPhone']])
        ->and(str_contains($sent, 'janice') || str_contains($sent, 'Peanuts') || str_contains($sent, 'secret'))->toBeFalse();
});

it('keeps only the login id, never its email or name', function (): void {
    $event = Event::createEvent();
    $event->setUser(UserDataBag::createFromArray(['id' => 7, 'email' => 'leo@example.com', 'username' => 'Leo', 'ip_address' => '1.2.3.4']));

    $user = (new SentryScrubber)($event)->getUser();

    expect($user?->getId())->toBe(7)
        ->and($user?->getEmail())->toBeNull()
        ->and($user?->getUsername())->toBeNull()
        ->and($user?->getIpAddress())->toBeNull();
});

it('drops breadcrumb messages and data (SQL bindings, log lines) but keeps their category', function (): void {
    $event = Event::createEvent();
    $event->setBreadcrumb([new Breadcrumb(Breadcrumb::LEVEL_INFO, Breadcrumb::TYPE_DEFAULT, 'db.sql.query', "select * from children where nickname = 'Amara'", ['bindings' => ['Amara']])]);

    $crumbs = (new SentryScrubber)($event)->getBreadcrumbs();

    expect($crumbs)->toHaveCount(1)
        ->and($crumbs[0]->getCategory())->toBe('db.sql.query')
        ->and($crumbs[0]->getMessage())->toBeNull()
        ->and($crumbs[0]->getMetadata())->toBe([]);
});

// Found in review 3.
it('removes SQL values, emails and phone numbers from error messages', function (): void {
    $event = Event::createEvent();
    $event->setExceptions([
        new ExceptionDataBag(new RuntimeException('Duplicate entry (Connection: mysql, SQL: insert into users (name, email) values (Janice Ramos, janice@example.com))')),
        new ExceptionDataBag(new RuntimeException('Could not reach janice@example.com on +971 50 123 4567')),
    ]);

    $values = array_map(fn (ExceptionDataBag $e): string => $e->getValue(), (new SentryScrubber)($event)->getExceptions());

    expect($values[0])->toBe('Duplicate entry (Connection: mysql, SQL: [removed])')
        ->and($values[1])->toBe('Could not reach [email] on [number]');
});
