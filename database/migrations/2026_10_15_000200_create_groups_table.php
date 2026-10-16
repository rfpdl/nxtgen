<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->comment('Groups are per church')->constrained()->restrictOnDelete();
            $table->string('name')->comment('e.g. Level 2 Boys');
            $table->unsignedSmallInteger('sort')->default(0)->comment('Display order');
            $table->unsignedTinyInteger('min_age')->comment('Youngest age in this group, on the moving-up reference date');
            $table->unsignedTinyInteger('max_age')->comment('Oldest age in this group');
            $table->string('gender')->nullable()->comment('GenderEnum, null = mixed');
            $table->foreignId('next_group_id')->nullable()->comment('Where kids go at Moving-Up; null = top group, they graduate')->constrained('groups')->nullOnDelete();
            $table->boolean('is_active')->default(true)->comment('Retired groups stay for history');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
