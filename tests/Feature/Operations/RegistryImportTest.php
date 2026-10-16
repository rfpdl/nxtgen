<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Data\Models\Church;
use App\Data\Models\Family;
use App\Data\Models\Group;
use App\Data\Models\Guardian;
use App\Data\Models\MagicLink;
use App\Data\Models\User;
use App\Enums\ChildStatusEnum;
use App\Enums\MagicLinkPurposeEnum;
use App\Enums\RegistrationSourceEnum;
use App\Enums\RoleEnum;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\PendingCommand;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Tests\Support\atChurch;
use function Tests\Support\church;
use function Tests\Support\grantRole;
use function Tests\Support\satelliteOf;

/** @param array<string, string> $args */
function runCommand(string $command, array $args, bool $expectSuccess = true): void
{
    $pending = artisan($command, $args);
    expect($pending)->toBeInstanceOf(PendingCommand::class);
    if ($pending instanceof PendingCommand) {
        $expectSuccess ? $pending->assertSuccessful() : $pending->assertFailed();
    }
}

/** @return array{0: Church, 1: string} */
function proposeSample(): array
{
    $church = church(['slug' => 'ccf']);
    satelliteOf($church, ['name' => 'CCF DXB']);
    satelliteOf($church, ['name' => 'House Church']);
    $out = sys_get_temp_dir().'/nxtgen-review-'.uniqid().'.xlsx';

    runCommand('nxtgen:registry-propose', ['csv' => base_path('tests/Fixtures/registry-sample.csv'), '--out' => $out]);

    return [$church, $out];
}

/** @param list<string> $header */
function col(array $header, string $name): int
{
    $index = array_search($name, $header, true);

    return is_int($index) ? $index : throw new RuntimeException("Missing column {$name}");
}

/** @return array<string, list<list<string>>> sheet name => rows */
function readReview(string $path): array
{
    $reader = new Reader;
    $reader->open($path);
    $sheets = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $sheets[$sheet->getName()][] = array_values(array_map(fn (mixed $v): string => is_scalar($v) ? (string) $v : '', $row->toArray()));
        }
    }
    $reader->close();

    return $sheets;
}

it('writes a review file with families, quarantine and a balancing reconciliation', function (): void {
    [, $out] = proposeSample();
    $sheets = readReview($out);

    expect(array_keys($sheets))->toBe(['Families', 'Quarantine', 'Reconciliation'])
        ->and(count($sheets['Families']) - 1)->toBe(4)
        ->and(count($sheets['Quarantine']) - 1)->toBe(1)
        ->and(collect($sheets['Reconciliation'])->pluck(1)->all())->toContain('6');
});

it('loads the approved families without emailing anyone', function (): void {
    Notification::fake();
    [$church, $out] = proposeSample();

    runCommand('nxtgen:registry-apply', ['xlsx' => $out, '--church' => 'ccf']);

    expect(Family::query()->count())->toBe(3)
        ->and(Child::query()->count())->toBe(4)
        ->and(Child::query()->where('first_name', 'Ysabella')->sole()->status)->toBe(ChildStatusEnum::Graduated)
        ->and(Family::query()->first()?->registration_source)->toBe(RegistrationSourceEnum::Import)
        ->and(MagicLink::query()->count())->toBe(0);

    Notification::assertNothingSent();
    expect($church->id)->toBeInt();
});

it('puts a House Church family on the House Church satellite', function (): void {
    Notification::fake();
    [, $out] = proposeSample();

    runCommand('nxtgen:registry-apply', ['xlsx' => $out, '--church' => 'ccf']);

    $rala = Child::query()->where('first_name', 'Ysabella')->sole()->family()->with('homeSatellite')->sole();
    expect($rala->homeSatellite->name)->toBe('House Church');
});

it('is safe to run twice', function (): void {
    Notification::fake();
    [, $out] = proposeSample();

    runCommand('nxtgen:registry-apply', ['xlsx' => $out, '--church' => 'ccf']);
    runCommand('nxtgen:registry-apply', ['xlsx' => $out, '--church' => 'ccf']);

    expect(Family::query()->count())->toBe(3)->and(Child::query()->count())->toBe(4);
});

