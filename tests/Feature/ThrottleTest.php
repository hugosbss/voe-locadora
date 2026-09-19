<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['https://viacep.com.br/*' => Http::response([], 404)]);
    }

    public function test_registration_endpoint_is_rate_limited_against_floods(): void
    {
        // 60 submissões (mesmo inválidas) dentro da janela ainda retornam
        // resposta normal; a 61ª é bloqueada.
        for ($i = 0; $i < 60; $i++) {
            $this->post('/cadastro', ['full_name' => 'a']);
        }

        $this->post('/cadastro', ['full_name' => 'a'])
            ->assertStatus(429);
    }

    public function test_get_cadastro_is_not_counted_as_submission_attempt(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->post('/cadastro', ['full_name' => 'a']);
        }

        // Atingido o limite de POSTs, o GET continua livre (apenas renderiza o
        // formulário e nunca é contabilizado como tentativa de envio).
        $this->get('/cadastro')
            ->assertOk();
    }

    public function test_validation_errors_do_not_consume_creation_limit(): void
    {
        Storage::fake('local');
        Mail::fake();
        $this->seedContractTemplate();

        // Vários ciclos de erro de validação/correção (o dobro dos campos
        // principais): o usuário segue corrigindo e NUNCA recebe 429.
        for ($i = 0; $i < 20; $i++) {
            $this->post('/cadastro', ['full_name' => 'a'])
                ->assertStatus(302)
                ->assertSessionHasErrors('cpf');
        }

        // Correções livres não esgotam o limite: a submissão correta seguinte
        // ainda é processada normalmente (sem 429).
        $this->post('/cadastro', $this->validPayload())
            ->assertRedirect(route('client-registrations.success'));

        $this->assertDatabaseCount('client_registrations', 1);
    }

    public function test_only_successfully_created_registrations_consume_creation_limit(): void
    {
        Storage::fake('local');
        Mail::fake();
        $this->seedContractTemplate();

        for ($i = 0; $i < 10; $i++) {
            $this->post('/cadastro', $this->validPayload($i))
                ->assertRedirect(route('client-registrations.success'));
        }

        // 11º cadastro criado dentro da janela é bloqueado: limita o spam de
        // cadastros reais sem penalizar quem apenas corrige erros.
        $this->post('/cadastro', $this->validPayload(999))
            ->assertStatus(429);

        $this->assertDatabaseCount('client_registrations', 10);
    }

    public function test_cep_lookup_is_rate_limited(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->get('/cep?cep=01310-100');
        }

        $this->get('/cep?cep=01310-100')->assertStatus(429);
    }

    public function test_admin_login_is_rate_limited_per_credential(): void
    {
        User::factory()->create(['email' => 'limitada@example.com', 'password' => 'password']);

        $email = 'limitada@example.com';
        $payload = fn () => ['email' => $email, 'password' => 'password'];

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', $payload());
        }

        $this->post('/admin/login', $payload())->assertStatus(429);
    }

    public function test_admin_login_limit_is_scoped_per_email(): void
    {
        User::factory()->create(['email' => 'outra@example.com', 'password' => 'password']);

        $payload = ['email' => 'outra@example.com', 'password' => 'password'];

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', $payload);
        }

        // Email diferente não sofre bloqueio em cascata.
        $this->post('/admin/login', ['email' => 'diferente@example.com', 'password' => 'password'])
            ->assertStatus(302);
    }

    public function test_password_reset_link_request_is_rate_limited(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/admin/recuperar-senha', ['email' => 'alvo@example.com']);
        }

        $this->post('/admin/recuperar-senha', ['email' => 'alvo@example.com'])
            ->assertStatus(429);
    }

    private function validPayload(int $seed = 0): array
    {
        return [
            'full_name' => 'Maria da Silva Souza',
            'cpf' => $this->validCpf($seed + 100000000),
            'birth_date' => '1990-05-10',
            'phone' => '(11) 91234-5678',
            'whatsapp' => '(11) 91234-5678',
            'email' => "cliente{$seed}@example.com",
            'cep' => '01310-100',
            'address' => 'Avenida Paulista',
            'address_number' => '1000',
            'neighborhood' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
            'cnh_number' => '12345678901',
            'cnh_category' => 'B',
            'cnh_expiry_date' => '2030-01-01',
            'start_date' => '2026-09-19',
            'end_date' => '2026-10-19',
            'cnh_front_file' => UploadedFile::fake()->image('cnh-front.jpg', 600, 400),
            'cnh_back_file' => UploadedFile::fake()->image('cnh-back.jpg', 600, 400),
            'proof_of_residence_file' => UploadedFile::fake()->image('comprovante.jpg', 600, 400),
            'selfie_file' => UploadedFile::fake()->image('selfie.jpg', 600, 400),
            'veracity_declaration_accepted' => '1',
            'privacy_policy_accepted' => '1',
            'contract_signature' => $this->signatureDataUrl(),
            'contract_signer_name' => 'Maria da Silva Souza',
            'contract_accepted' => '1',
        ];
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

    private function validCpf(int $seed): string
    {
        $digits = array_map(
            'intval',
            str_split(str_pad((string) ($seed % 999999999), 9, '0', STR_PAD_LEFT)),
        );

        for ($j = 9; $j < 11; $j++) {
            $sum = 0;

            for ($i = 0; $i < $j; $i++) {
                $sum += $digits[$i] * (($j + 1) - $i);
            }

            $rest = $sum % 11;
            $digits[$j] = $rest < 2 ? 0 : 11 - $rest;
        }

        return implode('', $digits);
    }
}
