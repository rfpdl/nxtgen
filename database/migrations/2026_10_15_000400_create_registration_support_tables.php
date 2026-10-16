<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Magic links, pending family claims, duplicate candidates and notes (plan §4.2, §4.3, §5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_claims', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->comment('Church')->constrained()->restrictOnDelete();
            $table->foreignId('family_id')->comment('Family waiting to be claimed')->constrained()->restrictOnDelete();
            $table->foreignId('guardian_id')->comment('Unlinked primary guardian the login will attach to')->constrained()->restrictOnDelete();
            $table->string('email_normalised')->comment('Lowercase, trimmed email the claim was sent to');
            $table->unsignedInteger('revision')->default(1)->comment('Bumped on contact change; old links stop working');
            $table->string('status')->default('pending')->comment('FamilyClaimStatusEnum: pending | accepted | revoked');
            $table->timestampTz('resolved_at')->nullable()->comment('When accepted or revoked');
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE family_claims ADD COLUMN pending_claim_key VARCHAR(320)
            GENERATED ALWAYS AS (CASE WHEN status = 'pending' THEN CONCAT(church_id, ':', email_normalised) END) STORED
            COMMENT 'One pending claim per email per church'");
        DB::statement('ALTER TABLE family_claims ADD UNIQUE INDEX family_claims_pending_claim_key_unique (pending_claim_key)');

        Schema::create('magic_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->comment('Login/verify links: the account')->constrained()->restrictOnDelete();
            $table->string('email_snapshot')->comment('Email the link was sent to; must still match on redemption');
            $table->string('purpose')->comment('MagicLinkPurposeEnum: login | verify_email | claim_family');
            $table->foreignId('family_claim_id')->nullable()->comment('Claim links: the claim')->constrained()->restrictOnDelete();
            $table->unsignedInteger('claim_revision')->nullable()->comment('Claim revision the link was issued for');
            $table->unsignedInteger('credential_version')->nullable()->comment('User credential version the link was issued for');
            $table->char('token_hash', 64)->unique()->comment('SHA-256 of the token; the token itself is never stored');
            $table->timestampTz('expires_at')->comment('Login 2 hours, verify/claim 7 days');
            $table->timestampTz('used_at')->nullable()->comment('Single use: set atomically on redemption');
            $table->string('ip', 45)->nullable()->comment('Requesting IP');
            $table->text('user_agent')->nullable()->comment('Requesting user agent');
            $table->timestampsTz();
        });

        Schema::create('duplicate_candidates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->comment('Church')->constrained()->restrictOnDelete();
            $table->string('subject_type')->comment('family | child');
            $table->unsignedBigInteger('subject_id')->comment('The new record');
            $table->unsignedBigInteger('candidate_id')->comment('The existing record it may duplicate');
            $table->string('reason')->comment('DuplicateReasonEnum: same_phone | same_name_dob | same_email');
            $table->string('status')->default('open')->comment('open | dismissed | confirmed');
            $table->foreignId('resolved_by')->nullable()->comment('Staff who reviewed it')->constrained('users')->nullOnDelete();
            $table->timestampTz('resolved_at')->nullable()->comment('When reviewed');
            $table->timestampsTz();

            $table->unique(['subject_type', 'subject_id', 'candidate_id']);
            $table->index(['church_id', 'status']);
        });

        Schema::create('notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->comment('Church')->constrained()->restrictOnDelete();
            $table->string('noteable_type')->comment('Morph alias: family | child');
            $table->unsignedBigInteger('noteable_id')->comment('Record the note is about');
            $table->foreignId('author_id')->nullable()->comment('Null for system notes')->constrained('users')->nullOnDelete();
            $table->string('kind')->comment('NoteKindEnum: system | observation');
            $table->text('body')->comment('The note');
            $table->string('visibility')->default('staff')->comment('staff | family');
            $table->timestampsTz();

            $table->index(['noteable_type', 'noteable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
        Schema::dropIfExists('duplicate_candidates');
        Schema::dropIfExists('magic_links');
        Schema::dropIfExists('family_claims');
    }
};
