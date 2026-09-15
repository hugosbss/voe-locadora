<?php

namespace Tests\Feature;

use App\Mail\NewRegistrationMail;
use App\Models\ClientRegistration;
use App\Models\User;
use App\Services\NewRegistrationNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use RuntimeException;
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
        'contract_signer_name' => 'Maria da Silva Souza',
        'contract_accepted' => '1',
    ];

    /**
     * Prepara o disco privado fake com o modelo do contrato. O template
     * oficial não está versionado; os testes usam um PDF sintético de
     * 12 páginas A4 (mesma estrutura) para validar o fluxo.
     */
    private function seedContractTemplate(): void
    {
        Storage::fake('local');

        $pdf = new \FPDF('P', 'pt', [595.276, 841.89]);

        for ($page = 1; $page <= 12; $page++) {
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 12);
            $pdf->Text(40, 40, 'Contrato sintetico para testes — pagina '.$page);
        }

        Storage::disk('local')->put(config('contracts.template_path'), $pdf->Output('S'));
    }

    /**
     * Gera um payload PNG válido desenhado (traço escuro em fundo branco).
     */
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
     * Payload completo de um envio válido (dados + arquivos + assinatura).
     *
     * @return array<string, mixed>
     */
    private function validPayload(bool $withSignature = true): array
    {
        return [
            ...$this->baseData,
            'cnh_front_file' => UploadedFile::fake()->image('cnh-front.jpg', 600, 400),
            'cnh_back_file' => UploadedFile::fake()->image('cnh-back.jpg', 600, 400),
            'proof_of_residence_file' => UploadedFile::fake()->image('comprovante.jpg', 600, 400),
            'selfie_file' => UploadedFile::fake()->image('selfie.jpg', 600, 400),
            'contract_signature' => $withSignature ? $this->signatureDataUrl() : '',
        ];
    }

    public function test_public_form_page_renders(): void
    {
        $this->get('/cadastro')
            ->assertOk()
            ->assertSee('Cadastro de Cliente')
            ->assertSee('Dados pessoais')
            ->assertSee('Enviar cadastro')
            ->assertSee('Contrato e assinatura')
            ->assertSee('id="signature-canvas"', false)
            ->assertSee('Sua assinatura')
            ->assertSee(route('client-registrations.contract'));
    }

    public function test_public_pages_use_vca_brand_and_have_no_protected_badge(): void
    {
        $html = $this->get('/cadastro')->assertOk()->getContent();

        $this->assertStringContainsString('>VCA</', $html);
        $this->assertStringContainsString('Formulário de cadastro', $html);
        $this->assertStringNotContainsString('Dados protegidos', $html);
        $this->assertStringNotContainsString('Painel da Locadora', $html);
        $this->assertStringNotContainsString('Locadora</span>', $html);
    }

    public function test_selfie_step_only_offers_camera_capture_without_gallery_option(): void
    {
        $html = $this->get('/cadastro')->assertOk()->getContent();

        $this->assertStringContainsString('name="selfie_file"', $html);
        $this->assertStringContainsString('capture="user"', $html);
        $this->assertStringContainsString('Tirar selfie', $html);
        $this->assertStringNotContainsString('Escolher da galeria', $html);
        $this->assertStringNotContainsString('gallery-input sr-only" data-doc="selfie"', $html);

        foreach (['cnh_front', 'cnh_back', 'proof_of_residence'] as $doc) {
            $this->assertStringContainsString('gallery-input sr-only" data-doc="'.$doc.'"', $html);
        }
    }

    public function test_client_can_submit_registration_with_documents(): void
    {
        $this->seedContractTemplate();

        $response = $this->post('/cadastro', $this->validPayload());

        $response
            ->assertRedirect(route('client-registrations.success'))
            ->assertSessionHas('registered_cpf');

        $this->assertDatabaseHas('client_registrations', [
            'cpf' => '39053344705',
            'status' => 'novo',
            'facial_status' => 'pending',
            'veracity_declaration_accepted' => true,
            'privacy_policy_accepted' => true,
            'contract_signed' => true,
            'contract_signer_name' => 'Maria da Silva Souza',
            'contract_signer_ip' => '127.0.0.1',
        ]);

        $registration = ClientRegistration::query()->firstOrFail();

        $this->assertNotNull($registration->cnh_front_path);
        $this->assertNotNull($registration->cnh_back_path);
        $this->assertNotNull($registration->proof_of_residence_path);
        $this->assertNotNull($registration->selfie_path);

        Storage::disk('local')->assertExists($registration->cnh_front_path);
        Storage::disk('local')->assertExists($registration->selfie_path);
        Storage::disk('local')->assertExists($registration->contract_signature_path);
        Storage::disk('local')->assertExists($registration->contract_signed_pdf_path);
    }

    public function test_registration_rejects_invalid_cpf(): void
    {
        $this->seedContractTemplate();

        $response = $this->post('/cadastro', [
            ...$this->validPayload(),
            'cpf' => '123.456.789-00',
        ]);

        $response
            ->assertSessionHasErrors('cpf')
            ->assertSessionDoesntHaveErrors('full_name');

        $this->assertDatabaseCount('client_registrations', 0);
    }

    public function test_registration_requires_documents_and_acceptances(): void
    {
        $payload = $this->validPayload();

        unset(
            $payload['cnh_front_file'],
            $payload['veracity_declaration_accepted'],
            $payload['privacy_policy_accepted'],
        );

        $response = $this->post('/cadastro', $payload);

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
            ...$this->validPayload(),
            'birth_date' => now()->subYears(17)->format('Y-m-d'),
        ];

        $this->post('/cadastro', $data)
            ->assertSessionHasErrors('birth_date');

        $this->assertDatabaseCount('client_registrations', 0);
    }

    public function test_documents_are_stored_privately_and_not_publicly_served(): void
    {
        $this->seedContractTemplate();

        $this->post('/cadastro', $this->validPayload());

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

    public function test_admins_are_notified_by_email_when_registration_is_created(): void
    {
        Mail::fake();
        $this->seedContractTemplate();

        User::factory()->count(2)->create();

        $this->post('/cadastro', $this->validPayload())
            ->assertRedirect(route('client-registrations.success'));

        Mail::assertSent(NewRegistrationMail::class, 2);

        $this->assertDatabaseCount('client_registrations', 1);
    }

    public function test_mail_failure_does_not_lose_the_registration(): void
    {
        $this->seedContractTemplate();

        $this->mock(NewRegistrationNotifier::class, function (MockInterface $mock): void {
            $mock->shouldReceive('notifyAdmins')
                ->once()
                ->andThrow(new RuntimeException('SMTP indisponível'));
        });

        $response = $this->post('/cadastro', $this->validPayload());

        $response->assertRedirect(route('client-registrations.success'));
        $this->assertDatabaseCount('client_registrations', 1);
    }

    public function test_registration_notification_email_omits_sensitive_data(): void
    {
        $registration = ClientRegistration::factory()->create([
            'full_name' => 'Maria da Silva Souza',
        ]);

        $html = (new NewRegistrationMail($registration))->render();

        $this->assertStringContainsString('Novo cadastro recebido', $html);
        $this->assertStringContainsString('Maria da Silva Souza', $html);
        $this->assertStringContainsString('Ver cadastro', $html);
        $this->assertStringContainsString($registration->uuid, $html);
        $this->assertStringNotContainsString($registration->cpf, $html);
        $this->assertStringNotContainsString($registration->cnh_number, $html);
    }

    public function test_registration_notification_email_logo_is_embedded_instead_of_absolute_url(): void
    {
        $registration = ClientRegistration::factory()->create();

        $html = (new NewRegistrationMail($registration))->render();

        $this->assertMatchesRegularExpression('/<img[^>]*src="(cid:|data:)/', $html);
        $this->assertStringNotContainsString('/images/brand/vca-logo.jpeg', $html);
    }
}
