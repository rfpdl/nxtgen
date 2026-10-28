<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Scan a name card at the door (slice 3, DECISIONS 2026-10-28 night). The NXTGEN name cards carry a Code 39 barcode of
 * the child's full name in capitals, no spaces (ADAMMATTHIASABERGOS): `name_key` is that, kept in step by the Child
 * model on every save (NameKey). `name_key_first` / `name_key_last` let a card match a child whose middle name is
 * missing on one side (first + last only, never fuzzier). Existing children are filled in by the next migration, once
 * the columns are wide enough (2026_10_29_000300 — Astra: a long name failed here).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('children', function (Blueprint $table): void {
            $table->string('name_key', 191)->default('')->after('last_name')->comment('First + middle + last name, A–Z only, upper-cased: what the name card\'s barcode spells');
            $table->string('name_key_first', 191)->default('')->after('name_key')->comment('The first name as a key (the card match without a middle name)');
            $table->string('name_key_last', 191)->default('')->after('name_key_first')->comment('The last name as a key (the card match without a middle name)');
            $table->index(['church_id', 'name_key']);
        });
    }

    public function down(): void
    {
        Schema::table('children', function (Blueprint $table): void {
            $table->dropIndex(['church_id', 'name_key']);
            $table->dropColumn(['name_key', 'name_key_first', 'name_key_last']);
        });
    }
};
