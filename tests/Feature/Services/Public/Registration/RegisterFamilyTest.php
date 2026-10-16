<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Data\Models\Satellite;
use App\Data\Models\User;
use App\Enums\RegistrationOutcomeEnum;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Tests\Support\church;
use function Tests\Support\satelliteOf;

/** @return array{0: Church, 1: Satellite} */
function ccf(): array
{
    $church = church(['slug' => 'ccf', 'name' => 'CCF']);

    return [$church, satelliteOf($church, ['name' => 'CCF DXB'])];
}

/**
 * @return array{home_satellite_id: int, area_text: string, privacy_consented: bool,
 *     guardian: array<string, mixed>, children: list<array<string, mixed>>}
 */
function validForm(Satellite $satellite): array
{
    return [
        'home_satellite_id' => $satellite->id,
        'area_text' => 'Al Barsha',
        'privacy_consented' => true,
        'guardian' => [
            'first_name' => 'Janice', 'last_name' => 'Ramos', 'relationship' => 'mother',
            'phone' => '0544897346', 'email' => 'janice@example.com', 'ministries' => ['Music'],
        ],
        'children' => [[
            'first_name' => 'Alexis', 'middle_name' => 'Amara', 'last_name' => 'Ramos', 'nickname' => 'Amara',
            'gender' => 'girl', 'date_of_birth' => '2019-05-10', 'has_allergies' => true, 'allergies' => 'Peanuts',
        ]],
    ];
}

it('shows the registration form with the church satellites @story#2', function (): void {
    [, $satellite] = ccf();

    get('/ccf/register')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Public/Register')
        ->where('church.name', 'CCF')
        ->where('satellites.0.id', $satellite->id)
        ->where('satellites.0.name', 'CCF DXB')
        ->has('enums.relationship')
        ->has('enums.gender'));
});

it('404s for a church that does not exist @story#3', function (): void {
    get('/nope/register')->assertNotFound();
});

it('registers a family and thanks the parent @story#4', function (): void {
    Notification::fake();
    [, $satellite] = ccf();

    post('/ccf/register', validForm($satellite))
        ->assertRedirect('/ccf/register/thanks')
        ->assertSessionHas('success', RegistrationOutcomeEnum::Registered->guardianMessage());

    expect(Family::query()->count())->toBe(1)
        ->and(Child::query()->sole()->nickname)->toBe('Amara')
        ->and(User::query()->sole()->email)->toBe('janice@example.com');
});

it('registers several children at once @story#4', function (): void {
    Notification::fake();
    [, $satellite] = ccf();

    $form = validForm($satellite);
    $form['children'][] = ['first_name' => 'Aaron', 'middle_name' => null, 'last_name' => 'Ramos', 'nickname' => 'Aaron', 'gender' => 'boy', 'date_of_birth' => '2017-01-02', 'has_allergies' => false, 'allergies' => null];

    post('/ccf/register', $form)->assertRedirect('/ccf/register/thanks');

    expect(Child::query()->count())->toBe(2);
});

it('does not register an already-registered email twice, and says nothing that reveals it @story#5', function (): void {
    Notification::fake();
    [, $satellite] = ccf();
    post('/ccf/register', validForm($satellite));

    post('/ccf/register', validForm($satellite))
        ->assertRedirect('/ccf/register/thanks')
        ->assertSessionHas('success', RegistrationOutcomeEnum::Registered->guardianMessage());

    expect(Family::query()->count())->toBe(1);
});

it('gives the same public answer for every registration outcome (no membership oracle) @story#5', function (): void {
    $messages = array_unique(array_map(fn (RegistrationOutcomeEnum $o): string => $o->guardianMessage(), RegistrationOutcomeEnum::cases()));

    expect($messages)->toHaveCount(1);
});

it('shows the thank-you page @story#7', function (): void {
    ccf();

    get('/ccf/register/thanks')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p->component('Public/RegisterThanks'));
});

it('requires the privacy agreement', function (): void {
    [, $satellite] = ccf();

    $form = validForm($satellite);
    $form['privacy_consented'] = false;

    post('/ccf/register', $form)->assertSessionHasErrors('privacy_consented');
});

it('requires at least one child', function (): void {
    [, $satellite] = ccf();
    $form = validForm($satellite);
    $form['children'] = [];

    post('/ccf/register', $form)->assertSessionHasErrors('children');
});

