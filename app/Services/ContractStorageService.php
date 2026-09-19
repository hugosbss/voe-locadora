<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Armazenamento seguro da assinatura digital e do contrato assinado.
 *
 * A assinatura chega como data URL PNG (payload do canvas). O payload é
 * validado pelo conteúdo REAL (decodificação + GD), reprocessado para um
 * novo PNG transparente e guardado com nome fixo gerado pelo servidor.
 *
 * Garantias (similares ao DocumentStorageService):
 *  - caminhos sempre gerados pelo servidor, whitelist de arquivos;
 *  - nenhum dado pessoal (CPF/nome) no nome do arquivo;
 *  - validação de dimensões e memória (decompression bomb);
 *  - rejeição de assinatura vazia (critério de tinta).
 */
class ContractStorageService
{
    public const string DISK = 'local';

    // Limites do payload PNG recebido do navegador (antes da decodificação).
    public const MAX_SIGNATURE_PAYLOAD_BYTES = 4 * 1024 * 1024;

    public const MAX_DIMENSION = 1500;

    public const MAX_IMAGE_SIDE = 4000;

    // Quantidade mínima de pixels de "tinta" para considerar desenhado.
    public const MIN_INK_PIXELS = 40;

    /**
     * Retorna o caminho completo (filesystem) de um arquivo de contrato.
     */
    public function fullPath(?string $path): ?string
    {
        return $path ? Storage::disk(self::DISK)->path($path) : null;
    }

    /**
     * Confirma que um caminho de contrato segue exatamente o formato
     * gerado pelo servidor — os dois únicos arquivos permitidos.
     */
    public function isSafeSignedPath(string $path): bool
    {
        return preg_match('#^contracts/signed/[0-9a-f-]{36}/(signature\.png|contrato-assinado\.pdf)$#', $path) === 1;
    }

    /**
     * Confirma que um arquivo de assinatura pertence ao cadastro informado
     * e segue o formato da whitelist de contratos.
     */
    public function belongsToRegistration(string $registrationUuid, string $path): bool
    {
        return $this->isSafeSignedPath($path)
            && str_starts_with($path, 'contracts/signed/'.$registrationUuid.'/');
    }

    public function response(string $path, ?string $name = null, array $headers = [])
    {
        return Storage::disk(self::DISK)->response($path, $name, $headers);
    }

    /**
     * Remove o diretório do contrato assinado de um cadastro.
     */
    public function deleteRegistrationDirectory(string $registrationUuid): void
    {
        Storage::disk(self::DISK)->deleteDirectory('contracts/signed/'.$registrationUuid);
        Storage::disk(self::DISK)->deleteDirectory('contracts/generated/'.$registrationUuid);
    }

    /**
     * Valida o payload base64, reprocessa o traço como PNG transparente e
     * o grava no diretório do cadastro. Retorna o caminho relativo.
     */
    public function storeSignature(string $dataUrl, string $registrationUuid): string
    {
        $binary = $this->decodePayload($dataUrl);

        $image = $this->validateAndDecode($binary);

        try {
            // Reprocessa em um novo PNG transparente, limitando dimensões
            // e descartando qualquer payload/metadado inesperado.
            $png = $this->normalize($image);

            $path = 'contracts/signed/'.$registrationUuid.'/'.config('contracts.signature_filename');

            Storage::disk(self::DISK)->put($path, $png);
        } finally {
            imagedestroy($image);
        }

        return $path;
    }

    /**
     * Extrai e valida a data URL `data:image/png;base64,...`. Todos os
     * erros viram RuntimeException para o fluxo de criação tratar.
     */
    private function decodePayload(string $payload): string
    {
        if (! is_string($payload) || $payload === '') {
            throw new RuntimeException('Assinatura não fornecida.');
        }

        if (strlen($payload) > self::MAX_SIGNATURE_PAYLOAD_BYTES * 2) {
            throw new RuntimeException('A assinatura excede o tamanho permitido.');
        }

        $prefix = 'data:image/png;base64,';

        if (! str_starts_with($payload, $prefix)) {
            throw new RuntimeException('Formato de assinatura inválido.');
        }

        $binary = base64_decode(substr($payload, strlen($prefix)), true);

        if ($binary === false || $binary === '') {
            throw new RuntimeException('Assinatura inválida.');
        }

        if (strlen($binary) > self::MAX_SIGNATURE_PAYLOAD_BYTES) {
            throw new RuntimeException('A assinatura excede o tamanho permitido.');
        }

        return $binary;
    }

    /**
     * Valida o conteúdo real (assinatura PNG) com GD e devolve o recurso.
     *
     * @return resource
     */
    private function validateAndDecode(string $binary)
    {
        if (strlen($binary) < 8 || substr($binary, 1, 3) !== 'PNG' || ord($binary[0]) !== 0x89) {
            throw new RuntimeException('O arquivo de assinatura não é um PNG válido.');
        }

        $info = @getimagesizefromstring($binary);

        if (! is_array($info) || ! isset($info[0], $info[1], $info[2]) || $info[2] !== IMAGETYPE_PNG) {
            throw new RuntimeException('O arquivo de assinatura não é um PNG válido.');
        }

        [$width, $height] = $info;

        if ($width < 1 || $height < 1 || $width > self::MAX_IMAGE_SIDE || $height > self::MAX_IMAGE_SIDE) {
            throw new RuntimeException('As dimensões da assinatura são inválidas.');
        }

        $this->guardMemory($width, $height);

        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            throw new RuntimeException('O conteúdo da assinatura não pôde ser decodificado.');
        }

