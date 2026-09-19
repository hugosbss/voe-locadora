<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Exceptions\QuotaUnavailableException;
use App\Models\ClientRegistration;
use App\Models\QuotaType;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleQuotaConfiguration;
use App\Services\QuotaAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regra de disponibilidade de cotas POR PERÍODO.
 *
 * Regras cobertas:
 *  - Datas inclusivas: conflito quando start_a <= end_b e end_a >= start_b.
 *  - Consomem a vaga os status configurados em quotas.consuming_statuses
 *    (novo, em_analise, aprovado); reprovado não consome.
 *  - A duração do período segue quotas.duration_mode (exact|max|min).
 *  - quotas.vehicle_exclusive força exclusividade física do veículo.
 *
 * A implementação de referência é App\Services\QuotaAvailabilityService.
 */
class QuotaAvailabilityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('quotas.duration_mode', 'exact');
        config()->set('quotas.vehicle_exclusive', false);
        config()->set('quotas.consuming_statuses', [
            RegistrationStatus::Novo->value,
            RegistrationStatus::EmAnalise->value,
            RegistrationStatus::Aprovado->value,
        ]);
    }

    private function availability(): QuotaAvailabilityService
    {
        return app(QuotaAvailabilityService::class);
    }

    /**
     * @return array{0: Vehicle, 1: QuotaType, 2: VehicleQuotaConfiguration}
     */
    private function makeVehicleQuota(int $quantity, string $code = 'FS', int $days = 2, int $suffix = 1): array
    {
        $vehicle = Vehicle::query()->create([
            'model' => "Veiculo {$suffix}",
            'plate' => "PLACA-{$suffix}",
            'active' => true,
        ]);

        $type = QuotaType::query()->create([
            'code' => $code,
            'name' => "Cota {$code}",
            'days' => $days,
            'active' => true,
        ]);

        $config = VehicleQuotaConfiguration::query()->create([
            'vehicle_id' => $vehicle->id,
            'quota_type_id' => $type->id,
            'quantity' => $quantity,
            'active' => true,
        ]);

        return [$vehicle, $type, $config];
    }

    private function reserve(string $status, int $vehicleId, int $quotaTypeId, string $start, string $end): ClientRegistration
    {
        return ClientRegistration::factory()->create([
            'vehicle_id' => $vehicleId,
            'quota_type_id' => $quotaTypeId,
            'start_date' => $start,
            'end_date' => $end,
            'status' => RegistrationStatus::from($status),
        ]);
    }

    private function availableAt(VehicleQuotaConfiguration $config, string $start, string $end): int
    {
        return $this->availability()->availableFor($config, Carbon::parse($start), Carbon::parse($end));
    }

    private function seedContractTemplate(): void
    {
        Storage::fake('local');

        $pdf = new \FPDF('P', 'pt', [595.276, 841.89]);

        for ($page = 1; $page <= 12; $page++) {
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 12);
            $pdf->Text(40, 40, 'Contrato sintetico para testes - pagina '.$page);
        }

        Storage::disk('local')->put(config('contracts.template_path'), $pdf->Output('S'));
    }

    private function signatureDataUrl(): string
    {
        $image = imagecreatetruecolor(520, 140);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));

        $ink = imagecolorallocate($image, 15, 15, 15);
        imageline($image, 40, 110, 480, 70, $ink);
        imageline($image, 60, 95, 470, 60, $ink);
        imageline($image, 80, 85, 450, 50, $ink);

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'full_name' => 'Maria da Silva Souza',
            'cpf' => '390.533.447-05',
            'birth_date' => '1990-05-10',
            'phone' => '(11) 91234-5678',
            'whatsapp' => '(11) 91234-5678',
            'email' => 'maria@example.com',
            'cep' => '01310-100',
            'address' => 'Avenida Paulista',
            'address_number' => '1000',
            'neighborhood' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
            'cnh_number' => '12345678901',
            'cnh_category' => 'B',
            'cnh_expiry_date' => '2030-01-01',
            'vehicle_id' => null,
            'quota_type_id' => null,
            'start_date' => null,
            'end_date' => null,
            'veracity_declaration_accepted' => '1',
            'privacy_policy_accepted' => '1',
            'contract_signer_name' => 'Maria da Silva Souza',
            'contract_accepted' => '1',
            'cnh_front_file' => UploadedFile::fake()->image('cnh-front.jpg', 600, 400),
            'cnh_back_file' => UploadedFile::fake()->image('cnh-back.jpg', 600, 400),
            'proof_of_residence_file' => UploadedFile::fake()->image('comprovante.jpg', 600, 400),
            'selfie_file' => UploadedFile::fake()->image('selfie.jpg', 600, 400),
            'contract_signature' => $this->signatureDataUrl(),
        ];
    }

    public function test_a_approved_booking_reduces_availability_for_its_period(): void
    {
        [$vehicle, $type, $config] = $this->makeVehicleQuota(8);

        $this->reserve('aprovado', $vehicle->id, $type->id, '2026-10-01', '2026-10-05');

        $this->assertSame(7, $this->availableAt($config, '2026-10-01', '2026-10-05'));
        $this->assertSame(8, $this->availableAt($config, '2026-11-01', '2026-11-05'));
    }

    public function test_b_availability_returns_after_end_date_passes(): void
    {
        [, , $config] = $this->makeVehicleQuota(8);

        $this->reserve('aprovado', $config->vehicle_id, $config->quota_type_id, '2025-01-01', '2025-01-05');

        $this->travelTo(Carbon::parse('2025-01-06'));

        $this->assertSame(8, $this->availableAt($config, '2025-01-06', '2025-01-10'));
        $this->assertSame(0, $this->availability()->statsForConfiguration($config)['reserved_today']);
    }

    public function test_c_disjoint_periods_do_not_interfere_with_each_other(): void
    {
        [$vehicle, $type, $config] = $this->makeVehicleQuota(8);

        $this->reserve('aprovado', $vehicle->id, $type->id, '2026-01-01', '2026-01-05');
        $this->reserve('aprovado', $vehicle->id, $type->id, '2026-03-01', '2026-03-05');
        $this->reserve('aprovado', $vehicle->id, $type->id, '2026-05-01', '2026-05-05');

        $this->assertSame(7, $this->availableAt($config, '2026-01-01', '2026-01-05'));
        $this->assertSame(7, $this->availableAt($config, '2026-03-01', '2026-03-05'));
        $this->assertSame(7, $this->availableAt($config, '2026-05-01', '2026-05-05'));
        $this->assertSame(8, $this->availableAt($config, '2026-07-01', '2026-07-05'));
    }

    public function test_d_ninth_booking_in_same_period_is_rejected_by_validation(): void
    {
        [$vehicle, $type] = $this->makeVehicleQuota(8);

        $start = now('America/Sao_Paulo')->toDateString();
        $end = now('America/Sao_Paulo')->addDay()->toDateString();

        for ($i = 0; $i < 8; $i++) {
            $this->reserve('novo', $vehicle->id, $type->id, $start, $end);
        }

        $this->seedContractTemplate();

        $payload = $this->validPayload();
        $payload['vehicle_id'] = (string) $vehicle->id;
        $payload['quota_type_id'] = (string) $type->id;
        $payload['start_date'] = $start;
        $payload['end_date'] = $end;

        $this->post('/cadastro', $payload)->assertSessionHasErrors('quota_type_id');

        $this->assertSame(8, ClientRegistration::query()->count());
    }

    public function test_e_partial_and_touching_overlaps_count_as_conflicts(): void
    {
        [$vehicle, $type, $config] = $this->makeVehicleQuota(8);

        $this->reserve('aprovado', $vehicle->id, $type->id, '2026-10-01', '2026-10-10');
        $this->reserve('aprovado', $vehicle->id, $type->id, '2026-10-10', '2026-10-12');
        $this->reserve('aprovado', $vehicle->id, $type->id, '2026-11-01', '2026-11-10');

        // Janela no meio da primeira reserva: cruza apenas 1.
        $this->assertSame(7, $this->availableAt($config, '2026-10-05', '2026-10-09'));

        // Borda encostando (fim=10/10, início=10/10): as duas contam.
        $this->assertSame(6, $this->availableAt($config, '2026-10-10', '2026-10-10'));

        // Período disjunto continua cheio.
        $this->assertSame(8, $this->availableAt($config, '2026-12-01', '2026-12-05'));
    }

    public function test_f_rejected_registration_does_not_consume_quota(): void
    {
        [$vehicle, $type, $config] = $this->makeVehicleQuota(8);

        $this->reserve('aprovado', $vehicle->id, $type->id, '2026-10-01', '2026-10-05');
        $this->reserve('reprovado', $vehicle->id, $type->id, '2026-10-01', '2026-10-05');

        $this->assertSame(7, $this->availableAt($config, '2026-10-01', '2026-10-05'));
    }

    public function test_g_new_and_under_review_registrations_consume_quota(): void
    {
        [$vehicle, $type, $config] = $this->makeVehicleQuota(8);

        $this->reserve('novo', $vehicle->id, $type->id, '2026-10-01', '2026-10-05');
        $this->reserve('em_analise', $vehicle->id, $type->id, '2026-10-01', '2026-10-05');

        $this->assertSame(6, $this->availableAt($config, '2026-10-01', '2026-10-05'));
    }

    public function test_g_rejecting_a_registration_releases_the_slot_again(): void
    {
        [$vehicle, $type, $config] = $this->makeVehicleQuota(8);

        $registration = $this->reserve('novo', $vehicle->id, $type->id, '2026-10-01', '2026-10-05');

        $this->assertSame(7, $this->availableAt($config, '2026-10-01', '2026-10-05'));

        $registration->status = RegistrationStatus::Reprovado;
        $registration->save();

        $this->assertSame(8, $this->availableAt($config, '2026-10-01', '2026-10-05'));
    }

    public function test_g_there_is_still_no_expired_or_cancelled_status(): void
    {
        $values = array_map(static fn (RegistrationStatus $status) => $status->value, RegistrationStatus::cases());

        $this->assertSame(['novo', 'em_analise', 'aprovado', 'reprovado'], $values);
    }

    public function test_h_concurrent_creation_revalidates_under_lock(): void
    {
        [$vehicle, $type, $config] = $this->makeVehicleQuota(8);

        for ($i = 0; $i < 8; $i++) {
            $this->reserve('novo', $vehicle->id, $type->id, '2026-10-01', '2026-10-05');
        }

        // Revalidação sequencial dentro do lock, exatamente como um segundo
        // request encontraria o estado após o primeiro commitar.
        $this->expectException(QuotaUnavailableException::class);

        try {
            $this->availability()->assertCanReserve(
                $config->vehicle_id,
                $config->quota_type_id,
                Carbon::parse('2026-10-01'),
                Carbon::parse('2026-10-05'),
                lock: true,
            );
        } finally {
            $this->assertSame(8, ClientRegistration::query()->count());
        }
    }

    public function test_h_reverting_a_rejected_registration_is_revalidated(): void
    {
        [$vehicle, $type] = $this->makeVehicleQuota(8);

        $start = now('America/Sao_Paulo')->addDays(10)->toDateString();
        $end = now('America/Sao_Paulo')->addDays(11)->toDateString();

        for ($i = 0; $i < 8; $i++) {
            $this->reserve('aprovado', $vehicle->id, $type->id, $start, $end);
        }

        $rejected = $this->reserve('reprovado', $vehicle->id, $type->id, $start, $end);

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.registrations.status', $rejected), ['status' => 'aprovado'])
            ->assertSessionHasErrors('status');

        $this->assertSame(RegistrationStatus::Reprovado, $rejected->refresh()->status);
    }

    public function test_h_transition_between_consuming_statuses_does_not_revalidate(): void
    {
        [$vehicle, $type] = $this->makeVehicleQuota(8);

        $start = now('America/Sao_Paulo')->addDays(10)->toDateString();
        $end = now('America/Sao_Paulo')->addDays(11)->toDateString();

        $registration = $this->reserve('aprovado', $vehicle->id, $type->id, $start, $end);

        for ($i = 0; $i < 7; $i++) {
            $this->reserve('aprovado', $vehicle->id, $type->id, $start, $end);
        }

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.registrations.status', $registration), ['status' => 'em_analise'])
            ->assertSessionHasNoErrors();

        $this->assertSame(RegistrationStatus::EmAnalise, $registration->refresh()->status);
    }

    public function test_i_different_quota_types_share_the_vehicle_only_when_exclusive(): void
    {
        $vehicle = Vehicle::query()->create(['model' => 'Polo 2026', 'plate' => 'PLACA-I', 'active' => true]);

        $fs = QuotaType::query()->create(['code' => 'FS', 'name' => 'Fim de semana', 'days' => 2, 'active' => true]);
        $s = QuotaType::query()->create(['code' => 'S', 'name' => 'Semanal', 'days' => 2, 'active' => true]);

        $fsConfig = VehicleQuotaConfiguration::query()->create(['vehicle_id' => $vehicle->id, 'quota_type_id' => $fs->id, 'quantity' => 8, 'active' => true]);
        $sConfig = VehicleQuotaConfiguration::query()->create(['vehicle_id' => $vehicle->id, 'quota_type_id' => $s->id, 'quantity' => 8, 'active' => true]);

        $this->reserve('aprovado', $vehicle->id, $fs->id, '2026-10-01', '2026-10-02');

        // Padrão: a disponibilidade é por veículo + tipo de cota.
        $this->assertSame(7, $this->availableAt($fsConfig, '2026-10-01', '2026-10-02'));
        $this->assertSame(8, $this->availableAt($sConfig, '2026-10-01', '2026-10-02'));

        // Exclusivo: qualquer reserva do veículo ocupa a vaga em qualquer tipo.
        config()->set('quotas.vehicle_exclusive', true);

        $this->assertSame(7, $this->availableAt($fsConfig, '2026-10-01', '2026-10-02'));
        $this->assertSame(7, $this->availableAt($sConfig, '2026-10-01', '2026-10-02'));
    }

    public function test_j_different_vehicles_do_not_interfere(): void
    {
        $type = QuotaType::query()->create(['code' => 'FS', 'name' => 'Fim de semana', 'days' => 2, 'active' => true]);

        $vehicleA = Vehicle::query()->create(['model' => 'Veiculo A', 'plate' => 'PLACA-A', 'active' => true]);
        $vehicleB = Vehicle::query()->create(['model' => 'Veiculo B', 'plate' => 'PLACA-B', 'active' => true]);

        $configA = VehicleQuotaConfiguration::query()->create(['vehicle_id' => $vehicleA->id, 'quota_type_id' => $type->id, 'quantity' => 8, 'active' => true]);
        $configB = VehicleQuotaConfiguration::query()->create(['vehicle_id' => $vehicleB->id, 'quota_type_id' => $type->id, 'quantity' => 8, 'active' => true]);

        $this->reserve('aprovado', $vehicleA->id, $type->id, '2026-10-01', '2026-10-02');

        $this->assertSame(7, $this->availableAt($configA, '2026-10-01', '2026-10-02'));
        $this->assertSame(8, $this->availableAt($configB, '2026-10-01', '2026-10-02'));
    }

    public function test_k_duration_mode_exact_max_and_min(): void
    {
        [, $type] = $this->makeVehicleQuota(8, 'FS', 2);

        $day1 = Carbon::parse('2026-10-01');
        $day2 = Carbon::parse('2026-10-02');
        $day3 = Carbon::parse('2026-10-03');
        $day4 = Carbon::parse('2026-10-04');

        config()->set('quotas.duration_mode', 'exact');
        $this->assertTrue($this->availability()->isPeriodValidForType($type, $day1, $day2));
        $this->assertFalse($this->availability()->isPeriodValidForType($type, $day1, $day3));

        config()->set('quotas.duration_mode', 'max');
        $this->assertTrue($this->availability()->isPeriodValidForType($type, $day1, $day2));
        $this->assertTrue($this->availability()->isPeriodValidForType($type, $day1, $day1));
        $this->assertFalse($this->availability()->isPeriodValidForType($type, $day1, $day4));

        config()->set('quotas.duration_mode', 'min');
        $this->assertFalse($this->availability()->isPeriodValidForType($type, $day1, $day1));
        $this->assertTrue($this->availability()->isPeriodValidForType($type, $day1, $day2));
        $this->assertTrue($this->availability()->isPeriodValidForType($type, $day1, $day3));
    }

    public function test_k_store_rejects_period_incompatible_with_duration_mode(): void
    {
        [$vehicle, $type] = $this->makeVehicleQuota(8, 'FS', 2);

        $this->seedContractTemplate();

        $start = now('America/Sao_Paulo')->toDateString();
        $end = now('America/Sao_Paulo')->addDays(4)->toDateString();

        $payload = $this->validPayload();
        $payload['vehicle_id'] = (string) $vehicle->id;
        $payload['quota_type_id'] = (string) $type->id;
        $payload['start_date'] = $start;
        $payload['end_date'] = $end;

        $this->post('/cadastro', $payload)->assertSessionHasErrors('end_date');
        $this->assertDatabaseCount('client_registrations', 0);
    }

    public function test_k_store_accepts_shorter_period_under_max_mode(): void
    {
        [$vehicle, $type] = $this->makeVehicleQuota(8, 'FS', 7);

        config()->set('quotas.duration_mode', 'max');

        $this->seedContractTemplate();

        $start = now('America/Sao_Paulo')->toDateString();
        $end = now('America/Sao_Paulo')->addDay()->toDateString();

        $payload = $this->validPayload();
        $payload['vehicle_id'] = (string) $vehicle->id;
        $payload['quota_type_id'] = (string) $type->id;
        $payload['start_date'] = $start;
        $payload['end_date'] = $end;

        $this->post('/cadastro', $payload)->assertRedirect(route('client-registrations.success'));
        $this->assertDatabaseCount('client_registrations', 1);
    }

    public function test_l_availability_endpoint_reports_availability_and_duration(): void
    {
        [$vehicle, $type] = $this->makeVehicleQuota(8, 'FS', 2);

        $this->reserve('aprovado', $vehicle->id, $type->id, '2026-10-01', '2026-10-02');

        $this->getJson(route('client-registrations.quota-availability', [
            'vehicle_id' => $vehicle->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-02',
        ]))
            ->assertOk()
            ->assertJsonPath('quotas.0.quota_type_id', $type->id)
            ->assertJsonPath('quotas.0.quantity', 8)
            ->assertJsonPath('quotas.0.available', 7)
            ->assertJsonPath('quotas.0.valid_duration', true);

        $this->getJson(route('client-registrations.quota-availability', [
            'vehicle_id' => $vehicle->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-05',
        ]))
            ->assertOk()
            ->assertJsonPath('quotas.0.valid_duration', false);
    }

    public function test_l_availability_endpoint_validates_input(): void
    {
        $this->getJson(route('client-registrations.quota-availability', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-02',
        ]))->assertStatus(422)->assertJsonValidationErrors('vehicle_id');

        [$vehicle] = $this->makeVehicleQuota(8);

        $this->getJson(route('client-registrations.quota-availability', [
            'vehicle_id' => $vehicle->id,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-01',
        ]))->assertStatus(422)->assertJsonValidationErrors('end_date');
    }

    public function test_m_quota_quantity_cannot_be_reduced_below_peak_reservations(): void
    {
        [$vehicle, , $config] = $this->makeVehicleQuota(8);

        for ($i = 0; $i < 8; $i++) {
            $this->reserve('aprovado', $config->vehicle_id, $config->quota_type_id, '2026-10-01', '2026-10-05');
        }

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.vehicles.update-quota', ['vehicle' => $vehicle, 'configuration' => $config]), ['quantity' => 4])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(8, $config->refresh()->quantity);
    }

    public function test_n_removing_configuration_with_active_reservations_deactivates_it(): void
    {
        [$vehicle, , $config] = $this->makeVehicleQuota(8);

        $this->reserve('aprovado', $config->vehicle_id, $config->quota_type_id, now('America/Sao_Paulo')->toDateString(), now('America/Sao_Paulo')->addMonth()->toDateString());

        $this->actingAs(User::factory()->create())
            ->delete(route('admin.vehicles.remove-quota', ['vehicle' => $vehicle, 'configuration' => $config]))
            ->assertRedirect();

        $this->assertDatabaseHas('vehicle_quota_configurations', ['id' => $config->id, 'active' => false]);
        $this->assertSame(1, ClientRegistration::query()->where('vehicle_id', $vehicle->id)->count());
    }

    public function test_o_weekly_reservations_keep_seven_available_in_each_window(): void
    {
        [$vehicle, $type, $config] = $this->makeVehicleQuota(8);

        $base = Carbon::parse('2026-10-01');

        for ($i = 0; $i < 8; $i++) {
            $start = $base->copy()->addDays($i * 10);
            $this->reserve('aprovado', $vehicle->id, $type->id, $start->toDateString(), $start->copy()->addDay()->toDateString());
        }

        for ($i = 0; $i < 8; $i++) {
            $start = $base->copy()->addDays($i * 10);
            $this->assertSame(7, $this->availableAt($config, $start->toDateString(), $start->copy()->addDay()->toDateString()));
        }
    }
}
