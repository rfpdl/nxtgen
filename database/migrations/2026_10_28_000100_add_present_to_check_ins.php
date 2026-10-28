<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Roll call (DECISIONS 2026-10-28; GLOSSARY "Roll call"): a Squad leader taps a checked-in child present. Latest wins,
 * no history — unmarking clears both, an Undo at the door clears both, and a re-check-in after an Undo starts unmarked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_ins', function (Blueprint $table): void {
            $table->timestampTz('present_at')->nullable()->after('checked_in_by')->comment('When the child was (last) marked present in their Squad; null = not marked');
            $table->foreignId('present_by')->nullable()->after('present_at')
                ->comment('Who marked the child present')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('present_by');
            $table->dropColumn('present_at');
        });
    }
};
