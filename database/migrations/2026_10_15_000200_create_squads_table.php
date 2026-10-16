<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Squads (GLOSSARY "Squad"): a Satellite's split of a Level by gender — "Level 2 Boys", "Level 1" (mixed). Made from
 * the age-level chart in config/nxtgen.php when a Satellite is created; no screen edits them. Levels themselves are
 * not a table: they are one church-wide list in config, and a child stores its Level number (children.level).
 *
 * No stored name: it is derived from level + gender (LevelChart::squadName), so it cannot drift from the chart.
 * One squad per (satellite, level, gender) is held by the database through `gender_key` — a plain unique key on
 * the nullable gender would not hold for the mixed squad, because MySQL treats NULLs as distinct.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('squads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->comment('Denormalised from the satellite, so every query can name the church')->constrained()->restrictOnDelete();
            $table->foreignId('satellite_id')->comment('The satellite whose squad this is')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('level')->comment('The Level (config nxtgen.levels) this squad is part of');
            $table->string('gender')->nullable()->comment('GenderEnum, null = mixed (Level 1)');
            $table->string('gender_key', 16)->storedAs("COALESCE(gender, 'mixed')")->comment('Gender or "mixed": the unique key cannot hold on a nullable column');
            $table->timestampsTz();

            $table->unique(['satellite_id', 'level', 'gender_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('squads');
    }
};
