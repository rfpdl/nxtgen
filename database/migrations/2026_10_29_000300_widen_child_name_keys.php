<?php

declare(strict_types=1);

use App\Foundation\Support\NameKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The name-card keys, wide enough for any name the forms allow (Astra): three parts of up to 100 characters, a letter
 * spelled out as up to two (ß → SS, æ → AE). ASCII — the keys are A–Z only, one byte each, so the (church_id, name_key)
 * index still fits — compared exactly (ascii_bin). Then every child's keys are worked out (NameKey::fillIn, safe to run
 * again).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('children', function (Blueprint $table): void {
            $table->string('name_key', NameKey::KEY_LENGTH)->charset('ascii')->collation('ascii_bin')->default('')->comment('First + middle + last name, A–Z only, upper-cased: what the name card\'s barcode spells')->change();
            $table->string('name_key_first', NameKey::PART_KEY_LENGTH)->charset('ascii')->collation('ascii_bin')->default('')->comment('The first name as a key (the card match without a middle name)')->change();
            $table->string('name_key_last', NameKey::PART_KEY_LENGTH)->charset('ascii')->collation('ascii_bin')->default('')->comment('The last name as a key (the card match without a middle name)')->change();
        });

        NameKey::fillIn();
    }

    public function down(): void
    {
        // The keys stay as they are: narrowing them could cut a key that was right.
    }
};
