<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Roll call (DECISIONS 2026-10-28; GLOSSARY "Roll call"): a Squad leader confirms how many children are in their Squad
 * at a Session. One per Squad per Session (unique key): confirming again replaces it — latest wins, no history — and
 * two leaders confirming at once still make one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('squad_head_counts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->comment('Denormalised from the session, so every query can name the church')->constrained()->restrictOnDelete();
            $table->foreignId('session_id')->comment('The Session counted')->constrained('schedule_sessions')->restrictOnDelete();
            $table->foreignId('squad_id')->comment('The Squad counted')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('head_count')->comment('How many children the Squad leader counted (0–200)');
            $table->timestampTz('counted_at')->comment('When the count was (last) confirmed');
            $table->foreignId('counted_by')->nullable()->comment('Who confirmed it')->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['session_id', 'squad_id']);
            $table->index(['church_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('squad_head_counts');
    }
};
