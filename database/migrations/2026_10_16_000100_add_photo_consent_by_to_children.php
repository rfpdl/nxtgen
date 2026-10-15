<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Who gave photo consent (plan §5 `photo_consent_by`) — always a parent of the child's family. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('children', function (Blueprint $table): void {
            $table->foreignId('photo_consent_by')->nullable()->after('photo_consent_at')
                ->comment('The parent (user) who agreed to a photo')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('children', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('photo_consent_by');
        });
    }
};
