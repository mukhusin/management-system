<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AuditImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_logs_cannot_be_updated(): void
    {
        Tender::factory()->create();
        $log = AuditLog::firstOrFail();

        $this->expectException(RuntimeException::class);
        $log->update(['event' => 'tampered']);
    }

    public function test_audit_logs_cannot_be_deleted(): void
    {
        Tender::factory()->create();
        $log = AuditLog::firstOrFail();

        $this->expectException(RuntimeException::class);
        $log->delete();
    }

    public function test_creating_a_model_writes_a_created_event(): void
    {
        $tender = Tender::factory()->create();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => $tender->getMorphClass(),
            'auditable_id' => $tender->id,
            'event' => 'created',
        ]);
    }
}
