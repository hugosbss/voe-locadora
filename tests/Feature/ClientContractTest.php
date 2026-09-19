<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Models\ClientRegistration;
use App\Models\User;
use App\Services\ContractPdfService;
use App\Services\ContractStorageService;
use App\Services\RetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientContractTest extends TestCase
{
    use RefreshDatabase;

    private array $baseData = [
        // Nome sem acentos: FPDF grava strings ASCII como literais no
        // content stream, o que permite verificar o overlay no teste.
        'full_name' => 'Joao Oliveira Santos',
        'cpf' => '529.982.247-25',
        'birth_date' => '1988-03-22',
        'phone' => '(11) 98765-4321',
        'whatsapp' => '(11) 98765-4321',
        'email' => 'joao@example.com',
        'cep' => '01310-100',
        'address' => 'Avenida Paulista',
        'address_number' => '500',
        'neighborhood' => 'Bela Vista',
        'city' => 'São Paulo',
        'state' => 'SP',
        'cnh_number' => '99887766554',
        'cnh_category' => 'B',
        'cnh_expiry_date' => '2031-01-01',
        'start_date' => '2026-09-19',
        'end_date' => '2026-10-19',
        'veracity_declaration_accepted' => '1',
        'privacy_policy_accepted' => '1',
        'contract_signer_name' => 'Joao Oliveira Santos',
        'contract_accepted' => '1',
    ];

    /**
     * O template oficial não está versionado; os testes usam um PDF
     * sintético de 12 páginas A4 com a mesma estrutura e página de assinatura.
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
     * Gera um payload PNG válido (traço escuro em fundo branco).
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
     * Payload completo de envio válido.
     *
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            ...$this->baseData,
            'cnh_front_file' => UploadedFile::fake()->image('cnh-front.jpg', 600, 400),
            'cnh_back_file' => UploadedFile::fake()->image('cnh-back.jpg', 600, 400),
            'proof_of_residence_file' => UploadedFile::fake()->image('comprovante.jpg', 600, 400),
            'selfie_file' => UploadedFile::fake()->image('selfie.jpg', 600, 400),
            'contract_signature' => $this->signatureDataUrl(),
        ], $overrides);
    }

    /**
     * Cria um cadastro com contrato assinado de verdade (arquivos reais no
     * disco fake), como se tivesse passado pelo fluxo completo.
     */
    private function createSignedRegistration(): ClientRegistration
    {
        $this->seedContractTemplate();

        // Somente dados pessoais (formulário): o factory não conhece o
        // campo de formulário `contract_accepted` (não é coluna).
        $data = $this->baseData;
        unset($data['contract_accepted']);

        $registration = ClientRegistration::factory()->create($data);

        $storage = app(ContractStorageService::class);
        $signaturePath = $storage->storeSignature($this->signatureDataUrl(), $registration->uuid);

        $contractPath = app(ContractPdfService::class)->generate(
            $registration->uuid,
            $signaturePath,
            $registration->full_name,
            $registration->cpf,
            now('America/Sao_Paulo')->format('d/m/Y H:i'),
        );

        // Colunas controladas pelo servidor não são mass-assignable.
        $registration->forceFill([
            'contract_signed' => true,
            'contract_signed_at' => now('America/Sao_Paulo')->setTimezone('UTC'),
            'contract_version' => config('contracts.version'),
            'contract_signed_pdf_path' => $contractPath,
            'contract_signature_path' => $signaturePath,
            'contract_signer_name' => $registration->full_name,
            'contract_signer_ip' => '127.0.0.1',
        ])->save();

        return $registration->fresh();
    }

    /* ---------- Etapa 7 (formulário) ---------- */

    public function test_contract_step_is_rendered_with_canvas_and_accept_checkbox(): void
    {
        $html = $this->get('/cadastro')->assertOk()->getContent();

        $this->assertStringContainsString('Contrato e assinatura', $html);
        $this->assertStringContainsString('id="signature-canvas"', $html);
        $this->assertStringContainsString('name="contract_accepted"', $html);
        $this->assertStringContainsString('name="contract_signer_name"', $html);
        $this->assertStringContainsString('Leia o contrato abaixo. Depois, assine para concluir seu cadastro.', $html);
        $this->assertStringContainsString(route('client-registrations.contract'), $html);
    }

    public function test_contract_template_is_served_for_reading(): void
    {
        $this->seedContractTemplate();

        $this->get(route('client-registrations.contract'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="contrato.pdf"');
    }

    public function test_full_submission_persists_signed_contract_with_server_fields(): void
    {
        $this->seedContractTemplate();

        $this->post('/cadastro', $this->validPayload())
            ->assertRedirect(route('client-registrations.success'));

        $registration = ClientRegistration::query()->firstOrFail();

        $this->assertTrue($registration->contract_signed);
        $this->assertSame('1.0', $registration->contract_version);
        $this->assertSame('Joao Oliveira Santos', $registration->contract_signer_name);
        $this->assertTrue($registration->contract_signed_at?->gte(now('America/Sao_Paulo')->subMinute()));
        $this->assertTrue($registration->contract_signed_at?->lte(now('America/Sao_Paulo')?->addSecond()));
        $this->assertStringStartsWith('contracts/signed/'.$registration->uuid.'/', $registration->contract_signature_path);
        $this->assertSame('contrato-assinado.pdf', basename((string) $registration->contract_signed_pdf_path));
        $this->assertSame('127.0.0.1', $registration->contract_signer_ip);

        Storage::disk('local')->assertExists($registration->contract_signature_path);
        Storage::disk('local')->assertExists($registration->contract_signed_pdf_path);
    }

    public function test_signed_contract_preserves_all_template_pages_and_stamps_page_twelve(): void
    {
        $this->seedContractTemplate();

        $this->post('/cadastro', $this->validPayload());

        $registration = ClientRegistration::query()->firstOrFail();
        $pdfPath = (string) $registration->contract_signed_pdf_path;

        $absolute = Storage::disk('local')->path($pdfPath);
        $this->assertFileExists($absolute);

        // O documento assinado mantém as 12 páginas do modelo (nenhuma
        // página é perdida no processo de overlay).
        $this->assertSame(12, app(ContractPdfService::class)->pageCount($absolute));

        // A página de assinatura recebe o nome, CPF e data do participante.
        // Fontes base do FPDF gravam strings ASCII como literais
        // `(...) Tj` no content stream — conferimos o overlay sem parser.
        $content = (string) file_get_contents($absolute);
        $this->assertStringContainsString('(Joao Oliveira Santos)', $content);
        $this->assertStringContainsString('529.982.247-25', $content);
        $this->assertMatchesRegularExpression('/\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}/', $content);
    }

    public function test_signature_is_rejected_when_left_blank(): void
    {
        $this->seedContractTemplate();

        $this->post('/cadastro', $this->validPayload(['contract_signature' => '']))
            ->assertSessionHasErrors('contract_signature');

        $this->assertDatabaseCount('client_registrations', 0);
    }

    public function test_blank_transparent_signature_is_rejected_by_the_server(): void
    {
        $this->seedContractTemplate();

        // PNG transparente/limpo sem tinta: passa no formato, falha no
        // critério de conteúdo (não confiamos no navegador).
        $transparent = imagecreatetruecolor(100, 50);
        imagesavealpha($transparent, true);
        $alphaColor = imagecolorallocatealpha($transparent, 255, 255, 255, 127);
        imagefill($transparent, 0, 0, $alphaColor);
        ob_start();
        imagepng($transparent);
        $png = ob_get_clean();
        imagedestroy($transparent);

        $this->post('/cadastro', $this->validPayload([
            'contract_signature' => 'data:image/png;base64,'.base64_encode($png),
        ]))
            ->assertSessionHasErrors('contract_signature');

        $this->assertDatabaseCount('client_registrations', 0);
    }

    public function test_signature_with_non_png_content_is_rejected(): void
    {
        $this->seedContractTemplate();

        $this->post('/cadastro', $this->validPayload([
            'contract_signature' => 'data:image/png;base64,'.base64_encode('não sou um png'),
        ]))
            ->assertSessionHasErrors('contract_signature');

        $this->assertDatabaseCount('client_registrations', 0);
    }

    public function test_signer_name_must_equal_full_name(): void
    {
        $this->seedContractTemplate();

        $this->post('/cadastro', $this->validPayload([
            'contract_signer_name' => 'Outra Pessoa Qualquer',
        ]))
            ->assertSessionHasErrors('contract_signer_name');

        $this->assertDatabaseCount('client_registrations', 0);
    }

    public function test_contract_acceptance_is_required(): void
    {
        $this->seedContractTemplate();

        $this->post('/cadastro', $this->validPayload(['contract_accepted' => '']))
            ->assertSessionHasErrors('contract_accepted');

        $this->assertDatabaseCount('client_registrations', 0);
    }

    /* ---------- Área administrativa ---------- */

    public function test_guest_is_redirected_to_login_on_contract_routes(): void
    {
        $registration = ClientRegistration::factory()->create();

        $this->get(route('admin.registrations.contract', $registration))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_signed_contract_and_audit_is_recorded(): void
    {
        $registration = $this->createSignedRegistration();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.contract', $registration))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => AuditAction::ViewContract->value,
            'registration_id' => $registration->id,
        ]);
    }

    public function test_admin_can_download_signed_contract(): void
    {
        $registration = $this->createSignedRegistration();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.contract.download', $registration))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename="contrato-assinado.pdf"');
    }

    public function test_admin_can_view_signature_image(): void
    {
        $registration = $this->createSignedRegistration();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.contract.signature', $registration))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_contract_routes_return_forbidden_for_unsigned_registrations(): void
    {
        $registration = ClientRegistration::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.contract', $registration))
            ->assertForbidden();
    }

    public function test_tampered_contract_path_is_never_served(): void
    {
        $registration = $this->createSignedRegistration();

        // Corrompe apenas o caminho do PDF (mantém a coluna de assinado).
        $registration->forceFill(['contract_signed_pdf_path' => 'cadastros/'.$registration->uuid.'/cnh_front/arquivo.jpg'])->save();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.contract', $registration->fresh()))
            ->assertNotFound();
    }

    public function test_registration_without_signed_contract_shows_pending_state_on_admin(): void
    {
        $registration = ClientRegistration::factory()->create();

        $html = $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.show', $registration))
            ->assertOk()
            ->assertSee('Contrato não assinado')
            ->getContent();

        $this->assertStringNotContainsString('/contrato-assinado', $html);
    }

    public function test_admin_show_renders_contract_details_when_signed(): void
    {
        $registration = $this->createSignedRegistration();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.show', $registration))
            ->assertOk()
            ->assertSee('Contrato assinado')
            ->assertSee('Joao Oliveira Santos')
            ->assertSee(route('admin.registrations.contract.download', $registration));
    }

    public function test_retention_purge_removes_contract_files(): void
    {
        $registration = $this->createSignedRegistration();

        Storage::disk('local')->assertExists($registration->contract_signature_path);
        Storage::disk('local')->assertExists($registration->contract_signed_pdf_path);

        app(RetentionService::class)->purge($registration);

        Storage::disk('local')->assertMissing($registration->contract_signature_path);
        Storage::disk('local')->assertMissing($registration->contract_signed_pdf_path);
    }
}
