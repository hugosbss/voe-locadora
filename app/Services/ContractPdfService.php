<?php

namespace App\Services;

use App\Models\ClientRegistration;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use setasign\Fpdi\Fpdi;

/**
 * Gera contratos a partir do modelo oficial (12 páginas, PDF estático).
 *
 * Uma única rotina de overlay percorre todas as páginas do modelo e, por
 * página, aplica os overlays configurados:
 *   - página 1 — dados do PARTICIPANTE (overlay de texto cobrindo os
 *     placeholders do modelo);
 *   - página 12 — assinatura do PARTICIPANTE (imagem + nome/CPF/data).
 *
 * generateFilled() e generate() são os dois "temperos" dessa rotina única:
 * o contrato assinado tem a página 1 preenchida E a página 12 assinada,
 * com as demais páginas reproduzidas integralmente sem alteração.
 *
 * O modelo original permanece intacto (somente leitura).
 */
class ContractPdfService
{
    public function __construct(
        private readonly ContractStorageService $storage,
    ) {}

    /**
     * Gera `contracts/generated/{uuid}/contrato-preenchido.pdf` com os dados
     * do PARTICIPANTE preenchidos na página 1 do template.
     *
     * As demais páginas são reproduzidas integralmente sem alteração.
     *
     * @return string Caminho relativo do PDF gerado.
     */
    public function generateFilled(ClientRegistration $registration): string
    {
        $dir = 'contracts/generated/'.$registration->uuid;
        $this->ensureDirectory($dir);

        $destination = $this->storage->fullPath($dir.'/'.config('contracts.filled_filename'));

        if ($destination === null) {
            throw new RuntimeException('Não foi possível gerar o contrato preenchido.');
        }

        $this->buildDocument($registration, $destination);

        return $dir.'/'.config('contracts.filled_filename');
    }

    /**
     * Gera `contracts/signed/{uuid}/contrato-assinado.pdf`: contrato
     * definitivo com a página 1 preenchida e a página 12 assinada.
     *
     * @param  string  $signaturePath  caminho relativo de contracts/signed/{uuid}/signature.png.
     * @param  string  $signedAt  data/hora da assinatura no formato d/m/Y H:i.
     * @return string Caminho relativo do PDF gerado.
     */
    public function generate(
        ClientRegistration $registration,
        string $signaturePath,
        string $signedAt,
    ): string {
        $dir = 'contracts/signed/'.$registration->uuid;
        $this->ensureDirectory($dir);

        $destination = $this->storage->fullPath($dir.'/'.config('contracts.signed_default_filename'));

        if ($destination === null) {
            throw new RuntimeException('Não foi possível gerar o contrato assinado.');
        }

        $this->buildDocument($registration, $destination, $signaturePath, $signedAt);

        return $dir.'/'.config('contracts.signed_default_filename');
    }

    /**
     * Cria o diretório de destino de um contrato no disco privado.
     */
    private function ensureDirectory(string $dir): void
    {
        if (! Storage::disk(ContractStorageService::DISK)->makeDirectory($dir)) {
            throw new RuntimeException("Não foi possível criar o diretório: {$dir}");
        }
    }

    /**
     * Reproduz o modelo página a página, sobrepondo os overlays configurados:
     * página 1 (dados) e página 12 (assinatura, quando fornecida).
     *
     * @param  string  $destination  caminho absoluto de saída.
     * @param  string|null  $signaturePath  caminho relativo da assinatura PNG.
     * @param  string|null  $signedAt  data/hora da assinatura (d/m/Y H:i).
     */
    private function buildDocument(
        ClientRegistration $registration,
        string $destination,
        ?string $signaturePath = null,
        ?string $signedAt = null,
    ): void {
        try {
            $pdf = new Fpdi('P', 'pt', [config('contracts.pdf.page_width'), config('contracts.pdf.page_height')]);

            // Streams gerados sem compressão: além de tamanho desprezível,
            // deixam o conteúdo dos overrides legível para auditoria.
            $pdf->SetCompression(false);

            // setSourceFile() devolve o total de páginas do modelo.
            $totalPages = $pdf->setSourceFile($this->templateAbsolutePath());

            $fields = (array) config('contracts.fields.1', []);
            $signaturePage = (int) config('contracts.pdf.template_page');

            // Reproduz integralmente o documento; nada é removido/reescrito.
            for ($page = 1; $page <= $totalPages; $page++) {
                $templateId = $pdf->importPage($page);

                $pdf->AddPage();
                $pdf->useTemplate(
                    $templateId,
                    0,
                    0,
                    config('contracts.pdf.page_width'),
                    config('contracts.pdf.page_height'),
                );

                if ($page === 1 && $fields !== []) {
                    $this->writeFields($pdf, $fields, $registration);
                }

                if ($page === $signaturePage && $signaturePath !== null) {
                    $this->stampSignature($pdf, $signaturePath);
                    $this->stampName($pdf, (string) $registration->full_name);
                    $this->stampCpf($pdf, (string) $registration->cpf);
                    $this->stampDate($pdf, (string) $signedAt);
                }
            }

            $pdf->Output($destination, 'F');
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new RuntimeException('Não foi possível gerar o contrato.', 0, $e);
        }
    }

