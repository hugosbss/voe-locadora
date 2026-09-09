<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Responsável por armazenar com segurança as fotos e documentos
 * dos clientes em armazenamento privado (não acessível publicamente).
 */
class DocumentStorageService
{
    public const DISK = 'local';

    /**
     * Armazena um documento em diretório privado e retorna o caminho.
     */
    public function store(UploadedFile $file, string $registrationUuid, string $document): string
    {
        $directory = "cadastros/{$registrationUuid}/{$document}";

        return Storage::disk(self::DISK)->putFile($directory, $file);
    }

    /**
     * Gera um identificador único para o cadastro.
     */
    public function newRegistrationUuid(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Retorna o caminho completo de um documento já armazenado.
     */
    public function fullPath(?string $path): ?string
    {
        return $path ? Storage::disk(self::DISK)->path($path) : null;
    }

    /**
     * Serve o arquivo (exige autenticação para acesso).
     */
    public function response(string $path)
    {
        return Storage::disk(self::DISK)->response($path);
    }

    /**
     * Remove o diretório do cadastro quando o registro for excluído.
     */
    public function deleteRegistrationDirectory(string $registrationUuid): void
    {
        Storage::disk(self::DISK)->deleteDirectory("cadastros/{$registrationUuid}");
    }
}
