<?php

declare(strict_types=1);

use App\Enums\ChildStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Family > guardians + children (plan §4–§5). The invariants that decide who can see which children are
 * enforced here, not only in code:
 *   - one family per user per church        guardians UNIQUE(church_id, user_id)
 *   - one login per family (v1)             generated login_family_key UNIQUE
 *   - primary guardian is in its family     composite FK families(id, primary_guardian_id) → guardians(family_id, id)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->comment('Church the family belongs to')->constrained()->restrictOnDelete();
            $table->foreignId('home_satellite_id')->comment('Satellite the family usually attends')->constrained('satellites')->restrictOnDelete();
            $table->unsignedBigInteger('primary_guardian_id')->nullable()->comment('Main contact; their login is the family email');
            $table->string('area_text')->nullable()->comment('Area of residence, free text in v1');
            $table->string('fb_account')->nullable()->comment('Facebook account name for the parents group');
            $table->boolean('wants_fb_group')->default(false)->comment('Add to the parents FB group / chat');
            $table->boolean('in_dgroup')->default(false)->comment('Already in a DGroup');
            $table->string('dgroup_area_text')->nullable()->comment('DGroup area, free text in v1');
            $table->string('dgroup_leader_name')->nullable()->comment('DGroup leader name');
            $table->string('dgroup_leader_phone')->nullable()->comment('DGroup leader contact');
            $table->boolean('wants_dgroup')->default(false)->comment('Would like to join a DGroup');
            $table->string('lifestage')->nullable()->comment('From the registration form');
            $table->json('preferred_schedule_ids')->nullable()->comment('Schedules (classes) the family usually attends');
            $table->timestampTz('privacy_consented_at')->nullable()->comment('Data privacy agreement accepted');
            $table->foreignId('registered_by_user_id')->nullable()->comment('Staff who registered on the parent\'s behalf')->constrained('users')->nullOnDelete();
            $table->string('registration_source')->comment('RegistrationSourceEnum: self | staff | import');
            $table->text('notes')->nullable()->comment('Free-text notes from registration');
            $table->timestampsTz();

            $table->index(['church_id', 'home_satellite_id']);
        });

        Schema::create('guardians', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->comment('Denormalised from the family so the database can enforce one family per user per church')->constrained()->restrictOnDelete();
            $table->foreignId('family_id')->comment('Family this adult belongs to')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->comment('Login, if this adult has one')->constrained()->restrictOnDelete();
            $table->string('relationship')->comment('RelationshipEnum');
            $table->string('first_name')->comment('First name');
            $table->string('last_name')->comment('Last name');
            $table->string('phone')->nullable()->comment('Mobile, as entered');
            $table->string('email')->nullable()->comment('Contact email while no login is linked (pending claim)');
            $table->json('ministries')->nullable()->comment('CCF ministries this adult serves in');
            $table->boolean('can_pick_up')->default(true)->comment('Allowed to collect the children');
            $table->timestampsTz();

            $table->unique(['church_id', 'user_id']);
            $table->unique(['family_id', 'id']);
        });

        DB::statement('ALTER TABLE guardians ADD COLUMN login_family_key BIGINT UNSIGNED
            GENERATED ALWAYS AS (CASE WHEN user_id IS NOT NULL THEN family_id END) STORED
            COMMENT "One login per family (v1); drop this index to allow second-parent invites"');
        DB::statement('ALTER TABLE guardians ADD UNIQUE INDEX guardians_login_family_key_unique (login_family_key)');

        Schema::table('families', function (Blueprint $table): void {
            $table->foreign(['id', 'primary_guardian_id'], 'families_primary_guardian_in_family')
                ->references(['family_id', 'id'])->on('guardians')->restrictOnDelete();
        });

        Schema::create('children', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->comment('Church the child belongs to')->constrained()->restrictOnDelete();
            $table->foreignId('family_id')->comment('Household')->constrained()->restrictOnDelete();
            $table->string('first_name')->comment('First name');
            $table->string('middle_name')->nullable()->comment('Middle name(s)');
            $table->string('last_name')->comment('Family name');
            $table->string('nickname')->comment('What the child is called; printed big on the card');
            $table->string('gender')->comment('GenderEnum');
            $table->date('date_of_birth')->comment('Birthday');
            $table->text('allergies')->nullable()->comment('Health: shown to Lifeshapers and own family only');
            $table->text('illnesses')->nullable()->comment('Health');
            $table->text('dislikes')->nullable()->comment('Things the child dislikes');
            $table->text('fears')->nullable()->comment('Things the child is afraid of');
            $table->text('guardian_notes')->nullable()->comment('Anything else the guardian shared ("Anything else we should know")');
            $table->string('status')->default(ChildStatusEnum::default()->value)->comment('ChildStatusEnum: active | inactive | graduated');
            $table->timestampTz('status_changed_at')->nullable()->comment('When the status last changed');
            $table->foreignId('status_changed_by')->nullable()->comment('Who changed it')->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('level')->nullable()->comment('Stored Level (config nxtgen.levels), null = none yet; changes only at Moving-Up or by a church admin');
            $table->timestampTz('level_set_at')->nullable()->comment('When the Level was last set');
            $table->foreignId('level_set_by')->nullable()->comment('Who set it')->constrained('users')->nullOnDelete();
            $table->string('photo_path')->nullable()->comment('Private object-storage path; consent required');
            $table->timestampTz('photo_consent_at')->nullable()->comment('When the parent agreed to a photo');
            $table->string('registration_source')->comment('RegistrationSourceEnum');
            $table->timestampsTz();

            $table->index(['church_id', 'status']);
            $table->index(['church_id', 'last_name']);
            $table->index(['church_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('children');
        Schema::table('families', function (Blueprint $table): void {
            $table->dropForeign('families_primary_guardian_in_family');
        });
        Schema::dropIfExists('guardians');
        Schema::dropIfExists('families');
    }
};