it('requires the parent email, name and phone and each child\'s name, gender and birthday', function (): void {
    [, $satellite] = ccf();
    $form = validForm($satellite);
    $form['guardian']['email'] = '';
    $form['guardian']['phone'] = '';
    $form['children'][0]['first_name'] = '';
    $form['children'][0]['gender'] = 'dragon';
    $form['children'][0]['date_of_birth'] = '2999-01-01';

    post('/ccf/register', $form)->assertSessionHasErrors([
        'guardian.email', 'guardian.phone', 'children.0.first_name', 'children.0.gender', 'children.0.date_of_birth',
    ]);
});

it('refuses a satellite from another church @story#6', function (): void {
    ccf();
    $foreign = Satellite::factory()->create();

    post('/ccf/register', validForm($foreign))->assertSessionHasErrors('home_satellite_id');

    expect(Family::query()->count())->toBe(0);
});

/*
 * Form parity with today's Google Form (plan §6 R1, review B6): the allergy question is answered every time,
 * yes or no, and a "yes" needs the details — a blank box must never be read as "no allergies".
 */
describe('allergies are an explicit yes or no', function (): void {
    it('requires the question to be answered', function (): void {
        [, $satellite] = ccf();
        $form = validForm($satellite);
        unset($form['children'][0]['has_allergies']);

        post('/ccf/register', $form)->assertSessionHasErrors(['children.0.has_allergies' => 'Please answer: does your child have any allergies?']);
    });

    it('needs the details when the answer is yes', function (): void {
        [, $satellite] = ccf();
        $form = validForm($satellite);
        $form['children'][0]['allergies'] = '';

        post('/ccf/register', $form)->assertSessionHasErrors(['children.0.allergies' => 'Please tell us what your child is allergic to.']);
    });

    it('keeps no details when the answer is no, whatever was typed', function (): void {
        Notification::fake();
        [, $satellite] = ccf();
        $form = validForm($satellite);
        $form['children'][0]['has_allergies'] = false;
        $form['children'][0]['allergies'] = 'typed then changed mind';

        post('/ccf/register', $form)->assertRedirect();

        expect(Child::query()->sole()->allergies)->toBeNull();
    });
});

it('requires each child\'s nickname — it is the name on their card', function (): void {
    [, $satellite] = ccf();
    $form = validForm($satellite);
    $form['children'][0]['nickname'] = '';

    post('/ccf/register', $form)->assertSessionHasErrors(['children.0.nickname' => 'Please enter the name your child goes by.']);
});

it('keeps the rest of the Google Form: DGroup area and leader phone, lifestage, ministry', function (): void {
    Notification::fake();
    [, $satellite] = ccf();
    $form = validForm($satellite) + [
        'in_dgroup' => true,
        'dgroup_area_text' => 'Barsha',
        'dgroup_leader_name' => 'Leah',
        'dgroup_leader_phone' => '0501231234',
        'lifestage' => 'Married with kids',
    ];

    post('/ccf/register', $form)->assertRedirect();

    $family = Family::query()->with('primaryGuardian')->sole();
    expect($family->dgroup_area_text)->toBe('Barsha')
        ->and($family->dgroup_leader_phone)->toBe('0501231234')
        ->and($family->lifestage)->toBe('Married with kids')
        ->and($family->primaryGuardian?->ministries)->toBe(['Music']);
});

it('keeps Laravel\'s own words for a too-long "Anything else we should know" (refactor 06 renamed the field)', function (): void {
    [, $satellite] = ccf();
    $form = validForm($satellite);
    $form['children'][0]['guardian_notes'] = str_repeat('a', 1001);

    post('/ccf/register', $form)
        ->assertSessionHasErrors(['children.0.guardian_notes' => 'The children.0.parent notes field must not be greater than 1000 characters.']);
});

it('keeps Laravel\'s own words when "Anything else we should know" is not text', function (): void {
    [, $satellite] = ccf();
    $form = validForm($satellite);
    $form['children'][0]['guardian_notes'] = ['not', 'text'];

    post('/ccf/register', $form)
        ->assertSessionHasErrors(['children.0.guardian_notes' => 'The children.0.parent notes field must be a string.']);
});
