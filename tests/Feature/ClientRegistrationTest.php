<?php

namespace Tests\Feature;

use App\Models\ClientRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private array $baseData = [
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
        'veracity_declaration_accepted' => '1',
        'privacy_policy_accepted' => '1',
    ];

    public function test_public_form_page_renders(): void
    {
        $this->get('/cadastro')
            ->assertOk()
            ->assertSee('Cadastro de Cliente')
            ->assertSee('Dados pessoais')
            ->assertSee('Enviar cadastro');
    }

    public function test_client_can_submit_registration_with_documents(): void
    {
        Storage::fake('local');

        $response = $this->post('/cadastro', [
            ...$this->baseData,
            'cnh_front_file' => UploadedFile::fake()->image('cnh-front.jpg', 600, 400),
            'cnh_back_file' => UploadedFile::fake()->image('cnh-back.jpg', 600, 400),
            'proof_of_residence_file' => UploadedFile::fake()->image('comprovante.jpg', 600, 400),
            'selfie_file' => UploadedFile::fake()->image('selfie.jpg', 600, 400),
        ]);

        $response
            ->assertRedirect(route('client-registrations.success'))
            ->assertSessionHas('registered_cpf');

        $this->assertDatabaseHas('client_registrations', [
            'cpf' => '39053344705',
            'status' => 'novo',
            'facial_status' => 'pending',
            'veracity_declaration_accepted' => true,
            'privacy_policy_accepted' => true,
        ]);

        $registration = ClientRegistration::query()->firstOrFail();

        $this->assertNotNull($registration->cnh_front_path);
        $this->assertNotNull($registration->cnh_back_path);
        $this->assertNotNull($registration->proof_of_residence_path);
        $this->assertNotNull($registration->selfie_path);

        Storage::disk('local')->assertExists($registration->cnh_front_path);
        Storage::disk('local')->assertExists($registration->selfie_path);
    }

    public function test_registration_rejects_invalid_cpf(): void
    {
        $response = $this->post('/cadastro', [
            ...$this->baseData,
            'cpf' => '123.456.789-00',
            'cnh_front_file' => UploadedFile::fake()->image('cnh-front.jpg', 600, 400),
            'cnh_back_file' => UploadedFile::fake()->image('cnh-back.jpg', 600, 400),
            'proof_of_residence_file' => UploadedFile::fake()->image('comprovante.jpg', 600, 400),
            'selfie_file' => UploadedFile::fake()->image('selfie.jpg', 600, 400),
        ]);

        $response
            ->assertSessionHasErrors('cpf')
            ->assertSessionDoesntHaveErrors('full_name');

        $this->assertDatabaseCount('client_registrations', 0);
    }

    public function test_registration_requires_documents_and_acceptances(): void
    {
        unset(
            $this->baseData['cnh_front_file'],
            $this->baseData['veracity_declaration_accepted'],
            $this->baseData['privacy_policy_accepted'],
        );

        $response = $this->post('/cadastro', $this->baseData);

        $response->assertSessionHasErrors([
            'cnh_front_file',
            'veracity_declaration_accepted',
            'privacy_policy_accepted',
        ]);

        $this->assertDatabaseCount('client_registrations', 0);
    }

    public function test_minors_are_not_accepted(): void
    {
        $data = [
            ...$this->baseData,
            'birth_date' => now()->subYears(17)->format('Y-m-d'),
            'cnh_front_file' => UploadedFile::fake()->image('cnh-front.jpg', 600, 400),
            'cnh_back_file' => UploadedFile::fake()->image('cnh-back.jpg', 600, 400),
            'proof_of_residence_file' => UploadedFile::fake()->image('comprovante.jpg', 600, 400),
            'selfie_file' => UploadedFile::fake()->image('selfie.jpg', 600, 400),
        ];

        $this->post('/cadastro', $data)
            ->assertSessionHasErrors('birth_date');

        $this->assertDatabaseCount('client_registrations', 0);
    }

    public function test_documents_are_stored_privately_and_not_publicly_served(): void
    {
        Storage::fake('local');

        $this->post('/cadastro', [
            ...$this->baseData,
            'cnh_front_file' => UploadedFile::fake()->image('cnh-front.jpg', 600, 400),
            'cnh_back_file' => UploadedFile::fake()->image('cnh-back.jpg', 600, 400),
            'proof_of_residence_file' => UploadedFile::fake()->image('comprovante.jpg', 600, 400),
            'selfie_file' => UploadedFile::fake()->image('selfie.jpg', 600, 400),
        ]);

        $registration = ClientRegistration::query()->firstOrFail();

        $this->assertStringStartsWith('cadastros/', $registration->cnh_front_path);

        // Arquivos do disco privado não podem ser acessados diretamente na web.
        $this->get('/storage/'.$registration->cnh_front_path)
            ->assertForbidden();
    }

    public function test_success_page_is_shown_after_registration(): void
    {
        $this->get(route('client-registrations.success'))
            ->assertOk()
            ->assertSee('Cadastro enviado');
    }
}
