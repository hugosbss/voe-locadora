<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\RegistrationStatus;
use App\Models\AdminAuditLog;
use App\Models\ClientRegistration;
use App\Models\User;
use App\Services\DocumentStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewing_registration_is_audited(): void
    {
        $registration = ClientRegistration::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.show', $registration))
            ->assertOk();

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => AuditAction::ViewRegistration->value,
            'registration_id' => $registration->id,
        ]);
    }

    public function test_viewing_document_is_audited_without_personal_data(): void
    {
        Storage::fake('local');

        $registration = ClientRegistration::factory()->create();
        $path = app(DocumentStorageService::class)->store(
            UploadedFile::fake()->image('frente.jpg', 600, 400),
            $registration->uuid,
            'cnh_front'
        );
        $registration['cnh_front_path'] = $path;
        $registration->save();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.photo', [$registration, 'cnh_front']))
            ->assertOk();

        $log = AdminAuditLog::query()->where('action', AuditAction::ViewDocument->value)->firstOrFail();

        $this->assertSame('cnh_front', $log->metadata['document'] ?? null);
        $this->assertArrayNotHasKey('cpf', $log->metadata);
    }

    public function test_status_change_is_audited_with_before_and_after(): void
    {
        $registration = ClientRegistration::factory()->create(['status' => RegistrationStatus::Novo]);

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.registrations.status', $registration), ['status' => 'aprovado'])
            ->assertRedirect();

        $log = AdminAuditLog::query()->where('action', AuditAction::UpdateStatus->value)->firstOrFail();

        $this->assertSame('novo', $log->metadata['previous']);
        $this->assertSame('aprovado', $log->metadata['new']);
        $this->assertStringNotContainsString((string) $registration->cpf, json_encode($log->metadata));
    }

    public function test_guest_requests_are_not_audited_as_registration_views(): void
    {
        $registration = ClientRegistration::factory()->create();

        $this->get(route('admin.registrations.show', $registration))
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseMissing('admin_audit_logs', [
            'action' => AuditAction::ViewRegistration->value,
        ]);
    }
}
