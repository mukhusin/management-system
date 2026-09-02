<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenders', function (Blueprint $table) {
            $table->id();

            // A stable per-source identifier so re-fetching the same
            // notice never creates a duplicate row.
            $table->string('source');          // e.g. world_bank, ted, taneps, ppra, reliefweb
            $table->string('external_id');     // id/reference as given by the source
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('country')->nullable();
            $table->string('sector')->nullable();
            $table->string('buyer')->nullable();       // issuing agency / donor
            $table->decimal('value', 18, 2)->nullable();
            $table->string('currency', 8)->nullable();
            $table->date('published_date')->nullable();
            $table->date('deadline_date')->nullable();
            $table->string('url')->nullable();
            $table->json('raw')->nullable();    // original payload, for debugging/reprocessing
            $table->timestamps();

            $table->unique(['source', 'external_id']);
            $table->index(['deadline_date']);
            $table->index(['country']);
            $table->index(['sector']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenders');
    }
};
