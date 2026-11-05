<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Moving-Up (GLOSSARY; TODO #27): the yearly step a church admin runs. Each one keeps exactly the children it moved,
 * with their Level and status before and after, so the last one can be put back — one child or everyone (Roy
 * 2026-11-06).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moving_ups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->comment('The church whose children moved up')->constrained()->restrictOnDelete();
            $table->foreignId('made_by')->nullable()->comment('The church admin who ran it')->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['church_id', 'created_at']);
        });

        Schema::create('moving_up_children', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->comment('The church (the Moving-Up\'s)')->constrained()->restrictOnDelete();
            $table->foreignId('moving_up_id')->comment('The Moving-Up')->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->comment('A child it moved')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('from_level')->comment('The Level before');
            $table->string('from_status', 20)->comment('The status before (ChildStatusEnum)');
            $table->unsignedTinyInteger('to_level')->comment('The Level after (the same at the top Level)');
            $table->string('to_status', 20)->comment('The status after: Graduated from the top Level');
            $table->timestampTz('put_back_at')->nullable()->comment('When it was put back for this child');
            $table->foreignId('put_back_by')->nullable()->comment('Who put it back')->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['moving_up_id', 'child_id']);
            $table->index(['church_id', 'child_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moving_up_children');
        Schema::dropIfExists('moving_ups');
    }
};
