<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * A user is a LOGIN and nothing else (plan §4). People live in `guardians`; access comes from a guardian
 * link (parent) and/or role assignments (staff). There is deliberately no family_id here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->comment('Display name of the person who logs in');
            $table->string('email')->unique()->comment('Login identity, stored lowercase');
            $table->timestampTz('email_verified_at')->nullable()->comment('Set when a verify or login link is redeemed');
            $table->unsignedInteger('credential_version')->default(1)->comment('Bumped on account email change; revokes outstanding links (plan §4.3)');
            $table->string('password')->nullable()->comment('Staff backup only; parents never have one');
            $table->boolean('is_platform_admin')->default(false)->comment('Can create churches (Roy)');
            $table->timestampTz('last_login_at')->nullable()->comment('Last successful sign-in');
            $table->rememberToken();
            $table->timestampsTz();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary()->comment('Staff password reset target');
            $table->string('token')->comment('Hashed reset token');
            $table->timestampTz('created_at')->nullable()->comment('When the reset was requested');
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary()->comment('Session id (unused while sessions are in Redis)');
            $table->foreignId('user_id')->nullable()->index()->comment('Signed-in user');
            $table->string('ip_address', 45)->nullable()->comment('Client IP');
            $table->text('user_agent')->nullable()->comment('Client user agent');
            $table->longText('payload')->comment('Serialized session');
            $table->integer('last_activity')->index()->comment('Unix time of last activity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
