<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Name cards 04 (GLOSSARY "Print list"): the approved cards an admin put on one list to print. Its identity is its exact
 * members, kept here when it is made — never worked out again from a search or a time — so downloading it and marking
 * it printed are always about the same cards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_lists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->comment('The church whose cards it lists')->constrained()->restrictOnDelete();
            $table->foreignId('made_by')->nullable()->comment('The admin who made it')->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['church_id', 'created_at']);
        });

        Schema::create('print_list_cards', function (Blueprint $table): void {
            $table->foreignId('print_list_id')->comment('The print list')->constrained()->cascadeOnDelete();
            $table->foreignId('card_request_id')->comment('A Card request on it — Approved when the list was made')->constrained()->restrictOnDelete();

            $table->primary(['print_list_id', 'card_request_id']);
            $table->index('card_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_list_cards');
        Schema::dropIfExists('print_lists');
    }
};
