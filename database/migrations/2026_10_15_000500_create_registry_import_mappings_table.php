<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Stable identity for the one-off Google Sheet import (plan §7): reruns never create a child twice. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registry_import_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->comment('Church imported into')->constrained()->restrictOnDelete();
            $table->string('source_child_key', 40)->comment('Stable key of the sheet row (email + child first name + birthday)');
            $table->string('approved_family_key', 40)->comment('Family key the reviewer approved');
            $table->foreignId('child_id')->comment('Child created from the row')->constrained('children')->restrictOnDelete();
            $table->foreignId('family_id')->comment('Family the child landed in')->constrained()->restrictOnDelete();
            $table->timestampTz('imported_at')->comment('When the row was loaded');
            $table->timestampsTz();

            $table->unique(['organization_id', 'source_child_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registry_import_mappings');
    }
};
