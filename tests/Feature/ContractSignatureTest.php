<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\RegistrationStatus;
use App\Models\ClientRegistration;
use App\Models\User;
use App\Services\ContractPdfService;
use App\Services\ContractStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

/**
 * Assinatura do contrato em um cadastro existente.
 *
 * O cadastro público é enviado SEM assinatura; a assinatura é registrada
 * depois, pelo link que a locadora copia no detalhe do cadastro e envia ao
 * cliente. O fluxo nunca duplica o cadastro: a assinatura é gravada no
 * registro original.
 */
class ContractSignatureTest extends TestCase
{
    use RefreshDatabase;

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
     * PNG transparente/limpo, sem tinta: passa no formato e falha no critério
     * de conteúdo do servidor.
     */
    private function blankSignatureDataUrl(): string
    {
        $image = imagecreatetruecolor(100, 50);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 255, 255, 255, 127));

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    /**
     * Cadastro elegível para assinatura: aprovado e sem assinatura.
     */
    private function approvedRegistration(): ClientRegistration
    {
        return ClientRegistration::factory()->create([
            'full_name' => 'Joao Oliveira Santos',
            'cpf' => '529.982.247-25',
            'cnh_number' => '99887766554',
            'status' => RegistrationStatus::Aprovado,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-30',
            'quota_days' => 30,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'contract_signature' => $this->signatureDataUrl(),
            'contract_accepted' => '1',
        ], $overrides);
    }

    /* ---------- Cadastro sem assinatura ---------- */

    public function test_registration_is_created_without_signature_and_keeps_filled_contract(): void
    {
        $this->seedContractTemplate();

        $registration = ClientRegistration::factory()->create();

        $this->post(route('client-registrations.signature.store', $registration), [])
            ->assertSessionHasErrors(['contract_signature', 'contract_accepted']);

        $this->assertFalse($registration->fresh()->hasSignedContract());
    }

    /* ---------- Elegibilidade do link ---------- */

    public function test_signature_page_is_available_for_approved_registration_without_signature(): void
    {
        $this->seedContractTemplate();

        $registration = $this->approvedRegistration();

        $this->get(route('client-registrations.signature', $registration))
            ->assertOk()
            ->assertSee('Contrato do Clube de Mobilidade')
            // Dados do titular para conferência, parcialmente mascarados.
            ->assertSee('Joao Oliveira Santos')
            ->assertSee('529.***.***-25')
            ->assertSee('*******6554')
            // Contrato para leitura + canvas da assinatura.
            ->assertSee(route('client-registrations.contract'))
            ->assertSee('id="signature-canvas"', false)
            ->assertSee('name="contract_accepted"', false)
            // A tela é pública: CPF e CNH completos não podem aparecer.
            ->assertDontSee('529.982.247-25')
            ->assertDontSee('99887766554');

        // A tela é somente leitura: nenhum campo de cadastro é enviado.
        $html = $this->get(route('client-registrations.signature', $registration))->getContent();
        $this->assertStringNotContainsString('name="full_name"', $html);
        $this->assertStringNotContainsString('name="cpf"', $html);
        $this->assertStringNotContainsString('name="contract_signer_name"', $html);
    }

    public function test_signature_page_is_unavailable_for_registration_not_approved(): void
    {
        $registration = ClientRegistration::factory()->create(['status' => RegistrationStatus::EmAnalise]);

        $this->get(route('client-registrations.signature', $registration))
            ->assertForbidden()
            ->assertSee('Link ainda indisponível')
            ->assertDontSee('id="signature-canvas"', false);
    }

    public function test_signature_page_is_unavailable_for_rejected_registration(): void
    {
        $registration = ClientRegistration::factory()->create(['status' => RegistrationStatus::Reprovado]);

        $this->get(route('client-registrations.signature', $registration))
            ->assertForbidden()
            ->assertDontSee('id="signature-canvas"', false);
    }

    public function test_signature_page_reports_contract_already_signed(): void
    {
        $this->seedContractTemplate();

        $registration = $this->approvedRegistration();

        $this->post(route('client-registrations.signature.store', $registration), $this->validPayload())
            ->assertRedirect(route('client-registrations.signature-done'));

        $this->get(route('client-registrations.signature', $registration->fresh()))
            ->assertOk()
            ->assertSee('Contrato já assinado')
            ->assertDontSee('id="signature-canvas"', false);
    }

    public function test_unknown_registration_uuid_is_not_found(): void
    {
        $this->get('/contrato/assinatura/'.fake()->uuid())
            ->assertNotFound();
    }

    /* ---------- Registro da assinatura ---------- */

    public function test_registration_casts_the_booking_period_to_dates(): void
    {
        $registration = $this->approvedRegistration();

        $this->assertInstanceOf(Carbon::class, $registration->start_date);
        $this->assertInstanceOf(Carbon::class, $registration->end_date);
        $this->assertIsInt($registration->quota_days);
    }

    public function test_signature_page_renders_the_booking_period(): void
    {
        $this->seedContractTemplate();

        $registration = $this->approvedRegistration();

        $this->get(route('client-registrations.signature', $registration))
            ->assertOk()
            ->assertSee(
                $registration->start_date->format('d/m/Y').' a '.$registration->end_date->format('d/m/Y'),
            );
    }

    public function test_signature_is_recorded_on_the_existing_registration(): void
    {
        $this->seedContractTemplate();

        $registration = $this->approvedRegistration();

        $this->post(route('client-registrations.signature.store', $registration), $this->validPayload())
            ->assertRedirect(route('client-registrations.signature-done'));

        $signed = $registration->fresh();

        $this->assertTrue($signed->contract_signed);
        $this->assertSame('1.0', $signed->contract_version);
        // O signatário é o titular do cadastro (nunca vem do navegador).
        $this->assertSame('Joao Oliveira Santos', $signed->contract_signer_name);
        $this->assertSame('127.0.0.1', $signed->contract_signer_ip);
        $this->assertTrue($signed->contract_signed_at?->gte(now('America/Sao_Paulo')->subMinute()));
        $this->assertTrue($signed->contract_signed_at?->lte(now('America/Sao_Paulo')->addSecond()));
        $this->assertStringStartsWith('contracts/signed/'.$signed->uuid.'/', (string) $signed->contract_signature_path);
        $this->assertSame('contrato-assinado.pdf', basename((string) $signed->contract_signed_pdf_path));

        Storage::disk('local')->assertExists($signed->contract_signature_path);
        Storage::disk('local')->assertExists($signed->contract_signed_pdf_path);

        // Nenhum cadastro novo é criado e nenhum outro campo muda.
        $this->assertDatabaseCount('client_registrations', 1);
        $this->assertDatabaseHas('client_registrations', [
            'id' => $signed->id,
            'full_name' => 'Joao Oliveira Santos',
            'cpf' => '52998224725',
            'status' => RegistrationStatus::Aprovado->value,
        ]);
    }

    public function test_signature_page_confirms_the_signature_was_recorded(): void
    {
        $this->get(route('client-registrations.signature-done'))
            ->assertOk()
            ->assertSee('Assinatura registrada');
    }

    public function test_signed_contract_keeps_all_template_pages_and_stamps_page_twelve(): void
    {
        $this->seedContractTemplate();

        $registration = $this->approvedRegistration();

        $this->post(route('client-registrations.signature.store', $registration), $this->validPayload());

        $absolute = Storage::disk('local')->path((string) $registration->fresh()->contract_signed_pdf_path);
        $this->assertFileExists($absolute);

        $reader = new Fpdi('P', 'pt');
        $this->assertSame(12, $reader->setSourceFile($absolute));

        $content = (string) file_get_contents($absolute);
        $this->assertStringContainsString('(Joao Oliveira Santos)', $content);
        $this->assertStringContainsString('529.982.247-25', $content);
        $this->assertMatchesRegularExpression('/\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}/', $content);
    }

    public function test_signature_is_audited(): void
    {
        $this->seedContractTemplate();

        $registration = $this->approvedRegistration();

        $this->post(route('client-registrations.signature.store', $registration), $this->validPayload());

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => AuditAction::ContractSigned->value,
            'registration_id' => $registration->id,
        ]);
    }

    /* ---------- Validação da assinatura ---------- */

    public function test_signature_is_required(): void
    {
        $this->seedContractTemplate();

        $registration = $this->approvedRegistration();

        $this->post(route('client-registrations.signature.store', $registration), $this->validPayload([
            'contract_signature' => '',
        ]))->assertSessionHasErrors('contract_signature');

        $this->assertFalse($registration->fresh()->hasSignedContract());
    }

    public function test_contract_acceptance_is_required(): void
    {
        $this->seedContractTemplate();

        $registration = $this->approvedRegistration();

        $this->post(route('client-registrations.signature.store', $registration), $this->validPayload([
            'contract_accepted' => '',
        ]))->assertSessionHasErrors('contract_accepted');

        $this->assertFalse($registration->fresh()->hasSignedContract());
    }

    public function test_blank_signature_is_rejected_by_the_server(): void
    {
        $this->seedContractTemplate();

        $registration = $this->approvedRegistration();

        $this->post(route('client-registrations.signature.store', $registration), $this->validPayload([
            'contract_signature' => $this->blankSignatureDataUrl(),
        ]))->assertSessionHasErrors('contract_signature');

        $this->assertFalse($registration->fresh()->hasSignedContract());
        Storage::disk('local')->assertMissing('contracts/signed/'.$registration->uuid);
    }

    public function test_signature_with_non_png_content_is_rejected(): void
    {
        $this->seedContractTemplate();

        $registration = $this->approvedRegistration();

        $this->post(route('client-registrations.signature.store', $registration), $this->validPayload([
            'contract_signature' => 'data:image/png;base64,'.base64_encode('não sou um png'),
        ]))->assertSessionHasErrors('contract_signature');

        $this->assertFalse($registration->fresh()->hasSignedContract());
    }

    public function test_failed_signature_keeps_the_filled_contract_and_allows_a_new_attempt(): void
    {
        $this->seedContractTemplate();

        $registration = $this->approvedRegistration();
        $filledPath = app(ContractPdfService::class)->generateFilled($registration);
        $registration->update(['filled_contract_path' => $filledPath]);

        $this->post(route('client-registrations.signature.store', $registration), $this->validPayload([
            'contract_signature' => $this->blankSignatureDataUrl(),
        ]))->assertSessionHasErrors('contract_signature');

        $failed = $registration->fresh();

        $this->assertFalse($failed->hasSignedContract());
        $this->assertNull($failed->contract_signature_path);
        $this->assertNull($failed->contract_signed_pdf_path);
        // O contrato preenchido do cadastro não é apagado pela tentativa.
        Storage::disk('local')->assertExists($filledPath);

        // O mesmo link continua válido para uma nova tentativa.
        $this->post(route('client-registrations.signature.store', $failed), $this->validPayload())
            ->assertRedirect(route('client-registrations.signature-done'));

        $this->assertTrue($failed->fresh()->hasSignedContract());
    }

    /* ---------- Link reutilizado / cadastro não elegível ---------- */

    public function test_registration_not_approved_cannot_be_signed(): void
    {
        $this->seedContractTemplate();

        $registration = ClientRegistration::factory()->create(['status' => RegistrationStatus::Novo]);

        $this->post(route('client-registrations.signature.store', $registration), $this->validPayload())
            ->assertRedirect(route('client-registrations.signature', $registration));

        $this->assertFalse($registration->fresh()->hasSignedContract());
    }

    public function test_contract_cannot_be_signed_twice(): void
    {
        $this->seedContractTemplate();

        $registration = $this->approvedRegistration();

        $this->post(route('client-registrations.signature.store', $registration), $this->validPayload())
            ->assertRedirect(route('client-registrations.signature-done'));

        $signed = $registration->fresh();
        $signedAt = $signed->contract_signed_at;
        $signaturePath = $signed->contract_signature_path;
        $pdfPath = $signed->contract_signed_pdf_path;

        $this->post(route('client-registrations.signature.store', $signed), $this->validPayload())
            ->assertRedirect(route('client-registrations.signature', $signed));

        $again = $signed->fresh();

        // Nada muda e — principalmente — o contrato já assinado não é apagado.
        $this->assertTrue($again->contract_signed);
        $this->assertEquals($signedAt, $again->contract_signed_at);
        $this->assertSame($signaturePath, $again->contract_signature_path);
        $this->assertSame($pdfPath, $again->contract_signed_pdf_path);

        Storage::disk('local')->assertExists($signaturePath);
        Storage::disk('local')->assertExists($pdfPath);
    }

    public function test_signature_does_not_change_the_registration_status(): void
    {
        $this->seedContractTemplate();

        $registration = $this->approvedRegistration();

        $this->post(route('client-registrations.signature.store', $registration), $this->validPayload());

        $this->assertSame(RegistrationStatus::Aprovado, $registration->fresh()->status);
    }

    /* ---------- Link no painel administrativo ---------- */

    public function test_admin_can_copy_the_signature_link_for_approved_registration(): void
    {
        $registration = $this->approvedRegistration();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.show', $registration))
            ->assertOk()
            ->assertSee('Link de assinatura do contrato')
            ->assertSee(route('client-registrations.signature', $registration), false);
    }

    public function test_admin_has_no_signature_link_before_approval(): void
    {
        $registration = ClientRegistration::factory()->create(['status' => RegistrationStatus::EmAnalise]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.show', $registration))
            ->assertOk()
            ->assertSee('Contrato não assinado')
            ->assertSee('O link de assinatura fica disponível aqui assim que o cadastro for aprovado.')
            ->assertDontSee(route('client-registrations.signature', $registration), false);
    }

    public function test_admin_has_no_signature_link_after_the_contract_is_signed(): void
    {
        $this->seedContractTemplate();

        $registration = $this->approvedRegistration();

        $this->post(route('client-registrations.signature.store', $registration), $this->validPayload());

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.show', $registration->fresh()))
            ->assertOk()
            ->assertSee('Contrato assinado')
            ->assertDontSee('Link de assinatura do contrato');
    }

    public function test_admin_signature_link_points_to_a_working_public_page(): void
    {
        $registration = $this->approvedRegistration();

        $url = route('client-registrations.signature', $registration);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.show', $registration))
            ->assertSee($url, false);

        $this->get($url)->assertOk()->assertSee('Contrato do Clube de Mobilidade');
    }

    /* ---------- Armazenamento ---------- */

    public function test_signature_is_stored_only_under_the_registration_directory(): void
    {
        $this->seedContractTemplate();

        $registration = $this->approvedRegistration();

        $this->post(route('client-registrations.signature.store', $registration), $this->validPayload());

        $signed = $registration->fresh();

        $this->assertTrue($signed->ownsStoredContractFile((string) $signed->contract_signature_path));
        $this->assertTrue($signed->ownsStoredContractFile((string) $signed->contract_signed_pdf_path));
        $this->assertTrue(app(ContractStorageService::class)->isSafeSignedPath((string) $signed->contract_signed_pdf_path));
    }
}
