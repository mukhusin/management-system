<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phase_signoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('phase');
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->boolean('forced')->default(false); // advanced despite incomplete milestones
            $table->timestamp('signed_at');

            $table->index(['project_id', 'phase']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phase_signoffs');
    }
};
