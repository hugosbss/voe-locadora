<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminRole;
use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gestão dos usuários administrativos.
 *
 * Novos usuários são sempre criados com o papel `admin` (não há seleção de
 * papel). A página fica restrita ao painel e exige autenticação.
 */
class UserController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * Lista os usuários administrativos e exibe o formulário de criação.
     */
    public function index(): View
    {
        $this->authorize('manage-users');

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'created_at'])
            ->each(fn (User $user) => $user->setHidden(['two_factor_secret', 'two_factor_recovery_codes']));

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    /**
     * Cria um novo usuário administrativo (papel admin fixo).
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-users');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => $validated['password'],
            'role' => AdminRole::Admin->value,
        ]);

        $this->audit->log(
            AuditAction::UserCreated,
            ['user_id' => $user->id, 'name' => $user->name],
            null,
            $request->user(),
            $request,
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuário criado com sucesso.');
    }
}
