<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\ClientRegistration;
use App\Models\QuotaType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminRegistrationStatementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_statement_with_registration_quota_and_vehicle_data(): void
    {
        [$registration, $vehicle, $quota] = $this->createRegistrationWithQuota();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.statement', $registration))
            ->assertOk()
            ->assertSee('Extrato do cliente')
            ->assertSee($registration->full_name)
            ->assertSee($registration->maskedCpf())
            ->assertSee($quota->code)
            ->assertSee($quota->name)
            ->assertSee($vehicle->model)
            ->assertSee($vehicle->plate);
    }

    public function test_statement_shows_the_days_registered_for_the_quota_type(): void
    {
        [$weekly] = $this->createRegistrationWithQuota(days: 9, start: '2026-09-21');
        [$short] = $this->createRegistrationWithQuota(days: 4, start: '2026-09-21');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.statement', $weekly))
            ->assertOk()
            ->assertSee('Dias da cota')
            ->assertSee('9 dias')
            ->assertDontSee('7 dias')
            ->assertViewHas('statement', fn (array $statement): bool => $statement['total'] === 9);

        $this->get(route('admin.registrations.statement', $short))
            ->assertOk()
            ->assertSee('4 dias')
            ->assertViewHas('statement', fn (array $statement): bool => $statement['total'] === 4);
    }

    public function test_statement_days_come_from_the_quota_type_and_not_from_the_registration_snapshot(): void
    {
        [$registration] = $this->createRegistrationWithQuota(days: 5, start: '2026-09-21');

        // `quota_days` é apenas o retrato do momento da venda: o extrato
        // reflete a cota cadastrada.
        $registration->update(['quota_days' => 1]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.statement', $registration))
            ->assertOk()
            ->assertViewHas('statement', fn (array $statement): bool => $statement['total'] === 5);
    }

    public function test_statement_lists_the_days_of_the_selected_month_with_weekday_names(): void
    {
        [$registration] = $this->createRegistrationWithQuota(days: 7, start: '2026-09-21');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.statement', $registration))
            ->assertOk()
            ->assertSee('SETEMBRO/2026')
            ->assertSee('21/09')
            ->assertSee('Segunda-feira')
            ->assertSee('22/09')
            ->assertSee('Terça-feira')
            ->assertSee('23/09')
            ->assertSee('Quarta-feira')
            ->assertSee('24/09')
            ->assertSee('Quinta-feira')
            ->assertSee('25/09')
            ->assertSee('Sexta-feira')
            ->assertSee('26/09')
            ->assertSee('Sábado')
            ->assertSee('27/09')
            ->assertSee('Domingo')
            // 28/09 está fora do período da cota.
            ->assertDontSee('28/09')
            ->assertSee('Total:')
            ->assertSee('7 dias');
    }

    public function test_statement_groups_days_by_week(): void
    {
        // Quinta a domingo na primeira semana; segunda a quarta na segunda.
        [$registration] = $this->createRegistrationWithQuota(days: 7, start: '2026-09-17');

        $html = $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.statement', $registration))
            ->assertOk()
            ->assertSee('1ª semana')
            ->assertSee('2ª semana')
            ->getContent();

        $this->assertSame(2, substr_count($html, 'ª semana'));
    }

    public function test_statement_month_selection_shows_another_month_without_changing_the_registration(): void
    {
        [$registration, , $quota] = $this->createRegistrationWithQuota(days: 7, start: '2026-09-21');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.statement', [$registration, 'mes' => '2026-10']))
            ->assertOk()
            ->assertSee('OUTUBRO/2026')
            ->assertDontSee('SETEMBRO/2026')
            ->assertDontSee('Segunda-feira')
            ->assertSee('Não há dias da cota em OUTUBRO/2026.');

        $this->assertDatabaseHas('client_registrations', [
            'id' => $registration->id,
            'start_date' => '2026-09-21',
            'end_date' => '2026-09-27',
            'quota_type_id' => $quota->id,
        ]);
    }

    public function test_statement_falls_back_to_the_quota_period_month_for_an_invalid_month(): void
    {
        [$registration] = $this->createRegistrationWithQuota(days: 7, start: '2026-09-21');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.statement', [$registration, 'mes' => 'setembro']))
            ->assertOk()
            ->assertSee('SETEMBRO/2026')
            ->assertSee('21/09');
    }

    public function test_statement_without_quota_informs_the_missing_data(): void
    {
        $registration = ClientRegistration::factory()->create([
            'status' => RegistrationStatus::Aprovado,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.statement', $registration))
            ->assertOk()
            ->assertSee($registration->full_name)
            ->assertSee('Não informado')
            ->assertSee('Este cadastro não possui período de cota registrado.');
    }

    public function test_statement_button_is_hidden_on_the_details_page_while_the_route_still_works(): void
    {
        [$registration] = $this->createRegistrationWithQuota();

        // O módulo de Extratos está implementado, mas oculto: o botão não
        // aparece no detalhe do cadastro.
        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.show', $registration))
            ->assertOk()
            ->assertDontSee('Ver extrato')
            ->assertDontSee(route('admin.registrations.statement', $registration), false);

        // A rota permanece disponível para consulta direta/validação interna.
        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.statement', $registration))
            ->assertOk();
    }

    public function test_statement_action_is_hidden_for_registrations_that_are_not_approved(): void
    {
        $registration = ClientRegistration::factory()->create([
            'status' => RegistrationStatus::EmAnalise,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.show', $registration))
            ->assertOk()
            ->assertDontSee('Ver extrato')
            ->assertDontSee(route('admin.registrations.statement', $registration), false);
    }

    public function test_statement_route_is_forbidden_for_registrations_that_are_not_approved(): void
    {
        $registration = ClientRegistration::factory()->create([
            'status' => RegistrationStatus::Novo,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.statement', $registration))
            ->assertForbidden();
    }

    public function test_guest_cannot_view_statement(): void
    {
        [$registration] = $this->createRegistrationWithQuota();

        $this->get(route('admin.registrations.statement', $registration))
            ->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_cannot_view_statement(): void
    {
        [$registration] = $this->createRegistrationWithQuota();

        $this->actingAs(User::factory()->create(['role' => 'operator']))
            ->get(route('admin.registrations.statement', $registration))
            ->assertForbidden();
    }

    public function test_unknown_uuid_returns_404_for_the_statement_route(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/cadastros/'.Str::uuid().'/extrato')
            ->assertNotFound();
    }

    /**
     * Cadastro com veículo e cota, período compatível com a duração da cota
     * (mesma regra `exact` da venda). O extrato existe apenas para cadastros
     * aprovados, então o status também é informado.
     *
     * @return array{0: ClientRegistration, 1: Vehicle, 2: QuotaType}
     */
    private function createRegistrationWithQuota(int $days = 7, string $start = '2026-09-21'): array
    {
        $quotaType = QuotaType::query()->create([
            'code' => 'S'.$days,
            'name' => 'Cota de '.$days.' dias',
            'days' => $days,
            'active' => true,
        ]);

        $vehicle = Vehicle::query()->create([
            'model' => 'Chevrolet Onix',
            'plate' => 'EDX2'.$days.'E7',
            'active' => true,
        ]);

        $startDate = Carbon::parse($start)->startOfDay();

        $registration = ClientRegistration::factory()->create([
            'full_name' => 'João da Silva',
            'cpf' => '52998224725',
            'status' => RegistrationStatus::Aprovado,
            'vehicle_id' => $vehicle->id,
            'quota_type_id' => $quotaType->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $startDate->copy()->addDays($days - 1)->toDateString(),
            'quota_days' => $days,
        ]);

        return [$registration, $vehicle, $quotaType];
    }
}
