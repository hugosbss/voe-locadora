<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use setasign\Fpdi\Fpdi;

/**
 * Gera o contrato assinado a partir do modelo oficial (12 páginas, PDF
 * estático). A assinatura desenhada, o nome/CPF do participante e a data
 * são sobrepostos na página 12, na área do PARTICIPANTE — sem reconstruir
 * o documento nem tocar nas demais páginas.
 *
 * O PDF final é derivado por overlay (FPDF + FPDI) e o modelo original
 * permanece intacto (somente leitura).
 */
class ContractPdfService
{
    public function __construct(
        private readonly ContractStorageService $storage,
    ) {}

    /**
     * Gera `contracts/signed/{uuid}/contrato-assinado.pdf` e retorna o
     * caminho relativo.
     *
     * @param  string  $registrationUuid  UUID do cadastro.
     * @param  string  $signaturePath  caminho relativo de contracts/signed/{uuid}/signature.png.
     * @param  string  $signerName  nome do participante (campo "Nome").
     * @param  string  $cpf  CPF apenas dígitos (campo "CPF").
     * @param  string  $signedAt  data/hora da assinatura no formato d/m/Y H:i.
     */
    public function generate(
        string $registrationUuid,
        string $signaturePath,
        string $signerName,
        string $cpf,
        string $signedAt,
    ): string {
        $template = $this->templateAbsolutePath();

        $destination = $this->storage->fullPath(
            'contracts/signed/'.$registrationUuid.'/'.config('contracts.signed_default_filename'),
        );

        if ($destination === null) {
            throw new RuntimeException('Não foi possível gerar o contrato assinado.');
        }

        try {
            $pdf = new Fpdi('P', 'pt', [config('contracts.pdf.page_width'), config('contracts.pdf.page_height')]);

            // Streams gerados sem compressão: além de tamanho desprezível,
            // deixam o conteúdo dos overrides legível para auditoria.
            $pdf->SetCompression(false);

            $pdf->setSourceFile($template);

            $totalPages = $this->pageCount($template);
            $signaturePage = (int) config('contracts.pdf.template_page');

            // Reproduz integralmente o documento, sobrepondo assinatura e
            // dados apenas na página calibrada — nada é removido/reescrito.
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

                if ($page === $signaturePage) {
                    $this->stampSignature($pdf, $signaturePath);
                    $this->stampName($pdf, $signerName);
                    $this->stampCpf($pdf, $cpf);
                    $this->stampDate($pdf, $signedAt);
                }
            }

            $pdf->Output($destination, 'F');
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new RuntimeException('Não foi possível gerar o contrato assinado.', 0, $e);
        }

        return 'contracts/signed/'.$registrationUuid.'/'.config('contracts.signed_default_filename');
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

        $digits = preg_replace('/\D/', '', (string) $cpf);
        $formatted = is_string($digits)
            ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits)
            : '';

        $this->printText($pdf, (string) $formatted, $config);
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
     * Escreve um texto na coordenada calibrada.
     *
     * @param  array<string, mixed>  $config
     */
    private function printText(Fpdi $pdf, string $value, array $config): void
    {
        if ($value === '') {
            return;
        }

        $pdf->SetFont((string) config('contracts.pdf.font'), '', (int) ($config['size'] ?? 9));
        $pdf->SetTextColor(31, 41, 55);
        $pdf->setXY((float) ($config['x'] ?? 0), (float) ($config['y'] ?? 0) - 8);
        $pdf->Cell(0, 10, $value, 0, 0, 'L');
    }

    /**
     * Conta as páginas de um PDF já gravado (auxiliar de validação/teste).
     */
    public function pageCount(string $absolutePath): int
    {
        if (! is_file($absolutePath)) {
            throw new RuntimeException('PDF não encontrado.');
        }

        $pdf = new Fpdi('P', 'pt');

        try {
            $pdf->setSourceFile($absolutePath);
        } catch (\Throwable $e) {
            throw new RuntimeException('Não foi possível ler o PDF.', 0, $e);
        }

        $pages = 0;

        while (true) {
            try {
                $pdf->importPage($pages + 1);
                $pages++;
            } catch (\Throwable) {
                break;
            }
        }

        return $pages;
    }
}
