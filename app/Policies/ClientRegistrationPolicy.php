<?php

namespace App\Policies;

use App\Enums\AdminRole;
use App\Models\ClientRegistration;
use App\Models\User;

/**
 * Autorização granular sobre recursos administrativos.
 *
 * Princípio: estar autenticado NÃO significa ter autorização. Cada ação
 * administrativa é avaliada por uma ability desta Policy. Hoje somente o
 * papel `admin` está habilitado; novos papéis (ex.: analista) entram em
 * `$allowedRoles` sem reescrever a aplicação.
 */
class ClientRegistrationPolicy
{
    /** @var array<int, AdminRole> */
    private array $allowedRoles = [
        AdminRole::Admin,
    ];

    private function hasAdminRole(User $user): bool
    {
        return in_array(AdminRole::tryFrom($user->role), $this->allowedRoles, true);
    }

    public function viewAny(User $user): bool
    {
        return $this->hasAdminRole($user);
    }

    public function view(User $user, ClientRegistration $registration): bool
    {
        return $this->hasAdminRole($user);
    }

    /**
     * Acesso a um documento específico: a autorização é avaliada em
     * relação ao cadastro ao qual o documento pertence.
     */
    public function viewDocument(User $user, ClientRegistration $registration, string $document): bool
    {
        if (! $this->hasAdminRole($user)) {
            return false;
        }

        return $registration->documentPath($document) !== null;
    }

    public function updateStatus(User $user, ClientRegistration $registration): bool
    {
        return $this->hasAdminRole($user);
    }

    public function delete(User $user, ClientRegistration $registration): bool
    {
        return $this->hasAdminRole($user);
    }
}
