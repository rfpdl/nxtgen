<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Schedules (GLOSSARY "Schedule", shown as "class"): a Satellite's named, regular kids class — "1PM" on Saturdays,
 * 13:00–15:00. Made by admins on the Classes page (DECISIONS 2026-10-26). The day and times are optional: a class
 * with no day shows every day at the door. Times are wall-clock times at the Satellite, not instants.
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
            $table->string('weekday', 9)->nullable()->comment('WeekdayEnum; null = any day');
            $table->time('starts_at')->nullable()->comment('Wall-clock start at the satellite (HH:MM), optional');
            $table->time('ends_at')->nullable()->comment('Wall-clock end at the satellite (HH:MM), optional; after starts_at');
            $table->boolean('is_active')->default(true)->comment('Retired classes stay for the record');
            $table->string('active_name', 100)->nullable()->storedAs('IF(is_active, name, NULL)')->comment('The name while active: the unique key holds only among active classes');
            $table->timestampsTz();

            $table->unique(['satellite_id', 'active_name']);
            $table->index(['church_id', 'satellite_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
