<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\ClientRegistration;
use App\Models\User;
use App\Services\DocumentStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_registration_list(): void
    {
        $registration = ClientRegistration::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.index'))
            ->assertOk()
            ->assertSee($registration->full_name)
            ->assertSee('Novo');
    }

    public function test_admin_can_filter_registrations_by_status(): void
    {
        ClientRegistration::factory()->create(['status' => RegistrationStatus::Aprovado]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.index', ['status' => 'aprovado']))
            ->assertOk()
            ->assertSee('Aprovado');
    }

    public function test_admin_can_view_registration_details_with_documents(): void
    {
        $registration = ClientRegistration::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.show', $registration))
            ->assertOk()
            ->assertSee($registration->full_name)
            ->assertSee($registration->maskedCpf())
            ->assertSee('Foto da CNH');
    }

    public function test_admin_can_update_registration_status(): void
    {
        $registration = ClientRegistration::factory()->create(['status' => RegistrationStatus::Novo]);

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.registrations.status', $registration), [
                'status' => 'aprovado',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('client_registrations', [
            'id' => $registration->id,
            'status' => 'aprovado',
        ]);
    }

    public function test_status_update_flashes_auto_redirect_flag_on_success(): void
    {
        $registration = ClientRegistration::factory()->create(['status' => RegistrationStatus::Novo]);

        $this->actingAs(User::factory()->create())
            ->from(route('admin.registrations.show', $registration))
            ->patch(route('admin.registrations.status', $registration), [
                'status' => 'em_analise',
            ])
            ->assertRedirect(route('admin.registrations.show', $registration))
            ->assertSessionHas('success', 'Status atualizado com sucesso.')
            ->assertSessionHas('status_updated_redirect');
    }

    public function test_status_update_with_invalid_value_does_not_schedule_redirect(): void
    {
        $registration = ClientRegistration::factory()->create(['status' => RegistrationStatus::Novo]);

        $this->actingAs(User::factory()->create())
            ->from(route('admin.registrations.show', $registration))
            ->patch(route('admin.registrations.status', $registration), [
                'status' => 'inexistente',
            ])
            ->assertSessionHasErrors('status')
            ->assertSessionMissing('status_updated_redirect');
    }

    public function test_show_page_renders_auto_redirect_attributes_when_flag_is_set(): void
    {
        $registration = ClientRegistration::factory()->create(['status' => RegistrationStatus::Novo]);

        $this->actingAs(User::factory()->create())
            ->withSession(['status_updated_redirect' => true])
            ->get(route('admin.registrations.show', $registration))
            ->assertOk()
            ->assertSee('data-status-redirect-after="2000"', false)
            ->assertSee('data-status-redirect-url="', false);
    }

    public function test_registrations_list_shows_confirmation_after_auto_redirect(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.index', ['status_updated' => 1]))
            ->assertOk()
            ->assertSee('Status atualizado com sucesso.', false);
    }

    public function test_show_page_renders_documents_as_fancybox_without_new_tab(): void
    {
        Storage::fake('local');

        $registration = ClientRegistration::factory()->create();
        $storage = app(DocumentStorageService::class);

        $front = $storage->store(
            UploadedFile::fake()->image('frente.jpg', 600, 400),
            $registration->uuid,
            'cnh_front'
        );
        $selfie = $storage->store(
            UploadedFile::fake()->image('selfie.jpg', 600, 400),
            $registration->uuid,
            'selfie'
        );
        $registration['cnh_front_path'] = $front;
        $registration['selfie_path'] = $selfie;
        $registration->save();

        $html = $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.show', $registration))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('target="_blank"', $html);
        $this->assertSame(2, substr_count($html, 'data-fancybox="cadastro-docs"'));
        $this->assertStringContainsString('aria-label="Ampliar Foto da CNH (frente)"', $html);
        $this->assertStringContainsString('alt="Foto da CNH (frente)"', $html);
        $this->assertStringContainsString('/foto/selfie', $html);
        $this->assertStringContainsString('Não enviado', $html);
    }

    public function test_document_thumbnails_have_uniform_title_and_image_area(): void
    {
        $registration = ClientRegistration::factory()->create();

        $html = $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.show', $registration))
            ->assertOk()
            ->getContent();

        $this->assertSame(4, substr_count($html, 'min-h-[2.5rem]'));
        $this->assertSame(4, substr_count($html, 'aspect-[3/4]'));
        $this->assertStringContainsString('max-w-2xl', $html);
    }

    public function test_guest_cannot_view_private_documents(): void
    {
        Storage::fake('local');

        $registration = ClientRegistration::factory()->create();
        $path = app(DocumentStorageService::class)->store(
            UploadedFile::fake()->image('frente.jpg', 600, 400),
            $registration->uuid,
            'cnh_front'
        );
        $registration->update(['cnh_front_path' => $path]);

        $this->get(route('admin.registrations.photo', [$registration, 'cnh_front']))
            ->assertRedirect(route('admin.login'));
    }

    public function test_authenticated_admin_can_view_private_documents(): void
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
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');
    }

    public function test_registration_urls_are_keyed_by_uuid_not_sequential_id(): void
    {
        $registration = ClientRegistration::factory()->create();

        $url = route('admin.registrations.show', $registration);

        $this->assertStringEndsWith('/'.$registration->uuid, $url);
    }

    public function test_unknown_uuid_returns_404_for_registration_routes(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/cadastros/'.Str::uuid())
            ->assertNotFound();
    }
}
