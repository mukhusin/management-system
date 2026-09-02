<?php

use App\Enums\WorkStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phases become a first-class layer between a project and its milestones:
 * Project → Phase → Milestone → Feature Set → Task → Sub-task.
 * Each phase has its own description, requirements, milestones and assignees.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default(WorkStatus::NotStarted->value);
            $table->unsignedTinyInteger('progress')->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->text('gate_note')->nullable();
            $table->foreignId('gate_signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('gate_signed_at')->nullable();
            $table->boolean('gate_forced')->default(false);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
        });

        Schema::create('phase_assignees', function (Blueprint $table) {
            $table->foreignId('phase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['phase_id', 'user_id']);
        });

        // Milestones now hang off a phase (project_id kept, denormalised).
        Schema::table('milestones', function (Blueprint $table) {
            $table->foreignId('phase_id')->nullable()->after('project_id')->constrained()->cascadeOnDelete();
            $table->dropColumn('phase');
        });

        // Sign-off is now recorded on the phase row itself.
        Schema::dropIfExists('phase_signoffs');

        // Requirements can be attached to a phase (else they stay project-level).
        Schema::table('scope_items', function (Blueprint $table) {
            $table->foreignId('phase_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
        });

        // Existing single-value phase pointer is superseded by the phases table.
        if (Schema::hasColumn('projects', 'current_phase')) {
            Schema::table('projects', fn (Blueprint $t) => $t->dropColumn('current_phase'));
        }
    }

    public function down(): void
    {
        Schema::table('projects', fn (Blueprint $t) => $t->string('current_phase')->nullable());
        Schema::table('scope_items', fn (Blueprint $t) => $t->dropConstrainedForeignId('phase_id'));
        Schema::table('milestones', function (Blueprint $t) {
            $t->dropConstrainedForeignId('phase_id');
            $t->string('phase')->nullable();
        });
        Schema::dropIfExists('phase_assignees');
        Schema::dropIfExists('phases');
    }
};