it('skips rows the reviewer did not approve', function (): void {
    Notification::fake();
    [, $out] = proposeSample();
    $sheets = readReview($out);

    // Reviewer sets "Approve" to "no" for the first child row.
    $families = $sheets['Families'];
    $approveCol = col($families[0], 'Approve');
    $families[1][$approveCol] = 'no';

    $edited = sys_get_temp_dir().'/nxtgen-edited-'.uniqid().'.xlsx';
    $writer = new Writer;
    $writer->openToFile($edited);
    $writer->getCurrentSheet()->setName('Families');
    foreach ($families as $r) {
        $writer->addRow(Row::fromValues($r));
    }
    $writer->close();

    runCommand('nxtgen:registry-apply', ['xlsx' => $edited, '--church' => 'ccf']);

    expect(Child::query()->count())->toBe(3);
});

it('refuses a reviewed group whose rows were already imported into different families (Codex diff review, high)', function (): void {
    Notification::fake();
    [, $out] = proposeSample();
    runCommand('nxtgen:registry-apply', ['xlsx' => $out, '--church' => 'ccf']);
    $sheets = readReview($out);

    // Reviewer later merges Rala's row into the Ramos family key AND adds a new child to that group.
    $families = $sheets['Families'];
    $h = $families[0];
    $key = col($h, 'Family key');
    $source = col($h, 'Source key');
    $first = col($h, 'Child first name');
    $ramosKey = $families[1][$key];
    foreach ($families as $i => $r) {
        if ($i > 0 && $r[$first] === 'Ysabella') {
            $families[$i][$key] = $ramosKey;
        }
    }
    $new = $families[1];
    $new[$source] = 'brand-new-source-key';
    $new[$first] = 'Newbie';
    $families[] = $new;

    $edited = sys_get_temp_dir().'/nxtgen-regroup-'.uniqid().'.xlsx';
    $writer = new Writer;
    $writer->openToFile($edited);
    $writer->getCurrentSheet()->setName('Families');
    foreach ($families as $r) {
        $writer->addRow(Row::fromValues($r));
    }
    $writer->close();

    runCommand('nxtgen:registry-apply', ['xlsx' => $edited, '--church' => 'ccf'], expectSuccess: false);

    expect(Child::query()->where('first_name', 'Newbie')->exists())->toBeFalse();
});

it('refuses a group whose parent email differs from the family it was already imported into', function (): void {
    Notification::fake();
    [, $out] = proposeSample();
    runCommand('nxtgen:registry-apply', ['xlsx' => $out, '--church' => 'ccf']);
    $sheets = readReview($out);

    $families = $sheets['Families'];
    $h = $families[0];
    $emailCol = col($h, 'Parent email');
    $source = col($h, 'Source key');
    $first = col($h, 'Child first name');
    $families[1][$emailCol] = 'someone-else@example.com';
    $new = $families[1];
    $new[$source] = 'another-new-key';
    $new[$first] = 'Stranger';
    $families[] = $new;

    $edited = sys_get_temp_dir().'/nxtgen-email-'.uniqid().'.xlsx';
    $writer = new Writer;
    $writer->openToFile($edited);
    $writer->getCurrentSheet()->setName('Families');
    foreach ($families as $r) {
        $writer->addRow(Row::fromValues($r));
    }
    $writer->close();

    runCommand('nxtgen:registry-apply', ['xlsx' => $edited, '--church' => 'ccf'], expectSuccess: false);

    expect(Child::query()->where('first_name', 'Stranger')->exists())->toBeFalse();
});

it('does not import a child twice after the sheet row was corrected (Codex diff review, medium)', function (): void {
    Notification::fake();
    [, $out] = proposeSample();
    runCommand('nxtgen:registry-apply', ['xlsx' => $out, '--church' => 'ccf']);
    $sheets = readReview($out);

    // The row is corrected in the sheet (new email → new source key), then re-proposed and re-applied.
    $families = $sheets['Families'];
    $h = $families[0];
    $families[1][col($h, 'Parent email')] = 'janice.corrected@example.com';
    $families[1][col($h, 'Source key')] = 'changed-after-correction';
    $families[1][col($h, 'Family key')] = 'corrected-family';

    $edited = sys_get_temp_dir().'/nxtgen-corrected-'.uniqid().'.xlsx';
    $writer = new Writer;
    $writer->openToFile($edited);
    $writer->getCurrentSheet()->setName('Families');
    foreach ($families as $r) {
        $writer->addRow(Row::fromValues($r));
    }
    $writer->close();

    runCommand('nxtgen:registry-apply', ['xlsx' => $edited, '--church' => 'ccf'], expectSuccess: false);

    expect(Child::query()->where('first_name', $families[1][col($h, 'Child first name')])->count())->toBe(1)
        ->and(Family::query()->count())->toBe(3);
});

