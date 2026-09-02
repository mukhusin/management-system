<?php

use App\Enums\Priority;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('type')->default(ProjectType::Engagement->value);
            $table->string('status')->default(ProjectStatus::NotStarted->value);
            $table->string('priority')->default(Priority::Medium->value);
            $table->string('current_phase')->nullable();
            $table->text('description')->nullable();
            $table->text('scope_statement')->nullable();
            $table->string('client')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->decimal('budget', 18, 2)->nullable();
            $table->string('currency', 8)->nullable();
            $table->date('start_date')->nullable();
            $table->date('target_deadline')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('next_action')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index('status');
            $table->index('target_deadline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
