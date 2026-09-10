<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro imutável de auditoria administrativa.
 *
 * Todos os campos são controlados pelo servidor (AuditService). Não existe
 * rota que aceite escrita de auditoria; usuários comuns não possuem qualquer
 * caminho de alteração sobre estes registros.
 */
class AdminAuditLog extends Model
{
    /** @use HasFactory<AdminAuditLog> */
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(ClientRegistration::class);
    }
}
