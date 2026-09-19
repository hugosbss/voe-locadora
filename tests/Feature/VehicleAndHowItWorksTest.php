<?php

namespace Tests\Feature;

use App\Models\QuotaType;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleAndHowItWorksTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_how_it_works_page_is_accessible_without_authentication(): void
    {
        $this->get(route('public.how-it-works'))
            ->assertOk()
            ->assertSee('Como funciona')
            ->assertSee('Quero fazer meu cadastro');
    }

    public function test_vehicle_can_be_created_with_quota_configuration(): void
    {
        $type = QuotaType::query()->create([
            'code' => 'S',
            'name' => 'Semanal',
            'days' => 7,
            'active' => true,
        ]);

        $vehicle = Vehicle::query()->create([
            'model' => 'Chevrolet Onix 1.0 Flex',
            'plate' => 'TXD6J56',
            'active' => true,
        ]);

        $vehicle->quotaConfigurations()->create([
            'quota_type_id' => $type->id,
            'quantity' => 8,
            'active' => true,
        ]);

        $this->assertDatabaseHas('vehicles', ['plate' => 'TXD6J56']);
        $this->assertDatabaseHas('vehicle_quota_configurations', ['vehicle_id' => $vehicle->id, 'quantity' => 8]);
        $this->assertSame(8, $vehicle->quotaConfigurations()->first()->quantity);
    }
}
