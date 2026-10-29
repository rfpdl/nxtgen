<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Name cards (DECISIONS 2026-10-29; GLOSSARY "Card request"): one ask for a new Name card for one Child, moving
 * Asked → Approved → Printed → Given, or Refused. Each step keeps when and who; the fee is in fils (AED 10 = 1000, a
 * first card 0) and is marked paid at the desk.
 *
 * One OPEN request per Child, held by the database: `open_child_id` is worked out by MySQL — the child while the
 * request is Asked, Approved or Printed, null once Given or Refused — and is unique. So closing a request frees the
 * Child for the next one with nothing to remember to clear, and two staff filing at once still make one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->comment('Denormalised from the child, so every query can name the church')->constrained()->restrictOnDelete();
            $table->foreignId('child_id')->comment('The child the new card is for')->constrained('children')->restrictOnDelete();
            $table->string('reason', 20)->comment('lost | damaged | first_card (CardReasonEnum)');
            $table->string('note', 200)->nullable()->comment('What staff were told, e.g. "lost at the park"');
            $table->string('step', 20)->default('asked')->comment('asked | approved | printed | given | refused (CardStepEnum)');
            $table->unsignedInteger('fee_minor')->comment('What the card costs, in fils: 1000 for lost or damaged, 0 for a first card');
            $table->timestampTz('paid_at')->nullable()->comment('When the fee was marked paid; null = not paid (or free)');
            $table->foreignId('paid_by')->nullable()->comment('Who marked it paid')->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable()->comment('When an admin approved it for printing');
            $table->foreignId('approved_by')->nullable()->comment('Who approved it')->constrained('users')->nullOnDelete();
            $table->timestampTz('printed_at')->nullable()->comment('When the card was marked printed');
            $table->foreignId('printed_by')->nullable()->comment('Who marked it printed')->constrained('users')->nullOnDelete();
            $table->timestampTz('given_at')->nullable()->comment('When the card was given to the child or parent');
            $table->foreignId('given_by')->nullable()->comment('Who gave it')->constrained('users')->nullOnDelete();
            $table->timestampTz('refused_at')->nullable()->comment('When an admin refused it');
            $table->foreignId('refused_by')->nullable()->comment('Who refused it')->constrained('users')->nullOnDelete();
            $table->string('refused_reason', 200)->nullable()->comment('Why it was refused (required to refuse)');
            $table->foreignId('created_by')->nullable()->comment('Who filed it; null = made by the app (a first card)')->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('open_child_id')->nullable()
                ->storedAs("case when step in ('asked', 'approved', 'printed') then child_id end")
                ->comment('The child while the request is open, else null — unique, so one open request per child');
            $table->timestampsTz();

            $table->unique('open_child_id');
            $table->index(['church_id', 'step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_requests');
    }
};
