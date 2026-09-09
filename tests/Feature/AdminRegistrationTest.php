<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\ClientRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_guest_cannot_view_private_documents(): void
    {
        Storage::fake('local');

        $registration = ClientRegistration::factory()->create();
        $path = Storage::disk('local')->putFile(
            'cadastros/teste/cnh_front',
            UploadedFile::fake()->image('frente.jpg', 600, 400)
        );
        $registration->update(['cnh_front_path' => $path]);

        $this->get(route('admin.registrations.photo', [$registration, 'cnh_front']))
            ->assertRedirect(route('admin.login'));
    }

    public function test_authenticated_admin_can_view_private_documents(): void
    {
        Storage::fake('local');

        $registration = ClientRegistration::factory()->create();
        $path = Storage::disk('local')->putFile(
            'cadastros/teste/cnh_front',
            UploadedFile::fake()->image('frente.jpg', 600, 400)
        );
        $registration->update(['cnh_front_path' => $path]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.photo', [$registration, 'cnh_front']))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');
    }
}
