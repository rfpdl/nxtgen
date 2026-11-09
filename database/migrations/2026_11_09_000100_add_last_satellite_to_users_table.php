<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The satellite a person used last (spec item 11; DECISIONS 2026-11-08: on the account, so it follows them to every
 * device) — where they land after signing in, while they still serve there.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('last_satellite_id')->nullable()->after('is_platform_admin')
                ->comment('The satellite whose pages they opened last — where they land after signing in')
                ->constrained('satellites')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('last_satellite_id');
        });
    }
};
