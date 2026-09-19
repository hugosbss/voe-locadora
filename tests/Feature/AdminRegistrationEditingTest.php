<?php

namespace Tests\Feature;

use App\Models\ClientRegistration;
use App\Models\QuotaType;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DocumentStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminRegistrationEditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_registration_edit_form(): void
    {
        [$registration] = $this->createRegistrationWithQuota();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.edit', $registration))
            ->assertOk()
            ->assertSee('Editar informações do veículo')
            ->assertSee('Informações do veículo')
            ->assertSee('Foto da retirada')
            ->assertSee('Tirar foto')
            ->assertSee('Galeria');
    }

    public function test_admin_can_update_registration_and_replace_vehicle_photos(): void
    {
        Storage::fake('local');

        [$registration] = $this->createRegistrationWithQuota();
        $storage = app(DocumentStorageService::class);
        $oldPath = $storage->store(
            UploadedFile::fake()->image('old.jpg', 600, 400),
            $registration->uuid,
            'vehicle_pickup',
        );
        $registration->update(['vehicle_pickup_photo_path' => $oldPath]);

        $response = $this->actingAs(User::factory()->create())
            ->put(route('admin.registrations.update', $registration), [
                'vehicle_observation' => 'Revisado pela administração.',
                'vehicle_pickup_photo' => UploadedFile::fake()->image('new-pickup.png', 600, 400),
                'vehicle_delivery_photo' => UploadedFile::fake()->image('delivery.png', 600, 400),
            ]);

        $response->assertRedirect(route('admin.registrations.show', $registration));
        $registration->refresh();

        $this->assertSame('Revisado pela administração.', $registration->vehicle_observation);
        $this->assertSame('2026-09-19', $registration->start_date);
        $this->assertSame('2026-10-19', $registration->end_date);
        $this->assertNotSame($oldPath, $registration->vehicle_pickup_photo_path);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($registration->vehicle_pickup_photo_path);
        Storage::disk('local')->assertExists($registration->vehicle_delivery_photo_path);
    }

    public function test_non_admin_cannot_edit_a_registration(): void
    {
        [$registration] = $this->createRegistrationWithQuota();

        $this->actingAs(User::factory()->create(['role' => 'operator']))
            ->get(route('admin.registrations.edit', $registration))
            ->assertForbidden();
    }

    /**
     * @return array{0: ClientRegistration, 1: Vehicle, 2: QuotaType}
     */
    private function createRegistrationWithQuota(): array
    {
        $quotaType = QuotaType::query()->create([
            'code' => 'M',
            'name' => 'Mensal',
            'days' => 30,
            'active' => true,
        ]);

        $vehicle = Vehicle::query()->create([
            'model' => 'Chevrolet Onix',
            'plate' => 'ABC1D23',
            'active' => true,
        ]);

        $vehicle->quotaConfigurations()->create([
            'quota_type_id' => $quotaType->id,
            'quantity' => 10,
            'active' => true,
        ]);

        $registration = ClientRegistration::factory()->create([
            'vehicle_id' => $vehicle->id,
            'quota_type_id' => $quotaType->id,
            'start_date' => '2026-09-19',
            'end_date' => '2026-10-19',
            'quota_days' => 30,
        ]);

        return [$registration, $vehicle, $quotaType];
    }
}
