<?php

use App\Enums\TenderState;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->string('state')->default(TenderState::Draft->value)->after('external_id');
            $table->foreignId('owner_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('service_line_id')->nullable()->after('sector')->constrained()->nullOnDelete();
            $table->string('priority')->nullable()->after('state');
            $table->string('client')->nullable()->after('buyer');
            $table->decimal('estimated_value', 18, 2)->nullable()->after('value');
            $table->text('scope_statement')->nullable()->after('description');
            $table->unsignedInteger('lock_version')->default(0);

            $table->index('state');
        });

        DB::table('tenders')->update(['state' => TenderState::Draft->value]);
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
            $table->dropConstrainedForeignId('service_line_id');
            $table->dropIndex(['state']);
            $table->dropColumn([
                'state', 'priority', 'client', 'estimated_value', 'scope_statement', 'lock_version',
            ]);
        });
    }
};
