<?php

namespace Tests\Unit;

use App\Enums\AuditAction;
use App\Models\AdminAuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_redacts_sensitive_metadata(): void
    {
        $user = User::factory()->create();

        app(AuditService::class)->log(
            AuditAction::ViewDocument,
            [
                'registration_uuid' => '9bad59d1-42ac-44cb-ac8c-dac2eb6bb9e4',
                'document' => 'cnh_front',
                'cpf' => '39053344705',
                'biometric' => 'raw-face-vector',
                'secret' => 'super-secret',
            ],
            null,
            $user,
        );

        $log = AdminAuditLog::query()->firstOrFail();

        $this->assertSame('[redacted]', $log->metadata['cpf']);
        $this->assertSame('[redacted]', $log->metadata['biometric']);
        $this->assertSame('[redacted]', $log->metadata['secret']);
        $this->assertSame('9bad59d1-42ac-44cb-ac8c-dac2eb6bb9e4', $log->metadata['registration_uuid']);
        $this->assertSame('cnh_front', $log->metadata['document']);
        $this->assertSame((string) $user->id, (string) $log->user_id);
    }
}
