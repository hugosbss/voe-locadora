<?php

namespace App\Policies;

use App\Models\QuotaType;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuotaTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, QuotaType $quotaType): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, QuotaType $quotaType): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, QuotaType $quotaType): bool
    {
        return $user->isAdmin();
    }
}
