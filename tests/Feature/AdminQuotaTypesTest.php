<?php

namespace Tests\Feature;

use App\Models\QuotaType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQuotaTypesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_quota_types_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.quotas.index'))
            ->assertOk()
            ->assertSee('Cotas');
    }

    public function test_admin_can_create_a_new_quota_type(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.quotas.store'), [
                'code' => 'S',
                'name' => 'Semanal',
                'days' => 7,
                'active' => true,
            ])
            ->assertRedirect(route('admin.quotas.index'));

        $this->assertDatabaseHas('quota_types', [
            'code' => 'S',
            'name' => 'Semanal',
            'days' => 7,
        ]);
    }

    public function test_admin_cannot_create_duplicate_quota_code(): void
    {
        QuotaType::query()->create([
            'code' => 'S',
            'name' => 'Semanal',
            'days' => 7,
            'active' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.quotas.store'), [
                'code' => 'S',
                'name' => 'Semanal - duplicado',
                'days' => 9,
                'active' => true,
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_admin_can_associate_a_vehicle_to_a_quota_type(): void
    {
        $quotaType = QuotaType::query()->create([
            'code' => 'S',
            'name' => 'Semanal',
            'days' => 7,
            'active' => true,
        ]);

        $vehicle = Vehicle::query()->create([
            'model' => 'Chevrolet Onix',
            'plate' => 'ABC1D23',
            'active' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.quotas.add-vehicle', $quotaType), [
                'vehicle_id' => $vehicle->id,
                'quantity' => 5,
                'active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('vehicle_quota_configurations', [
            'quota_type_id' => $quotaType->id,
            'vehicle_id' => $vehicle->id,
            'quantity' => 5,
        ]);
    }
}
