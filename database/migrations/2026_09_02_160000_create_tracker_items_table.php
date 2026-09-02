<?php

use App\Enums\Priority;
use App\Enums\TrackerStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_items', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable()->unique();
            $table->string('category');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('service_line_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default(TrackerStatus::NotStarted->value);
            $table->string('priority')->default(Priority::Medium->value);
            $table->unsignedTinyInteger('progress')->nullable();
            $table->string('next_action')->nullable();
            $table->date('entry_date')->nullable();
            $table->date('due_date')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index('category');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_items');
    }
};
