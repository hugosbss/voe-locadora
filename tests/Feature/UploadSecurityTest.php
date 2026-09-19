<?php

namespace Tests\Feature;

use App\Models\ClientRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesQuotaContext;
use Tests\TestCase;

class UploadSecurityTest extends TestCase
{
    use CreatesQuotaContext;
    use RefreshDatabase;

    private function baseData(): array
    {
        [$vehicle, $quota] = $this->createQuotaContext(30);
        [$startDate, $endDate] = $this->bookingPeriod(30);

        return [
            'full_name' => 'João Testador',
            'cpf' => '529.982.247-25',
            'birth_date' => '1985-04-02',
            'phone' => '(11) 91234-5678',
            'whatsapp' => '(11) 91234-5678',
            'email' => 'joao@example.com',
            'cep' => '01310-100',
            'address' => 'Avenida Paulista',
            'address_number' => '100',
            'neighborhood' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
            'cnh_number' => '12345678901',
            'cnh_category' => 'B',
            'cnh_expiry_date' => '2030-01-01',
            'vehicle_id' => (string) $vehicle->id,
            'quota_type_id' => (string) $quota->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'veracity_declaration_accepted' => '1',
            'privacy_policy_accepted' => '1',
            'contract_signature' => $this->signatureDataUrl(),
            'contract_signer_name' => 'João Testador',
            'contract_accepted' => '1',
        ];
    }

    private function files(): array
    {
        return [
            'cnh_front_file' => UploadedFile::fake()->image('cnh-front.jpg', 600, 400),
            'cnh_back_file' => UploadedFile::fake()->image('cnh-back.jpg', 600, 400),
            'proof_of_residence_file' => UploadedFile::fake()->image('comprovante.jpg', 600, 400),
            'selfie_file' => UploadedFile::fake()->image('selfie.jpg', 600, 400),
        ];
    }

    public function test_rejects_non_image_content_despite_jpg_extension(): void
    {
        Storage::fake('local');

        $textFile = tempnam(sys_get_temp_dir(), 'fake');
        file_put_contents($textFile, 'Não sou uma imagem.');

        $payload = [
            ...$this->baseData(),
            ...$this->files(),
            'cnh_front_file' => new UploadedFile($textFile, 'cnh-front.jpg', 'text/plain', null, true),
        ];

        $this->post('/cadastro', $payload)
            ->assertSessionHasErrors('cnh_front_file');

        $this->assertDatabaseCount('client_registrations', 0);
    }

    public function test_rejects_oversized_dimensions(): void
    {
        $this->post('/cadastro', [
            ...$this->baseData(),
            ...$this->files(),
            'cnh_front_file' => UploadedFile::fake()->image('big.jpg', 9000, 100),
        ])->assertSessionHasErrors('cnh_front_file');
    }

    public function test_rejects_tiny_dimensions(): void
    {
        $this->post('/cadastro', [
            ...$this->baseData(),
            ...$this->files(),
            'cnh_front_file' => UploadedFile::fake()->image('small.jpg', 100, 100),
        ])->assertSessionHasErrors('cnh_front_file');
    }

    public function test_stored_document_is_normalized_to_jpeg_with_server_generated_path(): void
    {
        Storage::fake('local');
        $this->seedContractTemplate();

        $this->post('/cadastro', [
            ...$this->baseData(),
            ...$this->files(),
        ])->assertRedirect();

        $registration = ClientRegistration::query()->firstOrFail();

        $this->assertMatchesRegularExpression(
            '#^cadastros/[0-9a-f-]{36}/[a-z_]+/[A-Za-z0-9]{40}\.jpg$#',
            $registration->cnh_front_path
        );

        $bytes = Storage::disk('local')->get($registration->cnh_front_path);
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $this->assertSame('image/jpeg', $finfo->buffer($bytes));
    }

    public function test_duplicate_cpf_is_accepted_and_registrations_are_independent(): void
    {
        Storage::fake('local');
        $this->seedContractTemplate();

        ClientRegistration::factory()->create(['cpf' => '52998224725']);

        $this->post('/cadastro', [
            ...$this->baseData(),
            ...$this->files(),
        ])->assertRedirect();

        $this->assertDatabaseCount('client_registrations', 2);

        $first = ClientRegistration::orderBy('id')->first();
        $second = ClientRegistration::orderByDesc('id')->first();

        $this->assertSame('52998224725', $second->cpf);
        $this->assertNotSame($first->uuid, $second->uuid);
        $this->assertMatchesRegularExpression(
            '#^contracts/signed/[0-9a-f-]{36}/contrato-assinado\.pdf$#',
            $second->contract_signed_pdf_path
        );
    }

    /**
     * Prepara o disco privado fake com o modelo do contrato (sintético,
     * 12 páginas A4 — mesmo que o usado nos testes de contrato).
     */
    private function seedContractTemplate(): void
    {
        $pdf = new \FPDF('P', 'pt', [595.276, 841.89]);

        for ($page = 1; $page <= 12; $page++) {
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 12);
            $pdf->Text(40, 40, 'Contrato sintetico para testes — pagina '.$page);
        }

        Storage::disk('local')->put(config('contracts.template_path'), $pdf->Output('S'));
    }

    /**
     * Gera um payload PNG de assinatura desenhada (traço escuro em fundo
     * branco), no mesmo formato enviado pelo canvas do navegador.
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
}
