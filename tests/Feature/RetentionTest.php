<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\RegistrationStatus;
use App\Models\ClientRegistration;
use App\Services\DocumentStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RetentionTest extends TestCase
{
    use RefreshDatabase;

    private function createExpiredWithDocument(): ClientRegistration
    {
        Storage::fake('local');

        $registration = ClientRegistration::factory()->create([
            'created_at' => now()->subDays(120),
            'status' => RegistrationStatus::Novo,
        ]);

        $path = app(DocumentStorageService::class)->store(
            UploadedFile::fake()->image('frente.jpg', 600, 400),
            $registration->uuid,
            'cnh_front'
        );
        $registration['cnh_front_path'] = $path;
        $registration->save();

        return $registration;
    }

    public function test_dry_run_lists_expired_without_deleting(): void
    {
        config(['retention.statuses.novo' => 90]);

        $registration = $this->createExpiredWithDocument();

        Artisan::call('cadastros:expurgo', ['--dry-run' => true]);

        $this->assertDatabaseHas('client_registrations', ['id' => $registration->id]);
        Storage::disk('local')->assertExists($registration->cnh_front_path);
        $this->assertDatabaseMissing('admin_audit_logs', [
            'action' => AuditAction::DeleteRegistration->value,
        ]);
    }

    public function test_purge_removes_registration_documents_and_audits(): void
    {
        config(['retention.statuses.novo' => 90]);

        $registration = $this->createExpiredWithDocument();

        Artisan::call('cadastros:expurgo');

        $this->assertDatabaseMissing('client_registrations', ['id' => $registration->id]);
        Storage::disk('local')->assertMissing($registration->cnh_front_path);

        // A auditoria é preservada; a integridade referencial é mantida via
        // ON DELETE SET NULL, e a UUID do cadastro fica no metadata.
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => AuditAction::DeleteRegistration->value,
            'registration_id' => null,
        ]);
    }

    public function test_recent_registration_is_not_purged(): void
    {
        config(['retention.statuses.novo' => 90]);

        $registration = ClientRegistration::factory()->create([
            'created_at' => now()->subDays(30),
        ]);

        Artisan::call('cadastros:expurgo');

        $this->assertDatabaseHas('client_registrations', ['id' => $registration->id]);
    }

    public function test_purge_is_idempotent_on_retry(): void
    {
        config(['retention.statuses.novo' => 90]);

        $registration = $this->createExpiredWithDocument();

        Artisan::call('cadastros:expurgo');
        Artisan::call('cadastros:expurgo');

        $this->assertDatabaseMissing('client_registrations', ['id' => $registration->id]);
    }
}
