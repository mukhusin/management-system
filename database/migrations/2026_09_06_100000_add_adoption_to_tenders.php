<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A tender is either an "opportunity" (ingested from an external source and
 * not yet acted on) or is in the "pipeline" (someone chose to pursue it and
 * it is now tracked through the lifecycle). adopted_at marks the crossover.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->timestamp('adopted_at')->nullable()->after('state');
            $table->foreignId('adopted_by')->nullable()->after('adopted_at')->constrained('users')->nullOnDelete();
            $table->index('adopted_at');
        });

        // Manually registered tenders were always pipeline items.
        DB::table('tenders')
            ->where('source', 'manual')
            ->update(['adopted_at' => DB::raw('created_at'), 'adopted_by' => DB::raw('user_id')]);
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('adopted_by');
            $table->dropColumn('adopted_at');
        });
    }
};
