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

    public function test_admin_index_lists_registrations_sharing_the_same_cpf(): void
    {
        $first = ClientRegistration::factory()->create(['full_name' => 'Cliente Duplicado Um', 'cpf' => '52998224725']);
        $second = ClientRegistration::factory()->create(['full_name' => 'Cliente Duplicado Dois', 'cpf' => '52998224725']);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.index'))
            ->assertOk()
            ->assertSee('Cliente Duplicado Um')
            ->assertSee('Cliente Duplicado Dois');

        // Cada registro mantém a própria URL administrativa (rota por UUID).
        $this->assertNotSame($first->uuid, $second->uuid);
        $this->get(route('admin.registrations.show', $first))->assertOk()->assertSee('Cliente Duplicado Um');
        $this->get(route('admin.registrations.show', $second))->assertOk()->assertSee('Cliente Duplicado Dois');
    }

    public function test_admin_can_filter_registrations_by_status(): void
    {
        $registration = ClientRegistration::factory()->create();
        $registration->status = RegistrationStatus::Aprovado;
        $registration->save();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.index', ['status' => 'aprovado']))
            ->assertOk()
            ->assertSee('Aprovado');
    }

    public function test_admin_can_filter_registrations_by_date_from(): void
    {
        $this->actingAs(User::factory()->create());

        $old = ClientRegistration::factory()->create(['full_name' => 'Cliente Antigo']);
        $old->created_at = now()->subDays(10);
        $old->save();

        $recent = ClientRegistration::factory()->create(['full_name' => 'Cliente Recente']);
        $recent->created_at = now()->subDay();
        $recent->save();

        $this->get(route('admin.registrations.index', ['date_from' => now()->subDays(5)->toDateString()]))
            ->assertOk()
            ->assertSee('Cliente Recente')
            ->assertDontSee('Cliente Antigo');
    }

    public function test_admin_can_filter_registrations_by_name_with_partial_match(): void
    {
        $this->actingAs(User::factory()->create());

        ClientRegistration::factory()->create(['full_name' => 'João da Silva']);
        ClientRegistration::factory()->create(['full_name' => 'Maria Souza']);

        $statusFilter = null;
        $dateFrom = null;
        $name = 'João';
        $this->get(route('admin.registrations.index', ['name' => 'João']))
            ->assertOk()
            ->assertSee('João da Silva')
            ->assertDontSee('Maria Souza');
    }

    public function test_admin_can_combine_all_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $matching = ClientRegistration::factory()->create(['full_name' => 'João da Silva']);
        $matching->status = RegistrationStatus::EmAnalise;
        $matching->created_at = now();
        $matching->save();

        $otherStatus = ClientRegistration::factory()->create(['full_name' => 'João Pedro']);
        $otherStatus->status = RegistrationStatus::Aprovado;
        $otherStatus->created_at = now();
        $otherStatus->save();

        $otherDate = ClientRegistration::factory()->create(['full_name' => 'Maria e João']);
        $otherDate->status = RegistrationStatus::EmAnalise;
        $otherDate->created_at = now()->subDays(10);
        $otherDate->save();

        $this->get(route('admin.registrations.index', [
            'status' => 'em_analise',
            'date_from' => now()->subDays(5)->toDateString(),
            'name' => 'João',
        ]))
            ->assertOk()
            ->assertSee('João da Silva')
            ->assertDontSee('João Pedro')
            ->assertDontSee('Maria e João');
    }

    public function test_invalid_date_from_is_ignored_without_errors(): void
    {
        $this->actingAs(User::factory()->create());

        ClientRegistration::factory()->create(['full_name' => 'Cliente Qualquer']);

        $this->get(route('admin.registrations.index', ['date_from' => 'nao-e-uma-data']))
            ->assertOk()
            ->assertSee('Cliente Qualquer');
    }

    public function test_name_filter_with_no_matches_shows_empty_state(): void
    {
        $this->actingAs(User::factory()->create());

        ClientRegistration::factory()->create(['full_name' => 'Cliente Real']);

        $this->get(route('admin.registrations.index', ['name' => 'Inexistente']))
            ->assertOk()
            ->assertSee('Nenhum cadastro encontrado para os filtros aplicados.')
            ->assertDontSee('Cliente Real');
    }

    public function test_date_filter_with_no_matches_shows_empty_state(): void
    {
        $this->actingAs(User::factory()->create());

        $registration = ClientRegistration::factory()->create(['full_name' => 'Cliente Antigo']);
        $registration->created_at = now()->subDays(30);
        $registration->save();

        $this->get(route('admin.registrations.index', ['date_from' => now()->addDay()->toDateString()]))
            ->assertOk()
            ->assertSee('Nenhum cadastro encontrado para os filtros aplicados.')
            ->assertDontSee('Cliente Antigo');
    }

    public function test_status_filter_with_no_matches_shows_empty_state(): void
    {
        $this->actingAs(User::factory()->create());

        ClientRegistration::factory()->create();

        $this->get(route('admin.registrations.index', ['status' => 'reprovado']))
            ->assertOk()
            ->assertSee('Nenhum cadastro encontrado para os filtros aplicados.');
    }

    public function test_clear_filters_link_removes_all_active_filters(): void
    {
        $this->actingAs(User::factory()->create());

        ClientRegistration::factory()->create(['full_name' => 'João da Silva']);

        $this->get(route('admin.registrations.index', ['status' => 'novo', 'name' => 'João']))
            ->assertOk()
            ->assertSee('Limpar');
    }

    public function test_pagination_preserves_active_filters_in_query_string(): void
    {
        $this->actingAs(User::factory()->create());

        ClientRegistration::factory()->count(25)->create(['full_name' => 'João da Silva'])
            ->each(function (ClientRegistration $registration): void {
                $registration->status = RegistrationStatus::Aprovado;
                $registration->created_at = now()->subDay();
                $registration->save();
            });

        $dateFrom = now()->subDays(3)->toDateString();

        $html = $this->get(route('admin.registrations.index', [
            'status' => 'aprovado',
            'date_from' => $dateFrom,
            'name' => 'João',
            'page' => 2,
        ]))
            ->assertOk()
            ->assertSee('João da Silva')
            ->getContent();

        // Filtros ativos preservados na querystring dos links de paginação.
        $this->assertStringContainsString('status=aprovado', $html);
        $this->assertStringContainsString('date_from='.$dateFrom, $html);
        $this->assertStringContainsString('name='.rawurlencode('João'), $html);
        $this->assertStringContainsString('&amp;page=1', $html);
        $this->assertStringContainsString('&amp;page=3', $html);
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

    public function test_registrations_list_consumes_status_updated_trigger(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.index', ['status_updated' => 1]))
            ->assertRedirect(route('admin.registrations.index'))
            ->assertSessionHas('success', 'Status atualizado com sucesso.');

        $this->get(route('admin.registrations.index'))
            ->assertOk()
            ->assertSee('Status atualizado com sucesso.', false)
            ->assertDontSee('status_updated', false);
    }

    public function test_status_updated_trigger_is_not_propagated_to_filters(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.index', ['status_updated' => 1, 'status' => 'novo']))
            ->assertRedirect(route('admin.registrations.index', ['status' => 'novo']))
            ->assertSessionMissing('status_updated_redirect');
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