    /**
     * Escreve todos os campos configurados para a página indicada.
     *
     * @param  array<int, array<string, mixed>>  $fields
     */
    private function writeFields(Fpdi $pdf, array $fields, ClientRegistration $registration): void
    {
        foreach ($fields as $field) {
            $value = $this->resolveValue((string) $field['source'], $registration);

            if ($value === null || $value === '') {
                continue;
            }

            $this->writeFieldText(
                $pdf,
                $value,
                (float) $field['x'],
                (float) $field['y'],
                (float) $field['max_width'],
                (int) $field['size'],
            );
        }
    }

    /**
     * Resolve o nome da fonte (source) para o valor real do model.
     */
    private function resolveValue(string $source, ClientRegistration $registration): ?string
    {
        return match ($source) {
            'full_name' => $registration->full_name,
            'cpf' => $this->formatCpf($registration->cpf),
            'cnh_number' => $registration->cnh_number,
            'birth_date' => $registration->birth_date
                ? $registration->birth_date->format('d/m/Y')
                : null,
            'full_address' => $this->composeAddress($registration),
            'phone' => $registration->phone ?: $registration->whatsapp,
            'email' => $registration->email,
            default => null,
        };
    }

    /**
     * Monta o endereço completo: "Rua, Número - Bairro, Cidade/UF - CEP".
     */
    private function composeAddress(ClientRegistration $registration): ?string
    {
        $parts = array_filter([
            $registration->address,
            $registration->address_number,
        ]);

        $street = $parts !== [] ? implode(', ', $parts) : null;

        $cityLine = array_filter([
            $registration->neighborhood,
            $registration->city && $registration->state
                ? $registration->city.'/'.$registration->state
                : $registration->city,
            $registration->cep ? 'CEP '.$registration->cep : null,
        ]);

        $cityPart = $cityLine !== [] ? implode(', ', $cityLine) : null;

        if ($street === null && $cityPart === null) {
            return null;
        }

        $parts = array_filter([$street, $cityPart]);

        return $parts !== [] ? implode(' - ', $parts) : null;
    }

    /**
     * Formata um CPF em "000.000.000-00" (vazio quando sem 11 dígitos).
     */
    private function formatCpf(?string $cpf): string
    {
        $digits = preg_replace('/\D/', '', (string) $cpf);

        if (! is_string($digits) || $digits === '') {
            return '';
        }

        $formatted = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits);