        if (imagesx($image) !== $width || imagesy($image) !== $height) {
            imagedestroy($image);

            throw new RuntimeException('Os dados da assinatura são inconsistentes.');
        }

        $this->assertHasInk($image);

        return $image;
    }

    /**
     * Reprocessa a assinatura em um PNG transparente de dimensões limitadas.
     *
     * @param  resource  $image
     */
    private function normalize($image): string
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $scale = min(1.0, self::MAX_DIMENSION / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $output = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($output === false) {
            throw new RuntimeException('Não foi possível alocar a assinatura.');
        }

        // Canal alfa preservado (fundo transparente) ao redimensionar.
        imagesavealpha($output, true);
        $transparent = imagecolorallocatealpha($output, 255, 255, 255, 127);

        if ($transparent !== false) {
            imagealphablending($output, false);
            imagefilledrectangle($output, 0, 0, $targetWidth, $targetHeight, $transparent);
            imagealphablending($output, true);
        }

        imagesavealpha($image, true);

        // Imagecopyresampled ignora alfa em imagens com pillow edge; usamos
        // TrueColor + imagesavealpha, suficiente para um traço antialiased.
        imagecopyresampled(
            $output,
            $image,
            0, 0, 0, 0,
            $targetWidth, $targetHeight,
            $width, $height
        );

        // O canvas é desenhado sobre fundo branco opaco. Converte o fundo
        // claro em transparência para o traço ser sobreposto ao PDF sem
        // encobrir o texto do contrato (mantém o antialiasing).
        $this->removeLightBackground($output);

        ob_start();
        imagepng($output, null, 9);
        $png = (string) ob_get_clean();

        imagedestroy($output);

        if ($png === '') {
            throw new RuntimeException('A assinatura não pôde ser reprocessada.');
        }

        return $png;
    }

    /**
     * Converte os pixels claros (fundo do canvas) em transparência.
     *
     * @param  resource  $image
     */
    private function removeLightBackground($image): void
    {
        $width = imagesx($image);
        $height = imagesy($image);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;
                $red = ($rgba >> 16) & 0xFF;
                $green = ($rgba >> 8) & 0xFF;
                $blue = $rgba & 0xFF;

                if ($alpha === 127) {
                    continue;
                }

                $brightness = ($red + $green + $blue) / 3;

                if ($brightness > 245) {
                    imagesetpixel($image, $x, $y, imagecolorallocatealpha($image, 255, 255, 255, 127));
                } elseif ($brightness > 110) {
                    // Pixel de borda (antialiasing): dissolve para transparente
                    // proporcionalmente à claridade. 127 = totalmente transparente.
                    $inkAlpha = (int) round((1 - ($brightness - 110) / 135) * 127);
                    imagesetpixel(
                        $image,
                        $x,
                        $y,
                        imagecolorallocatealpha($image, $red, $green, $blue, max(0, min(127, $inkAlpha))),
                    );
                }
            }
        }
    }

    /**
     * Rejeita assinaturas "vazias": canvas limpo gera um retângulo
     * transparente sem tinta — o servidor não confia no navegador.
     *
     * @param  resource  $image
     */
    private function assertHasInk($image): void
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $ink = 0;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;
                $red = ($rgba >> 16) & 0xFF;
                $green = ($rgba >> 8) & 0xFF;
                $blue = $rgba & 0xFF;

                // Tinta: opaca (> translucidez residual) e escura.
                if ($alpha <= 90 && ($red + $green + $blue) < 660) {
                    $ink++;

                    if ($ink >= self::MIN_INK_PIXELS) {
                        return;
                    }
                }
            }
        }

        throw new RuntimeException('A assinatura está vazia.');
    }

    /**
     * Protege contra decompression bombs na assinatura.
     */
    private function guardMemory(int $width, int $height): void
    {
        $limit = ini_get('memory_limit');

        if ($limit === false || $limit === '-1') {
            return;
        }

        $bytes = (int) $limit;

        if (str_ends_with((string) $limit, 'M')) {
            $bytes = (int) rtrim((string) $limit, 'M') * 1024 * 1024;
        } elseif (str_ends_with((string) $limit, 'G')) {
            $bytes = (int) rtrim((string) $limit, 'G') * 1024 * 1024 * 1024;
        } elseif (str_ends_with((string) $limit, 'K')) {
            $bytes = (int) rtrim((string) $limit, 'K') * 1024;
        }

        if ($width * $height * 5 > ($bytes * 0.75)) {
            throw new RuntimeException('A assinatura é grande demais para o servidor.');
        }
    }
}
