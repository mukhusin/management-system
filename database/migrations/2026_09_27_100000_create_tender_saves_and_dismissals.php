<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user bookmarking on the external opportunities feed — distinct from
 * "adopt" (which commits the tender into everyone's pipeline). Saving
 * shortlists it for yourself; dismissing hides noise you've decided isn't
 * relevant, without affecting what anyone else sees.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tender_saves', function (Blueprint $table) {
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->primary(['tender_id', 'user_id']);
        });

        Schema::create('tender_dismissals', function (Blueprint $table) {
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->primary(['tender_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_dismissals');
        Schema::dropIfExists('tender_saves');
    }
};
