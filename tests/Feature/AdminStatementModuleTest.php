<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\ClientRegistration;
use App\Models\QuotaType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Módulo de Extratos: listagem dos extratos dos cadastros aprovados,
 * ordenação, filtros e navegação de anterior/próximo limitada ao histórico
 * do cliente visualizado.
 */
class AdminStatementModuleTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_admin_menu_exposes_the_extratos_module(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.statements.index'))
            ->assertOk()
            ->assertSee('Extratos')
            ->assertSee(route('admin.statements.index'), false);
    }

    public function test_extratos_menu_item_stays_active_on_the_module_and_on_the_statement(): void
    {
        $registration = $this->createStatement();
        $activeMenuItem = '/href="[^"]*\/admin\/extratos"[^>]*aria-current="page"/s';

        $this->assertMatchesRegularExpression(
            $activeMenuItem,
            $this->actingAs(User::factory()->create())
                ->get(route('admin.statements.index'))
                ->assertOk()
                ->getContent(),
        );

        $this->assertMatchesRegularExpression(
            $activeMenuItem,
            $this->actingAs(User::factory()->create())
                ->get(route('admin.registrations.statement', $registration))
                ->assertOk()
                ->getContent(),
        );
    }

    public function test_approved_registration_is_listed_with_the_existing_registration_data(): void
    {
        $registration = $this->createStatement(days: 9, start: '2026-09-21');
        $quota = $registration->quotaType;
        $vehicle = $registration->vehicle;

        $this->actingAs(User::factory()->create())
            ->get(route('admin.statements.index'))
            ->assertOk()
            ->assertSee($registration->full_name)
            ->assertSee($registration->maskedCpf())
            ->assertSee($quota->code)
            ->assertSee($quota->name)
            ->assertSee($vehicle->model)
            ->assertSee($vehicle->plate)
            ->assertSee('9 dias')
            // Data de referência usada na ordenação.
            ->assertSee('21/09/2026')
            ->assertSee('Início da cota')
            ->assertSee(route('admin.registrations.statement', $registration), false);
    }

    public function test_statement_days_come_from_the_quota_type_in_the_listing(): void
    {
        $this->createStatement(days: 4, code: 'Q4');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.statements.index'))
            ->assertOk()
            ->assertSee('4 dias')
            ->assertDontSee('7 dias');
    }

    public function test_only_approved_registrations_are_listed(): void
    {
        $approved = $this->createStatement(status: RegistrationStatus::Aprovado, name: 'Cliente Aprovado');
        $this->createStatement(status: RegistrationStatus::Novo, name: 'Cliente Novo');
        $this->createStatement(status: RegistrationStatus::EmAnalise, name: 'Cliente Em Analise');
        $this->createStatement(status: RegistrationStatus::Reprovado, name: 'Cliente Reprovado');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.statements.index'))
            ->assertOk()
            ->assertSee($approved->full_name)
            ->assertDontSee('Cliente Novo')
            ->assertDontSee('Cliente Em Analise')
            ->assertDontSee('Cliente Reprovado')
            ->assertViewHas('statements', fn ($statements) => $statements->total() === 1);
    }

    public function test_listing_is_empty_when_there_is_no_approved_registration(): void
    {
        $this->createStatement(status: RegistrationStatus::Novo);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.statements.index'))
            ->assertOk()
            ->assertSee('Nenhum cadastro aprovado com extrato disponível.')
            ->assertViewHas('statements', fn ($statements) => $statements->total() === 0);
    }

    public function test_listing_orders_statements_from_the_most_recent_to_the_oldest(): void
    {
        $this->createStatement(name: 'Extrato Antigo', cpf: '11111111111', start: '2026-07-01');
        $this->createStatement(name: 'Extrato Intermediario', cpf: '22222222222', start: '2026-08-01');
        $this->createStatement(name: 'Extrato Recente', cpf: '33333333333', start: '2026-09-01');

        $html = $this->actingAs(User::factory()->create())
            ->get(route('admin.statements.index'))
            ->assertOk()
            ->getContent();

        $this->assertLessThan(strpos($html, 'Extrato Intermediario'), strpos($html, 'Extrato Recente'));
        $this->assertLessThan(strpos($html, 'Extrato Antigo'), strpos($html, 'Extrato Intermediario'));
    }

    public function test_listing_can_be_filtered_by_name_or_cpf(): void
    {
        $this->createStatement(name: 'Maria Souza', cpf: '52998224725');
        $this->createStatement(name: 'Carlos Lima', cpf: '11144477735');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.statements.index', ['q' => 'Maria']))
            ->assertOk()
            ->assertSee('Maria Souza')
            ->assertDontSee('Carlos Lima');

        $this->get(route('admin.statements.index', ['q' => '111.444.777.35']))
            ->assertOk()
            ->assertSee('Carlos Lima')
            ->assertDontSee('Maria Souza');
    }

    public function test_guest_cannot_list_statements(): void
    {
        $this->get(route('admin.statements.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_cannot_list_statements(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'operator']))
            ->get(route('admin.statements.index'))
            ->assertForbidden();
    }

    public function test_most_recent_statement_has_no_next_navigation(): void
    {
        $this->createStatement(start: '2026-08-01');
        $recent = $this->createStatement(start: '2026-09-01');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.statement', $recent))
            ->assertOk()
            ->assertSee('Extrato 1 de 2')
            ->assertSee('data-statement-nav="previous"', false)
            ->assertDontSee('data-statement-nav="next"', false);
    }

    public function test_oldest_statement_has_no_previous_navigation(): void
    {
        $oldest = $this->createStatement(start: '2026-08-01');
        $this->createStatement(start: '2026-09-01');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.statement', $oldest))
            ->assertOk()
            ->assertSee('Extrato 2 de 2')
            ->assertSee('data-statement-nav="next"', false)
            ->assertDontSee('data-statement-nav="previous"', false);
    }

    public function test_navigation_stays_within_the_statements_of_the_same_client(): void
    {
        $older = $this->createStatement(cpf: '52998224725', start: '2026-08-01');
        $current = $this->createStatement(cpf: '52998224725', start: '2026-09-01');
        $other = $this->createStatement(cpf: '11144477735', name: 'Carlos Lima', start: '2026-09-15');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.statement', $current))
            ->assertOk()
            // Só os extratos deste cliente entram no histórico: 2 no total.
            ->assertSee('Extrato 1 de 2')
            ->assertSee('href="'.route('admin.registrations.statement', $older).'"', false)
            ->assertSee('data-statement-nav="previous"', false)
            // Cliente diferente: nunca aparece como opção de navegação.
            ->assertDontSee(route('admin.registrations.statement', $other), false)
            ->assertDontSee('Carlos Lima');
    }

    public function test_navigation_from_previous_statement_can_return_to_the_most_recent_one(): void
    {
        $older = $this->createStatement(cpf: '52998224725', start: '2026-08-01');
        $recent = $this->createStatement(cpf: '52998224725', start: '2026-09-01');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.statement', $older))
            ->assertOk()
            ->assertSee('Extrato 2 de 2')
            ->assertSee('href="'.route('admin.registrations.statement', $recent).'"', false)
            ->assertDontSee('data-statement-nav="previous"', false);
    }

    public function test_statement_without_history_has_no_navigation(): void
    {
        $only = $this->createStatement();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.statement', $only))
            ->assertOk()
            ->assertSee('Extrato 1 de 1')
            ->assertDontSee('data-statement-nav="previous"', false)
            ->assertDontSee('data-statement-nav="next"', false);
    }

    public function test_registrations_not_approved_are_not_part_of_the_client_history(): void
    {
        $approved = $this->createStatement(cpf: '52998224725', start: '2026-08-01');
        $pending = $this->createStatement(
            cpf: '52998224725',
            start: '2026-09-01',
            status: RegistrationStatus::EmAnalise,
        );

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.statement', $approved))
            ->assertOk()
            ->assertSee('Extrato 1 de 1')
            ->assertDontSee('data-statement-nav="previous"', false)
            ->assertDontSee('data-statement-nav="next"', false)
            ->assertDontSee(route('admin.registrations.statement', $pending), false);
    }

    /**
     * Cadastro usado como extrato. O status é informado para permitir também
     * os cenários de cadastro não aprovado.
     */
    private function createStatement(
        string $name = 'João da Silva',
        string $cpf = '52998224725',
        int $days = 7,
        string $start = '2026-09-21',
        RegistrationStatus $status = RegistrationStatus::Aprovado,
        ?string $code = null,
    ): ClientRegistration {
        $this->sequence++;

        $quotaType = QuotaType::query()->create([
            'code' => $code ?? 'S'.$days.'-'.$this->sequence,
            'name' => 'Cota de '.$days.' dias',
            'days' => $days,
            'active' => true,
        ]);

        $vehicle = Vehicle::query()->create([
            'model' => 'Chevrolet Onix',
            'plate' => 'EDX'.$this->sequence.'E7',
            'active' => true,
        ]);

        $startDate = Carbon::parse($start)->startOfDay();

        return ClientRegistration::factory()->create([
            'full_name' => $name,
            'cpf' => $cpf,
            'status' => $status,
            'vehicle_id' => $vehicle->id,
            'quota_type_id' => $quotaType->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $startDate->copy()->addDays($days - 1)->toDateString(),
            'quota_days' => $days,
        ]);
    }
}
