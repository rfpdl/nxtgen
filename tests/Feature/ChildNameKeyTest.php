<?php

declare(strict_types=1);

use App\Data\Models\Child;
use App\Domains\Family\DataObjects\ChildDetailsData;
use App\Domains\Family\Jobs\CreateChildJob;
use App\Domains\Family\Jobs\UpdateChildDetailsJob;
use App\Enums\GenderEnum;
use App\Enums\RegistrationSourceEnum;
use App\Foundation\Support\NameKey;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Finder\Finder;

use function Tests\Support\childOf;
use function Tests\Support\church;
use function Tests\Support\familyAt;
use function Tests\Support\satelliteOf;

/*
 * The name-card key (slice 3) follows a child's names on every write: the Child model sets it on each save, and no code
 * writes a child's names around the model.
 */

function details(string $first, ?string $middle, string $last): ChildDetailsData
{
    return new ChildDetailsData(firstName: $first, middleName: $middle, lastName: $last, nickname: $first, gender: GenderEnum::Girl, dateOfBirth: '2019-03-01', allergies: null);
}

it('is set when a child is made — registration, adding a child, the registry import — and follows every edit', function (): void {
    $family = familyAt(satelliteOf(church()));

    foreach ([RegistrationSourceEnum::Staff, RegistrationSourceEnum::Import] as $source) {
        $made = (new CreateChildJob(family: $family, details: details('Amara', 'Joy', 'Reyes'), level: 2, source: $source))->handle();
        expect([$made->fresh()?->name_key, $made->fresh()?->name_key_first, $made->fresh()?->name_key_last])->toBe(['AMARAJOYREYES', 'AMARA', 'REYES']);
    }

    $child = Child::query()->firstOrFail();
    (new UpdateChildDetailsJob(child: $child, details: details('Amara', null, 'Peñaflor')))->handle();
    expect($child->fresh()?->name_key)->toBe('AMARAPENAFLOR');

    $other = childOf($family, ['first_name' => 'Ben', 'middle_name' => 'Jo', 'last_name' => 'Cruz']);
    $other->forceFill(['first_name' => 'Benjamin'])->save();
    expect($other->fresh()?->name_key)->toBe('BENJAMINJOCRUZ');
});

it('is never bypassed: no code writes children around the model', function (string $code, bool $bypasses): void {
    expect(writesChildrenAroundTheModel($code))->toBe($bypasses);
})->with([
    'a query-builder update' => ["Child::query()->where('id', 1)->update(['first_name' => 'X']);", true],
    'an upsert' => ["Child::query()->upsert([], ['id']);", true],
    'a static insert' => ['Child::insert([]);', true],
    'the children table directly' => ["DB::table('children')->where('id', 1)->update([]);", true],
    'a raw update' => ["DB::update('update children set first_name = ?', ['X']);", true],
    'a model save' => ['$child->forceFill([\'first_name\' => \'X\'])->save();', false],
    'another model\'s update' => ["Squad::query()->where('id', 1)->update(['level' => 2]);", false],
]);

it('finds no such write in the app or the seeders', function (): void {
    $offenders = [];
    foreach (Finder::create()->files()->in([app_path(), database_path('seeders')])->name('*.php') as $file) {
        // NameKey::fillIn writes only the keys themselves, worked out from the names it reads — the migrations' backfill.
        if ($file->getRelativePathname() !== 'Foundation/Support/NameKey.php' && writesChildrenAroundTheModel($file->getContents())) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([]);
});

/** A write to children that skips the Child model's saving hook (and so the name-card keys). */
function writesChildrenAroundTheModel(string $code): bool
{
    foreach (preg_split('/;/', $code) ?: [] as $statement) {
        if (preg_match("/DB::table\\(\\s*['\"]children['\"]\\s*\\)/", $statement) === 1
            || preg_match('/DB::(update|insert|statement)\\(\\s*[\'"][^\'"]*\\bchildren\\b/i', $statement) === 1
            || preg_match('/\\bChild::(query\\(\\).*->)?(update|upsert|insert|insertOrIgnore|updateOrInsert)\\(/s', $statement) === 1) {
            return true;
        }
    }

    return false;
}

it('keeps the stored name and its keys together when two edits of different name parts race (Astra)', function (): void {
    $child = childOf(familyAt(satelliteOf(church())), ['first_name' => 'Ana', 'middle_name' => null, 'last_name' => 'Reyes']);
    $one = Child::query()->findOrFail($child->id);
    $two = Child::query()->findOrFail($child->id);

    // Both loaded "Ana Reyes"; one renames her first name, the other her last name.
    $one->forceFill(['first_name' => 'Maria'])->save();
    $two->forceFill(['last_name' => 'Cruz'])->save();

    $stored = Child::query()->findOrFail($child->id);
    expect($stored->name_key)->toBe(NameKey::of($stored->first_name, $stored->middle_name, $stored->last_name))
        ->and([$stored->name_key_first, $stored->name_key_last])->toBe([NameKey::code($stored->first_name), NameKey::code($stored->last_name)])
        // The later edit wins the whole name.
        ->and($stored->fullName())->toBe('Ana Cruz');
});

it('saves the longest names a form allows — accents that grow when spelled out too (ß → SS, æ → AE)', function (): void {
    $long = str_repeat('ß', 100);
    $child = childOf(familyAt(satelliteOf(church())), ['first_name' => $long, 'middle_name' => str_repeat('æ', 100), 'last_name' => $long]);

    $stored = Child::query()->findOrFail($child->id);
    expect(strlen($stored->name_key))->toBe(600)
        ->and($stored->name_key)->toBe(str_repeat('SS', 100).str_repeat('AE', 100).str_repeat('SS', 100))
        ->and([strlen($stored->name_key_first), strlen($stored->name_key_last)])->toBe([200, 200]);
});

it('fills in every child\'s keys, and can run again safely — long names too', function (): void {
    $family = familyAt(satelliteOf(church()));
    $long = childOf($family, ['first_name' => str_repeat('ß', 100), 'middle_name' => null, 'last_name' => 'Reyes']);
    $amara = childOf($family, ['first_name' => 'Amara', 'middle_name' => 'Joy', 'last_name' => 'Reyes']);
    DB::table('children')->update(['name_key' => '', 'name_key_first' => '', 'name_key_last' => '']);

    NameKey::fillIn();
    NameKey::fillIn();

    expect(Child::query()->findOrFail($amara->id)->name_key)->toBe('AMARAJOYREYES')
        ->and(Child::query()->findOrFail($long->id)->name_key)->toBe(str_repeat('SS', 100).'REYES');
});
