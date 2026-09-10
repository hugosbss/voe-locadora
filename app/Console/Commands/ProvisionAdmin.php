<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ProvisionAdmin extends Command
{
    protected $signature = 'admin:provision
        {email? : E-mail do administrador (padrão: .env ADMIN_EMAIL)}
        {--name= : Nome de exibição}
        {--force : Recria a senha mesmo que o usuário já exista}
        {--reset-2fa : Limpa a configuração de 2FA (útil quando códigos de recuperação se esgotam)}';

    protected $description = 'Cria ou atualiza o usuário administrador usando a senha da variável ADMIN_PASSWORD';

    public function handle(): int
    {
        $password = config('admin.password');

        if (! is_string($password) || $password === '') {
            $this->error('ADMIN_PASSWORD não está definida. Configure-a no ambiente antes de executar.');

            return self::FAILURE;
        }

        if (strlen($password) < 12) {
            $this->error('ADMIN_PASSWORD deve ter pelo menos 12 caracteres.');

            return self::FAILURE;
        }

        $defaults = config('admin');
        $email = strtolower($this->argument('email') ?: (string) $defaults['email']);
        $name = $this->option('name') ?: (string) $defaults['name'];

        $user = User::query()->where('email', $email)->first();

        if ($user && ! $this->option('force') && ! $this->option('reset-2fa')) {
            $this->warn("O usuário {$email} já existe. Para reiniciar a senha use --force.");

            return self::SUCCESS;
        }

        $data = ['name' => $name, 'password' => $password];

        if ($user && $this->option('reset-2fa')) {
            $data['two_factor_secret'] = null;
            $data['two_factor_recovery_codes'] = [];
            $data['two_factor_enabled_at'] = null;
        }

        $user = User::query()->updateOrCreate(['email' => $email], $data);

        if ($this->option('reset-2fa')) {
            $this->info('2FA desativado. Solicite a ativação pelo painel de segurança.');
        }

        $this->info("Administrador {$user->email} configurado com sucesso.");

        return self::SUCCESS;
    }
}
