<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * A Check-in's revision (roll call 05, Astra): a re-check-in after an Undo revives the SAME row, so a tap on Who's in
 * made before the Undo could otherwise mark the new Check-in. Every revival bumps it; a present mark names the revision
 * it was tapped on and only that one is marked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_ins', function (Blueprint $table): void {
            $table->unsignedInteger('revision')->default(0)->after('squad_id')->comment('Bumped each time the row is revived after an Undo; a present mark must name it');
        });
    }

    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table): void {
            $table->dropColumn('revision');
        });
    }
};
