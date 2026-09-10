<?php

namespace App\Console\Commands;

use App\Services\RetentionService;
use Illuminate\Console\Command;

class PurgeExpiredRegistrations extends Command
{
    protected $signature = 'cadastros:expurgo
        {--dry-run : Apenas lista o que seria excluído, sem apagar}';

    protected $description = 'Remove cadastros cujo prazo de retenção (por status) já expirou, incluindo documentos e auditoria';

    public function handle(RetentionService $retention): int
    {
        $expired = $retention->expired();
        $dryRun = (bool) $this->option('dry-run');

        if ($expired->isEmpty()) {
            $this->info('Nenhum cadastro expirou a retenção.');

            return self::SUCCESS;
        }

        $this->info("{$expired->count()} cadastro(s) elegível(eis) à exclusão.");

        foreach ($expired as $registration) {
            if ($dryRun) {
                $days = $retention->retentionDays($registration->status);
                $this->warn(
                    "[dry-run] Cadastro #{$registration->id} — {$registration->full_name} — {$registration->status->value} +{$days} dias: seria excluído"
                );

                continue;
            }

            $retention->purge($registration);
            $this->info("[expurgado] Cadastro #{$registration->id} removido.");
        }

        return self::SUCCESS;
    }
}
