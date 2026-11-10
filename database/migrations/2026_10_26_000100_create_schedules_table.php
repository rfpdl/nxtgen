<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Schedules (GLOSSARY "Schedule", shown as "class"): a Satellite's named kids class, made by admins on the Classes
 * page (DECISIONS 2026-10-26). Weekly — "1PM" on Saturdays — or one-time on one date — "Christmas party" (spec item
 * 14): exactly one of `weekday` and `date`. Both times always, the end after the start. Times are wall-clock times at
 * the Satellite, not instants; the date is the Satellite's date.
 *
 * A class is retired, never deleted (Sessions will point at it). One ACTIVE class per name per Satellite is held
 * by the database through `active_name` — a unique key including `is_active` would allow only one retired class
 * per name, and MySQL treats NULLs as distinct, so retired rows (null) never collide.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->comment('Denormalised from the satellite, so every query can name the church')->constrained()->restrictOnDelete();
            $table->foreignId('satellite_id')->comment('The satellite whose class this is')->constrained()->restrictOnDelete();
            $table->string('name', 100)->comment('What people call the class, e.g. 1PM, Special class');
            $table->string('weekday', 9)->nullable()->comment('WeekdayEnum: a weekly class; null for a one-time class');
            $table->date('date')->nullable()->comment('A one-time class\'s date; null for a weekly class');
            $table->time('starts_at')->comment('Wall-clock start at the satellite (HH:MM)');
            $table->time('ends_at')->comment('Wall-clock end at the satellite (HH:MM), after starts_at');
            $table->boolean('is_active')->default(true)->comment('Retired classes stay for the record');
            $table->string('active_name', 100)->nullable()->storedAs('IF(is_active, name, NULL)')->comment('The name while active: the unique key holds only among active classes');
            $table->timestampsTz();

            $table->unique(['satellite_id', 'active_name']);
            $table->index(['church_id', 'satellite_id', 'is_active']);
        });

        DB::statement('ALTER TABLE schedules ADD CONSTRAINT schedules_weekly_or_one_time CHECK ((weekday IS NULL) <> (date IS NULL))');
        DB::statement('ALTER TABLE schedules ADD CONSTRAINT schedules_ends_after_start CHECK (ends_at > starts_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
