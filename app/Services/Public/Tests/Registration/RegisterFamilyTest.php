<?php

declare(strict_types=1);

use App\Data\Models\Branch;
use App\Data\Models\Child;
use App\Data\Models\Family;
use App\Data\Models\Organization;
use App\Data\Models\User;
use App\Enums\RegistrationOutcomeEnum;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

/** @return array{0: Organization, 1: Branch} */
function ccf(): array
{
    $org = Organization::factory()->create(['slug' => 'ccf', 'name' => 'CCF']);

    return [$org, Branch::factory()->for($org)->create(['name' => 'CCF DXB'])];
}

/**
 * @return array{home_branch_id: int, area_text: string, privacy_consented: bool,
 *     guardian: array<string, mixed>, children: list<array<string, mixed>>}
 */
function validForm(Branch $branch): array
{
    return [
        'home_branch_id' => $branch->id,
        'area_text' => 'Al Barsha',
        'privacy_consented' => true,
        'guardian' => [
            'first_name' => 'Janice', 'last_name' => 'Ramos', 'relationship' => 'mother',
            'phone' => '0544897346', 'email' => 'janice@example.com', 'ministries' => ['Music'],
        ],
        'children' => [[
            'first_name' => 'Alexis', 'middle_name' => 'Amara', 'last_name' => 'Ramos', 'nickname' => 'Amara',
            'gender' => 'girl', 'date_of_birth' => '2019-05-10', 'allergies' => 'Peanuts',
        ]],
    ];
}

it('shows the registration form with the church satellites', function (): void {
    [, $branch] = ccf();

    get('/ccf/register')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
        ->component('Public/Register')
        ->where('church.name', 'CCF')
        ->where('satellites.0.id', $branch->id)
        ->where('satellites.0.name', 'CCF DXB')
        ->has('relationships')
        ->has('genders'));
});

it('404s for a church that does not exist', function (): void {
    get('/nope/register')->assertNotFound();
});

it('registers a family and thanks the parent', function (): void {
    Notification::fake();
    [, $branch] = ccf();

    post('/ccf/register', validForm($branch))
        ->assertRedirect('/ccf/register/thanks')
        ->assertSessionHas('success', RegistrationOutcomeEnum::Registered->parentMessage());

    expect(Family::query()->count())->toBe(1)
        ->and(Child::query()->sole()->nickname)->toBe('Amara')
        ->and(User::query()->sole()->email)->toBe('janice@example.com');
});

it('registers several children at once', function (): void {
    Notification::fake();
    [, $branch] = ccf();

    $form = validForm($branch);
    $form['children'][] = ['first_name' => 'Aaron', 'middle_name' => null, 'last_name' => 'Ramos', 'nickname' => 'Aaron', 'gender' => 'boy', 'date_of_birth' => '2017-01-02', 'allergies' => null];

    post('/ccf/register', $form)->assertRedirect('/ccf/register/thanks');

    expect(Child::query()->count())->toBe(2);
});

it('does not register an already-registered email twice, and says nothing that reveals it', function (): void {
    Notification::fake();
    [, $branch] = ccf();
    post('/ccf/register', validForm($branch));

    post('/ccf/register', validForm($branch))
        ->assertRedirect('/ccf/register/thanks')
        ->assertSessionHas('success', RegistrationOutcomeEnum::Registered->parentMessage());

    expect(Family::query()->count())->toBe(1);
});

it('gives the same public answer for every registration outcome (no membership oracle)', function (): void {
    $messages = array_unique(array_map(fn (RegistrationOutcomeEnum $o): string => $o->parentMessage(), RegistrationOutcomeEnum::cases()));

    expect($messages)->toHaveCount(1);
});

it('shows the thank-you page', function (): void {
    ccf();

    get('/ccf/register/thanks')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p->component('Public/RegisterThanks'));
});

it('requires the privacy agreement', function (): void {
    [, $branch] = ccf();

    $form = validForm($branch);
    $form['privacy_consented'] = false;

    post('/ccf/register', $form)->assertSessionHasErrors('privacy_consented');
});

it('requires at least one child', function (): void {
    [, $branch] = ccf();
    $form = validForm($branch);
    $form['children'] = [];

    post('/ccf/register', $form)->assertSessionHasErrors('children');
});

it('requires the parent email, name and phone and each child\'s name, gender and birthday', function (): void {
    [, $branch] = ccf();
    $form = validForm($branch);
    $form['guardian']['email'] = '';
    $form['guardian']['phone'] = '';
    $form['children'][0]['first_name'] = '';
    $form['children'][0]['gender'] = 'dragon';
    $form['children'][0]['date_of_birth'] = '2999-01-01';

    post('/ccf/register', $form)->assertSessionHasErrors([
        'guardian.email', 'guardian.phone', 'children.0.first_name', 'children.0.gender', 'children.0.date_of_birth',
    ]);
});

it('refuses a satellite from another church', function (): void {
    ccf();
    $foreign = Branch::factory()->create();

    post('/ccf/register', validForm($foreign))->assertSessionHasErrors('home_branch_id');

    expect(Family::query()->count())->toBe(0);
});
