<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Check-in at the door (DECISIONS 2026-10-26).
 *
 * `schedule_sessions` (GLOSSARY "Session"): one class (Schedule) on one date. Never opened or closed — the first
 * Check-in makes it, and the unique (schedule_id, date) key makes two phones pressing at once share one. The table
 * is not called `sessions`: Laravel's database session driver owns that name.
 *
 * `check_ins` (GLOSSARY "Check-in"): a child at a Session, by whom and when, in THIS satellite's Squad for the
 * child's Level and gender. One row per child per Session (unique key): an Undo stamps undone_at/by, and checking
 * the child in again revives the same row — so the history of who checked in last is on the row, and two phones
 * can never make two.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->comment('Denormalised from the class, so every query can name the church')->constrained()->restrictOnDelete();
            $table->foreignId('satellite_id')->comment('The satellite whose class this is')->constrained()->restrictOnDelete();
            $table->foreignId('schedule_id')->comment('The class (Schedule) that met')->constrained()->restrictOnDelete();
            $table->date('date')->comment('The day it met, in the church\'s timezone');
            $table->timestampsTz();

            $table->unique(['schedule_id', 'date']);
            $table->index(['church_id', 'satellite_id', 'date']);
        });

        Schema::create('check_ins', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->comment('Denormalised from the session, so every query can name the church')->constrained()->restrictOnDelete();
            $table->foreignId('session_id')->comment('The Session the child is at')->constrained('schedule_sessions')->restrictOnDelete();
            $table->foreignId('child_id')->comment('The child checked in')->constrained('children')->restrictOnDelete();
            $table->foreignId('squad_id')->comment('This satellite\'s Squad for the child\'s Level and gender, at check-in')->constrained()->restrictOnDelete();
            $table->timestampTz('checked_in_at')->comment('When the child was (last) checked in');
            $table->foreignId('checked_in_by')->nullable()->comment('Who checked the child in')->constrained('users')->nullOnDelete();
            $table->timestampTz('undone_at')->nullable()->comment('When the check-in was undone; null = the child is in');
            $table->foreignId('undone_by')->nullable()->comment('Who undid it')->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['session_id', 'child_id']);
            $table->index(['church_id', 'child_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_ins');
        Schema::dropIfExists('schedule_sessions');
    }
};
