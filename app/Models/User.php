<?php

namespace App\Models;

use App\Enums\AdminRole;
use App\Mail\AdminResetPasswordMail;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;

#[Fillable(['name', 'email', 'password', 'role', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_enabled_at'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'array',
            'two_factor_enabled_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === AdminRole::Admin->value;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled_at !== null && $this->two_factor_secret !== null;
    }

    /**
     * Envio do link de recuperação de senha com a identidade visual do VCA.
     */
    public function sendPasswordResetNotification($token): void
    {
        Mail::to($this)->send(new AdminResetPasswordMail($this, $token));
    }
}
