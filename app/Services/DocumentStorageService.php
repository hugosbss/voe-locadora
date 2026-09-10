<?php

namespace App\Services;

use App\Models\ClientRegistration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Armazenamento seguro de fotos e documentos em disco privado.
 *
 * Segurança aplicada:
 *  - somente tipos de documento em whitelist;
 *  - validação do conteúdo REAL (finfo + getimagesize + GD), sem confiar
 *    em extensão ou MIME informados pelo cliente;
 *  - reprocessamento da imagem para um novo JPEG neutro, descartando
 *    metadados e payloads inesperados;
 *  - nomes de arquivos e caminhos sempre gerados pelo servidor;
 *  - limites de dimensões e memória para mitigar decompression bombs.
 */
class DocumentStorageService
{
    public const string DISK = 'local';

    /**
     * Retorna o caminho completo (filesystem) de um documento já armazenado.
     */
    public function fullPath(?string $path): ?string
    {
        return $path ? Storage::disk(self::DISK)->path($path) : null;
    }

    /**
     * Confirma que um caminho segue exatamente o formato gerado pelo
     * servidor — jamais controlado pelo cliente.
     */
    public function isSafePath(string $path): bool
    {
        return preg_match('#^cadastros/[0-9a-f-]{36}/[a-z_]+/[A-Za-z0-9]{40}\.jpg$#', $path) === 1;
    }

    /**
     * Serve o arquivo do disco privado (a autorização é responsabilidade
     * do controlador que chama este método).
     */
    public function response(string $path, ?string $name = null, array $headers = [])
    {
        return Storage::disk(self::DISK)->response($path, $name, $headers);
    }

    /**
     * Remove o diretório do cadastro quando o registro for eliminado.
     */
    public function deleteRegistrationDirectory(string $registrationUuid): void
    {
        Storage::disk(self::DISK)->deleteDirectory('cadastros/'.$registrationUuid);
    }

    /**
     * Limites de segurança aplicados no reprocessamento das imagens.
     */
    public const MAX_DIMENSION = 1600;

    public const MAX_IMAGE_SIDE = 8000;

    public const JPEG_QUALITY = 80;

    private const MIME_WHITELIST = [
        'image/jpeg' => true,
        'image/png' => true,
        'image/webp' => true,
    ];

    private const GD_LOADERS = [
        IMAGETYPE_JPEG => 'imagecreatefromjpeg',
        IMAGETYPE_PNG => 'imagecreatefrompng',
        IMAGETYPE_WEBP => 'imagecreatefromwebp',
    ];

    /**
     * Armazena um documento normalizado em diretório privado e retorna o
     * caminho relativo. Nome e caminho são gerados pelo servidor.
     */
    public function store(UploadedFile $file, string $registrationUuid, string $document): string
    {
        if (! array_key_exists($document, ClientRegistration::DOCUMENTS)) {
            throw new RuntimeException("Tipo de documento não permitido: {$document}");
        }

        $jpeg = $this->normalizeImage($file);

        $directory = 'cadastros/'.$registrationUuid.'/'.$document;
        $filename = Str::random(40).'.jpg';

        Storage::disk(self::DISK)->put($directory.'/'.$filename, $jpeg);

        return $directory.'/'.$filename;
    }

    /**
     * Valida o conteúdo real da imagem e devolve uma nova versão segura
     * (JPEG reprocessado, sem metadados EXIF/ICC ou payload adicional).
     */
    private function normalizeImage(UploadedFile $file): string
    {
        $path = $file->isValid() ? $file->getRealPath() : null;

        if (! $path || ! is_file($path)) {
            throw new RuntimeException('Arquivo inválido ou não recebido.');
        }

        // 1) Sniffing do conteúdo real: o MIME informado pelo cliente é ignorado.
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            throw new RuntimeException('O conteúdo do arquivo não pôde ser inspecionado.');
        }

        $detectedMime = finfo_file($finfo, $path);
        finfo_close($finfo);

        if (! $detectedMime || ! isset(self::MIME_WHITELIST[strtolower($detectedMime)])) {
            throw new RuntimeException('O arquivo não é uma imagem válida (JPEG, PNG ou WEBP).');
        }