        return $formatted ?? $digits;
    }

    /**
     * Escreve texto no PDF com iconv para ISO-8859-1 + size fitting
     * automático (mínimo 6 pt).
     *
     * FPDF não suporta UTF-8 nativamente; todo texto passa por iconv antes
     * de ser escrito. O placeholder do modelo é coberto por um retângulo
     * branco antes do valor (se habilitado).
     */
    private function writeFieldText(
        Fpdi $pdf,
        string $value,
        float $x,
        float $y,
        float $maxWidth,
        int $fontSize,
    ): void {
        $font = (string) config('contracts.pdf.font', 'Helvetica');
        $encoded = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $value);

        if ($encoded === false || $encoded === '') {
            return;
        }

        // Cobre o placeholder do modelo ("[NOME COMPLETO]", "[●]") antes de
        // escrever o valor — o texto original permanece no modelo importado,
        // então precisa ser ocultado no plano visual.
        if (config('contracts.pdf.mask_placeholder', true)) {
            $this->maskPlaceholder($pdf, $x, $y, $maxWidth);
        }

        $size = (float) $fontSize;

        // Set font first so GetStringWidth works correctly.
        $pdf->SetFont($font, '', $size);

        // Reduz o tamanho da fonte até o texto caber em max_width.
        while ($size >= 6.0 && $pdf->GetStringWidth($encoded) > $maxWidth) {
            $size -= 0.5;
            $pdf->SetFont($font, '', $size);
        }

        $pdf->SetTextColor(31, 41, 55);
        $pdf->setXY($x, $y - 8);
        $pdf->Cell(0, 10, $encoded, 0, 0, 'L');
    }

    /**
     * Cobre uma faixa horizontal do modelo com branco (placeholder).
     *
     * A caixa é desenhada na mesma origem da célula de texto (setXY(x, y-8))
     * com a altura configurada — suficiente para cobrir o glifo do campo.
     */
    private function maskPlaceholder(Fpdi $pdf, float $x, float $y, float $maxWidth): void
    {
        $height = (float) config('contracts.pdf.mask_height', 11.0);

        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($x, $y - 8, $maxWidth, $height, 'F');
    }

    /**
     * Caminho absoluto do modelo oficial no disco privado.
     */
    private function templateAbsolutePath(): string
    {
        $path = Storage::disk(ContractStorageService::DISK)->path((string) config('contracts.template_path'));

        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Modelo do contrato indisponível.');
        }

        return $path;
    }

    /**
     * Sobreposição da assinatura no bloco do PARTICIPANTE, preservando a
     * proporção do traço dentro da caixa calibrada.
     */
    private function stampSignature(Fpdi $pdf, string $signaturePath): void
    {
        $signatureAbsolute = $this->storage->fullPath($signaturePath);

        if ($signatureAbsolute === null || ! is_file($signatureAbsolute)) {
            throw new RuntimeException('Assinatura indisponível para o contrato.');
        }

        $info = @getimagesize($signatureAbsolute);

        if (! is_array($info) || ! isset($info[0], $info[1])) {
            throw new RuntimeException('Assinatura inválida para o contrato.');
        }

        [$width, $height] = [max(1, (int) $info[0]), max(1, (int) $info[1])];

        $maxWidth = (float) config('contracts.pdf.signature.max_width');
        $maxHeight = (float) config('contracts.pdf.signature.max_height');

        $scale = min($maxWidth / $width, $maxHeight / $height, 1.0);
        $drawWidth = round($width * $scale, 2);
        $drawHeight = round($height * $scale, 2);

        $x = (float) config('contracts.pdf.signature.x');
        $y = (float) config('contracts.pdf.signature.y');

        // Centraliza verticalmente na caixa de assinatura.
        $yCentered = $y + ($maxHeight - $drawHeight) / 2;

        $pdf->Image($signatureAbsolute, $x, $yCentered, $drawWidth, $drawHeight, 'PNG');
    }

    /**
     * Imprime o nome do participante ao lado do rótulo "Nome".
     */
    private function stampName(Fpdi $pdf, string $signerName): void
    {
        $config = (array) config('contracts.pdf.signer_name');

        $this->printText($pdf, $signerName, $config);
    }

    /**
     * Imprime o CPF ao lado do rótulo "CPF".
     */
    private function stampCpf(Fpdi $pdf, string $cpf): void
    {
        $config = (array) config('contracts.pdf.signer_cpf');

        $this->printText($pdf, $this->formatCpf($cpf), $config);
    }

    /**
     * Imprime a data/hora da assinatura ao lado do rótulo "Data".
     */
    private function stampDate(Fpdi $pdf, string $signedAt): void
    {
        $config = (array) config('contracts.pdf.signed_date');

        $this->printText($pdf, $signedAt, $config);
    }

    /**
     * Escreve um texto na coordenada calibrada (bloco de assinatura).
     *
     * Quando o campo define mask_width, o placeholder correspondente
     * ("____/____/________") é coberto por branco antes do valor.
     *
     * @param  array<string, mixed>  $config
     */
    private function printText(Fpdi $pdf, string $value, array $config): void
    {
        if ($value === '') {
            return;
        }

        $x = (float) ($config['x'] ?? 0);
        $y = (float) ($config['y'] ?? 0);

        if (config('contracts.pdf.mask_placeholder', true) && isset($config['mask_width'])) {
            $this->maskPlaceholder($pdf, $x, $y, (float) $config['mask_width']);
        }

        $pdf->SetFont((string) config('contracts.pdf.font'), '', (int) ($config['size'] ?? 9));
        $pdf->SetTextColor(31, 41, 55);
        $pdf->setXY($x, $y - 8);
        $pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $value), 0, 0, 'L');
    }
}
