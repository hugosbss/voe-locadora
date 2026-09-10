<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'backup:cadastros
        {--only= : Nome da tabela/filtro opcional do mysqldump}
        {--keep= : Quantidade de backups locais a manter (padrão: config backup.retention)}';

    protected $description = 'Gera um dump MySQL criptografado e o mantém fora do alcance da web';

    public function handle(): int
    {
        $database = config('database.connections.mysql.database');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $mysqldump = (string) config('backup.mysqldump_binary', 'mysqldump');

        $outputDir = storage_path('app/backups');
        $destinationDir = config('backup.destination', $outputDir);

        if (! is_dir($destinationDir) && ! mkdir($destinationDir, 0750, true) && ! is_dir($destinationDir)) {
            $this->error("Não foi possível criar o diretório de backup: {$destinationDir}");

            return self::FAILURE;
        }

        $stamp = now()->format('Y-m-d_H-i-s');
        $dumpPath = $destinationDir.'/cadastros_'.$stamp.'.sql';
        $encryptedPath = $dumpPath.'.enc';

        $dumpCommand = [
            $mysqldump,
            '--host='.$host,
            '--port='.(string) $port,
            '--user='.$username,
            '--password='.$password,
            '--single-transaction',
            '--routines',
            '--triggers',
            $database,
        ];

        if ($only = $this->option('only')) {
            $dumpCommand[] = '--tables';
            $dumpCommand[] = $only;
        }

        $process = new Process($dumpCommand, base_path());

        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('Falha ao gerar o dump: '.$process->getErrorOutput());

            return self::FAILURE;
        }

        $bytes = (int) file_put_contents($dumpPath, $process->getOutput());

        if ($bytes === 0) {
            $this->error('O dump foi gerado vazio — abortando.');

            return self::FAILURE;
        }

        // Backup em repouso: criptografia AES-256-CBC derivada de APP_KEY.
        $key = (string) config('app.key');

        $encrypt = new Process([
            'openssl', 'enc',
            '-aes-256-cbc',
            '-pbkdf2',
            '-salt',
            '-pass', 'pass:'.$key,
            '-in', $dumpPath,
            '-out', $encryptedPath,
        ], base_path());

        $encrypt->run();

        @unlink($dumpPath);

        if (! $encrypt->isSuccessful() || ! is_file($encryptedPath)) {
            $this->error('Falha ao criptografar o backup.');

            return self::FAILURE;
        }

        $this->info("Backup criptografado gerado: {$encryptedPath} (".$this->humanFilesize($bytes).' brutos).');

        $this->rotate($destinationDir);

        return self::SUCCESS;
    }

    private function rotate(string $directory): void
    {
        $keep = (int) ($this->option('keep') ?: config('backup.retention', 14));

        $files = glob($directory.'/*.sql.enc') ?: [];

        usort($files, fn (string $a, string $b) => filemtime($a) <=> filemtime($b));

        $excess = count($files) - $keep;

        for ($i = 0; $i < $excess; $i++) {
            @unlink($files[$i]);
        }
    }

    private function humanFilesize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
