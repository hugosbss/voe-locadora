<?php

namespace App\Console\Commands;

use App\Enums\FacialStatus;
use App\Enums\RegistrationStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ImportLegacySqlite extends Command
{
    protected $signature = 'cadastros:import-legado
        {--connection=sqlite-legacy : Connection SQLite com os dados antigos}
        {--only-latest : Importa somente os registros sem duplicidade de CPF}';

    protected $description = 'Migra os cadastros do SQLite legado para o MySQL, preservando documentos';

    public function handle(): int
    {
        $connection = (string) $this->option('connection');

        try {
            $legacy = DB::connection($connection)->table('client_registrations')->get();
        } catch (RuntimeException $e) {
            $this->error('Não foi possível ler o banco legado: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($legacy->isEmpty()) {
            $this->info('Banco legado vazio.');

            return self::SUCCESS;
        }

        $imported = 0;
        $skipped = 0;

        foreach ($legacy as $row) {
            $existing = DB::table('client_registrations')->where('cpf', $row->cpf)->exists();

            if ($existing) {
                $skipped++;
                $this->warn("[pulado] CPF já presente ({$row->cpf}).");

                continue;
            }

            $normalizedStatus = $this->normalizeStatus($row->status);
            $normalizedFacial = $this->normalizeFacialStatus($row->facial_status);

            DB::table('client_registrations')->insert([
                'id' => (int) $row->id,
                'uuid' => $this->normalizeUuid($row),
                'full_name' => $row->full_name,
                'cpf' => $row->cpf,
                'birth_date' => $row->birth_date,
                'phone' => $row->phone,
                'whatsapp' => $row->whatsapp,
                'email' => $row->email,
                'cep' => $row->cep,
                'address' => $row->address,
                'address_number' => $row->address_number,
                'neighborhood' => $row->neighborhood,
                'city' => $row->city,
                'state' => $row->state,
                'cnh_number' => $row->cnh_number,
                'cnh_category' => $row->cnh_category,
                'cnh_expiry_date' => $row->cnh_expiry_date,
                'cnh_front_path' => $row->cnh_front_path,
                'cnh_back_path' => $row->cnh_back_path,
                'proof_of_residence_path' => $row->proof_of_residence_path,
                'selfie_path' => $row->selfie_path,
                'facial_status' => $normalizedFacial,
                'status' => $normalizedStatus,
                'veracity_declaration_accepted' => (bool) $row->veracity_declaration_accepted,
                'privacy_policy_accepted' => (bool) $row->privacy_policy_accepted,
                // Legado mantido: registra a versão vigente da política e a
                // data do consentimento.
                'privacy_policy_version' => (string) config('privacy.version'),
                'veracity_declaration_accepted_at' => $row->veracity_declaration_accepted ? ($row->updated_at ?? now()) : null,
                'privacy_policy_accepted_at' => $row->privacy_policy_accepted ? ($row->updated_at ?? now()) : null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ]);

            $imported++;
        }

        $this->info("Importação concluída: {$imported} registro(s) importado(s), {$skipped} pulado(s).");

        return self::SUCCESS;
    }

    private function normalizeStatus(mixed $raw): string
    {
        $value = strtolower(trim((string) $raw));

        return RegistrationStatus::tryFrom($value)?->value ?? RegistrationStatus::Novo->value;
    }

    private function normalizeFacialStatus(mixed $raw): string
    {
        $value = strtolower(trim((string) $raw));

        return FacialStatus::tryFrom($value)?->value ?? FacialStatus::Pending->value;
    }

    private function normalizeUuid(object $row): string
    {
        $uuid = trim((string) ($row->uuid ?? ''));

        if (Str::isUuid($uuid)) {
            return $uuid;
        }

        // Registros antigos sem coluna uuid costumam gravar os arquivos
        // em `cadastros/{uuid}/...`: reaproveita o uuid do caminho para
        // manter o vínculo (e o expurgo) íntegro.
        foreach (['cnh_front_path', 'cnh_back_path', 'proof_of_residence_path', 'selfie_path'] as $field) {
            if (preg_match('#^cadastros/([0-9a-f-]{36})/#', (string) ($row->{$field} ?? ''), $matches)) {
                if (Str::isUuid($matches[1])) {
                    return $matches[1];
                }
            }
        }

        return (string) Str::uuid();
    }
}
