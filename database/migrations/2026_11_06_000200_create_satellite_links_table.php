<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Links (GLOSSARY; Roy 2026-11-06, TODO #31 step 3): a satellite's own addresses on its QR poster — its parents'
 * Facebook group, its WhatsApp group, any link an admin names — each printed as a QR with its label. Up to 6 a
 * satellite, so the poster still prints on one page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('satellite_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->comment('The church (the satellite\'s)')->constrained()->restrictOnDelete();
            $table->foreignId('satellite_id')->comment('The satellite whose poster shows it')->constrained()->cascadeOnDelete();
            $table->string('label', 60)->comment('What the poster says under its QR, e.g. "Parents\' WhatsApp group"');
            $table->string('url', 500)->comment('The address its QR opens: https only');
            $table->unsignedSmallInteger('position')->comment('Its place on the poster, first = lowest');
            $table->timestampsTz();

            $table->index(['satellite_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satellite_links');
    }
};
