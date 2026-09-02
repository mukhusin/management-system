<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scope_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('code');           // S1, S2, ...
            $table->text('description');
            $table->string('source')->default('manual'); // manual | tender
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('scope_item_task', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scope_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->unique(['scope_item_id', 'task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scope_item_task');
        Schema::dropIfExists('scope_items');
    }
};
