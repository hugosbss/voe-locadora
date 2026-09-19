<?php

namespace App\Enums;

/**
 * Ações registradas na auditoria administrativa.
 *
 * A auditoria é escrita somente pelo AuditService (backend) e nunca aceita
 * valores controlados pelo cliente.
 */
enum AuditAction: string
{
    case ViewRegistration = 'view_registration';
    case ViewDocument = 'view_document';
    case UpdateStatus = 'update_status';
    case UpdateRegistration = 'update_registration';
    case DeleteRegistration = 'delete_registration';
    case LoginSuccess = 'login_success';
    case LoginFailed = 'login_failed';
    case Logout = 'logout';
    case TwoFactorEnabled = 'two_factor_enabled';
    case TwoFactorDisabled = 'two_factor_disabled';
    case TwoFactorRecoveryUsed = 'two_factor_recovery_used';
    case ConsentRecorded = 'consent_recorded';
    case ViewContract = 'view_contract';
    case UserCreated = 'user_created';
    case PasswordReset = 'password_reset';

    public function label(): string
    {
        return match ($this) {
            self::ViewRegistration => 'Visualização de cadastro',
            self::ViewDocument => 'Visualização de documento',
            self::UpdateStatus => 'Alteração de status',
            self::UpdateRegistration => 'Alteração de cadastro',
            self::DeleteRegistration => 'Exclusão de cadastro',
            self::LoginSuccess => 'Login administrativo',
            self::LoginFailed => 'Falha de login administrativo',
            self::Logout => 'Logout administrativo',
            self::TwoFactorEnabled => 'Ativação da verificação em duas etapas',
            self::TwoFactorDisabled => 'Desativação da verificação em duas etapas',
            self::TwoFactorRecoveryUsed => 'Uso de código de recuperação',
            self::ConsentRecorded => 'Consentimento registrado',
            self::ViewContract => 'Visualização de contrato assinado',
            self::UserCreated => 'Criação de usuário administrativo',
            self::PasswordReset => 'Redefinição de senha',
        };
    }
}
