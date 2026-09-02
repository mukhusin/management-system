<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tenders, projects and tasks can each have more than one owner / assignee.
 * Moves the single owner_id / assignee_id columns to pivot tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tender_owners', function (Blueprint $table) {
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['tender_id', 'user_id']);
        });

        Schema::create('project_owners', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_id', 'user_id']);
        });

        Schema::create('task_assignees', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['task_id', 'user_id']);
        });

        // Carry existing single values over.
        DB::statement('INSERT INTO tender_owners (tender_id, user_id) SELECT id, owner_id FROM tenders WHERE owner_id IS NOT NULL');
        DB::statement('INSERT INTO project_owners (project_id, user_id) SELECT id, owner_id FROM projects WHERE owner_id IS NOT NULL');
        DB::statement('INSERT INTO task_assignees (task_id, user_id) SELECT id, assignee_id FROM tasks WHERE assignee_id IS NOT NULL');

        Schema::table('tenders', fn (Blueprint $t) => $t->dropConstrainedForeignId('owner_id'));
        Schema::table('projects', fn (Blueprint $t) => $t->dropConstrainedForeignId('owner_id'));
        Schema::table('tasks', fn (Blueprint $t) => $t->dropConstrainedForeignId('assignee_id'));
    }

    public function down(): void
    {
        Schema::table('tenders', fn (Blueprint $t) => $t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete());
        Schema::table('projects', fn (Blueprint $t) => $t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete());
        Schema::table('tasks', fn (Blueprint $t) => $t->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete());

        DB::statement('UPDATE tenders t JOIN tender_owners o ON o.tender_id = t.id SET t.owner_id = o.user_id');
        DB::statement('UPDATE projects p JOIN project_owners o ON o.project_id = p.id SET p.owner_id = o.user_id');
        DB::statement('UPDATE tasks k JOIN task_assignees a ON a.task_id = k.id SET k.assignee_id = a.user_id');

        Schema::dropIfExists('tender_owners');
        Schema::dropIfExists('project_owners');
        Schema::dropIfExists('task_assignees');
    }
};