it('loads a same-name, same-birthday child in another family when the reviewer marks it distinct', function (): void {
    Notification::fake();
    [, $out] = proposeSample();
    runCommand('nxtgen:registry-apply', ['xlsx' => $out, '--church' => 'ccf']);
    $sheets = readReview($out);

    $families = $sheets['Families'];
    $h = $families[0];
    $twin = $families[1];
    $twin[col($h, 'Parent email')] = 'other-household@example.com';
    $twin[col($h, 'Family key')] = 'other-household';
    $twin[col($h, 'Source key')] = 'twin-source-key';
    $twin[col($h, 'Distinct child')] = 'yes';

    $edited = sys_get_temp_dir().'/nxtgen-twin-'.uniqid().'.xlsx';
    $writer = new Writer;
    $writer->openToFile($edited);
    $writer->getCurrentSheet()->setName('Families');
    foreach ([$h, $twin] as $r) {
        $writer->addRow(Row::fromValues($r));
    }
    $writer->close();

    runCommand('nxtgen:registry-apply', ['xlsx' => $edited, '--church' => 'ccf']);

    expect(Child::query()->where('first_name', $twin[col($h, 'Child first name')])->count())->toBe(2);
});

it('keeps the group the reviewer chose instead of recalculating it (review A3)', function (): void {
    Notification::fake();
    [$church, $out] = proposeSample();
    $chosen = Group::factory()->for($church)->create(['name' => 'Level 4 Girls', 'min_age' => 10, 'max_age' => 12]);
    $sheets = readReview($out);
    $families = $sheets['Families'];
    $h = $families[0];
    $families[1][col($h, 'Group')] = 'Level 4 Girls';

    $edited = sys_get_temp_dir().'/nxtgen-group-'.uniqid().'.xlsx';
    $writer = new Writer;
    $writer->openToFile($edited);
    $writer->getCurrentSheet()->setName('Families');
    foreach ([$h, $families[1]] as $r) {
        $writer->addRow(Row::fromValues($r));
    }
    $writer->close();

    runCommand('nxtgen:registry-apply', ['xlsx' => $edited, '--church' => 'ccf']);

    expect(Child::query()->sole()->group_id)->toBe($chosen->id);
});

it('fails the apply command when approved rows could not be loaded (review B2)', function (): void {
    Notification::fake();
    [, $out] = proposeSample();
    $sheets = readReview($out);
    $families = $sheets['Families'];
    $h = $families[0];
    $families[1][col($h, 'Home satellite')] = 'Nowhere';

    $edited = sys_get_temp_dir().'/nxtgen-bad-'.uniqid().'.xlsx';
    $writer = new Writer;
    $writer->openToFile($edited);
    $writer->getCurrentSheet()->setName('Families');
    foreach ([$h, $families[1]] as $r) {
        $writer->addRow(Row::fromValues($r));
    }
    $writer->close();

    $pending = artisan('nxtgen:registry-apply', ['xlsx' => $edited, '--church' => 'ccf']);
    expect($pending)->toBeInstanceOf(PendingCommand::class);
    if ($pending instanceof PendingCommand) {
        $pending->assertFailed();
    }
});

it('fails when an approved row lost its family key, instead of dropping it (review-spar r2)', function (): void {
    Notification::fake();
    [, $out] = proposeSample();
    $sheets = readReview($out);
    $families = $sheets['Families'];
    $h = $families[0];
    $families[1][col($h, 'Family key')] = '';

    $edited = sys_get_temp_dir().'/nxtgen-nokey-'.uniqid().'.xlsx';
    $writer = new Writer;
    $writer->openToFile($edited);
    $writer->getCurrentSheet()->setName('Families');
    foreach ([$h, $families[1]] as $r) {
        $writer->addRow(Row::fromValues($r));
    }
    $writer->close();

    runCommand('nxtgen:registry-apply', ['xlsx' => $edited, '--church' => 'ccf'], expectSuccess: false);
});

