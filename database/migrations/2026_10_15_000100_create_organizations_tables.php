<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->comment('The church, e.g. CCF');
            $table->string('slug')->unique()->comment('URL key: /{slug}/register');
            $table->string('timezone')->default('Asia/Dubai')->comment('Classes are generated in this timezone');
            $table->string('currency', 3)->default('AED')->comment('Card fees');
            $table->date('moving_up_reference_date')->nullable()->comment('Age is measured on this date for group suggestions');
            $table->json('settings')->nullable()->comment('satellite_label, lifeshapers_can_open, card fees');
            $table->timestampsTz();
        });

        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->comment('Church this satellite belongs to')->constrained()->restrictOnDelete();
            $table->string('name')->comment('Satellite name, e.g. Holy Trinity Church');
            $table->string('slug')->comment('URL key within the church');
            $table->timestampsTz();

            $table->unique(['organization_id', 'slug']);
        });

        Schema::create('role_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->comment('Church the role applies in')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->comment('Staff member')->constrained()->restrictOnDelete();
            $table->string('role')->comment('RoleEnum: admin | lifeshaper');
            $table->string('scope_type')->comment('ScopeTypeEnum: organization | branch | event_series');
            $table->unsignedBigInteger('scope_id')->comment('Id of the organization, branch or event series');
            $table->boolean('is_active')->default(true)->comment('Revoked assignments stay for audit');
            $table->foreignId('granted_by')->nullable()->comment('Who granted it')->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['user_id', 'role', 'scope_type', 'scope_id']);
            $table->index(['organization_id', 'user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_assignments');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('organizations');
    }
};