        // 2) Cabeçalhos da imagem e limites de dimensão (proteção a bombas).
        $info = @getimagesize($path);

        if (! is_array($info) || ! isset($info[0], $info[1], $info[2])) {
            throw new RuntimeException('O arquivo não é uma imagem válida.');
        }

        [$width, $height, $imageType] = $info;

        if ($width < 1 || $height < 1 || $width > self::MAX_IMAGE_SIDE || $height > self::MAX_IMAGE_SIDE) {
            throw new RuntimeException('As dimensões da imagem são inválidas.');
        }

        $this->guardMemory($width, $height);

        // 3) Decodifica com base no conteúdo (GD é tolerante a reencodes;
        //    valida novamente o tipo reportado pelo getimagesize).
        if (! isset(self::GD_LOADERS[$imageType])) {
            throw new RuntimeException('Formato de imagem não permitido.');
        }

        $binary = file_get_contents($path);

        if ($binary === false) {
            throw new RuntimeException('O conteúdo da imagem não pôde ser lido.');
        }

        $source = @imagecreatefromstring($binary);

        if ($source === false) {
            throw new RuntimeException('O conteúdo da imagem não pôde ser decodificado.');
        }

        // Defesa extra: se as dimensões decodificadas divergirem dos
        // cabeçalhos, rejeita (cabeçalho adulterado).
        if (imagesx($source) !== $width || imagesy($source) !== $height) {
            imagedestroy($source);
            throw new RuntimeException('Os dados da imagem são inconsistentes.');
        }

        // Corrige orientação EXIF de JPEGs (conteúdo legitimamente rotacionado).
        if ($imageType === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            [$source, $width, $height] = $this->applyExifOrientation($source, $path, $width, $height);
        }

        // 4) Reprocessamento para um novo JPEG neutro, limitando dimensões.
        $scale = min(1.0, self::MAX_DIMENSION / max($width, $height));
        $targetWidth = (int) round($width * $scale);
        $targetHeight = (int) round($height * $scale);

        $output = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($output === false) {
            imagedestroy($source);
            throw new RuntimeException('Não foi possível alocar a imagem.');
        }

        $white = imagecolorallocate($output, 255, 255, 255);

        if ($white !== false) {
            imagefilledrectangle($output, 0, 0, $targetWidth, $targetHeight, $white);
        }

        imagecopyresampled(
            $output,
            $source,
            0, 0, 0, 0,
            $targetWidth, $targetHeight,
            $width, $height
        );

        ob_start();
        imagejpeg($output, null, self::JPEG_QUALITY);
        $jpeg = (string) ob_get_clean();

        imagedestroy($output);
        imagedestroy($source);

        if ($jpeg === '') {
            throw new RuntimeException('A imagem não pôde ser reprocessada.');
        }

        return $jpeg;
    }

    /**
     * Aplica a rotação EXIF e retorna a nova imagem (ou a original quando
     * não houver orientação para corrigir), junto com as dimensões finais.
     *
     * @param  resource  $image
     * @return array{0: resource, 1: int, 2: int}
     */
    private function applyExifOrientation($image, string $path, int $width, int $height): array
    {
        $degrees = match ((int) ($this->exifOrientation($path) ?? 0)) {
            3, 4 => 180,
            5, 6 => -90,
            7, 8 => 90,
            default => 0,
        };

        if ($degrees === 0) {
            return [$image, $width, $height];
        }

        $rotated = imagerotate($image, $degrees, 0);

        if ($rotated === false) {
            return [$image, $width, $height];
        }

        imagedestroy($image);

        return [$rotated, (int) imagesx($rotated), (int) imagesy($rotated)];
    }

    private function exifOrientation(string $path): ?int
    {
        $exif = @exif_read_data($path);

        if (! is_array($exif) || ! isset($exif['Orientation'])) {
            return null;
        }

        return (int) $exif['Orientation'];
    }

    /**
     * Protege contra decompression bombs: a estimativa de memória deve
     * caber confortavelmente no limite configurado do PHP.
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

        // Estimativa conservadora: ~5 bytes por pixel (RGBA + buffers GD).
        if ($width * $height * 5 > ($bytes * 0.75)) {
            throw new RuntimeException('A imagem é grande demais para o servidor.');
        }
    }
}