it('fails when the review file has no Families sheet', function (): void {
    proposeSample();
    $empty = sys_get_temp_dir().'/nxtgen-nosheet-'.uniqid().'.xlsx';
    $writer = new Writer;
    $writer->openToFile($empty);
    $writer->getCurrentSheet()->setName('Something else');
    $writer->addRow(Row::fromValues(['x']));
    $writer->close();

    runCommand('nxtgen:registry-apply', ['xlsx' => $empty, '--church' => 'ccf'], expectSuccess: false);
});

it('re-validates edited rows: two families with a cleared parent email are NOT merged under one blank login (review-spar r3)', function (): void {
    Notification::fake();
    [, $out] = proposeSample();
    $sheets = readReview($out);
    $families = $sheets['Families'];
    $h = $families[0];
    $rows = [$h];
    foreach (array_slice($families, 1) as $r) {
        $r[col($h, 'Parent email')] = '';
        $rows[] = $r;
    }

    $edited = sys_get_temp_dir().'/nxtgen-blank-'.uniqid().'.xlsx';
    $writer = new Writer;
    $writer->openToFile($edited);
    $writer->getCurrentSheet()->setName('Families');
    foreach ($rows as $r) {
        $writer->addRow(Row::fromValues($r));
    }
    $writer->close();

    runCommand('nxtgen:registry-apply', ['xlsx' => $edited, '--church' => 'ccf'], expectSuccess: false);

    expect(User::query()->count())->toBe(0)
        ->and(Family::query()->count())->toBe(0)
        ->and(Child::query()->count())->toBe(0);
});

it('reports an edited row with an invalid gender instead of crashing', function (): void {
    Notification::fake();
    [, $out] = proposeSample();
    $sheets = readReview($out);
    $families = $sheets['Families'];
    $h = $families[0];
    $families[1][col($h, 'Gender')] = 'dragon';

    $edited = sys_get_temp_dir().'/nxtgen-gender-'.uniqid().'.xlsx';
    $writer = new Writer;
    $writer->openToFile($edited);
    $writer->getCurrentSheet()->setName('Families');
    foreach ([$h, $families[1]] as $r) {
        $writer->addRow(Row::fromValues($r));
    }
    $writer->close();

    runCommand('nxtgen:registry-apply', ['xlsx' => $edited, '--church' => 'ccf'], expectSuccess: false);
    expect(Child::query()->count())->toBe(0);
});

/*
 * Review 3 (B3): staff are added on People BEFORE the import. A Lifeshaper who is also a parent in the sheet gets
 * their family as a pending claim (the email is someone's login: never linked on the sheet's word) — and the
 * import sends no emails. The apply must say so, and the existing resend must get them in.
 */
it('reports families waiting for an existing login to confirm, and the resend gets the parent in', function (): void {
    Notification::fake();
    [$church, $out] = proposeSample();
    $lifeshaper = User::factory()->create(['email' => 'janice@example.com']);
    grantRole($lifeshaper, RoleEnum::Lifeshaper, atChurch($church));
    $admin = User::factory()->create();
    grantRole($admin, RoleEnum::Admin, atChurch($church));

    $apply = artisan('nxtgen:registry-apply', ['xlsx' => $out, '--church' => 'ccf']);
    if (! $apply instanceof PendingCommand) {
        throw new RuntimeException('Expected a pending command.');
    }
    $apply->expectsOutputToContain('1 family waits for an existing login to confirm (no email was sent): janice@example.com')->assertSuccessful()->run();

    $family = Family::query()->whereHas('guardians', fn ($g) => $g->where('email', 'janice@example.com'))->sole();
    expect(Guardian::query()->where('user_id', $lifeshaper->id)->exists())->toBeFalse();

    // An admin sends the link from the family page; the parent opens it and the family is theirs.
    actingAs($admin)->post("/ccf/families/{$family->id}/resend")->assertSessionHas('success');
    $url = '';
    Notification::assertSentOnDemand(MagicLinkNotification::class, function (MagicLinkNotification $n) use (&$url): bool {
        $url = $n->url;

        return $n->purpose === MagicLinkPurposeEnum::ClaimFamily;
    });
    actingAs($lifeshaper)->post(str_replace('/magic/claim/', '/magic/claim/', (string) parse_url($url, PHP_URL_PATH)))->assertRedirect('/home');

    expect(Guardian::query()->where('user_id', $lifeshaper->id)->where('family_id', $family->id)->exists())->toBeTrue();
});
