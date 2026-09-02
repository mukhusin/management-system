<?php

use App\Enums\Priority;
use App\Enums\ServiceRequestState;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable()->unique();
            $table->string('source');
            $table->string('state')->default(ServiceRequestState::New->value);
            $table->string('priority')->default(Priority::Medium->value);
            $table->foreignId('service_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('client')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('summary');
            $table->text('details')->nullable();
            $table->decimal('estimated_value', 18, 2)->nullable();
            $table->string('currency', 8)->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index('state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
