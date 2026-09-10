<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\ClientRegistration;
use Illuminate\View\View;

/**
 * Página inicial da área administrativa: porta de entrada após o login.
 *
 * Apenas agrega dados reais já existentes (nada fictício). Cada rota do
 * método é protegida por autenticação e autorização via Policy.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', ClientRegistration::class);

        $stats = [
            'total' => ClientRegistration::count(),
            'new' => ClientRegistration::query()->where('status', RegistrationStatus::Novo->value)->count(),
            'review' => ClientRegistration::query()->where('status', RegistrationStatus::EmAnalise->value)->count(),
            'approved' => ClientRegistration::query()->where('status', RegistrationStatus::Aprovado->value)->count(),
            'rejected' => ClientRegistration::query()->where('status', RegistrationStatus::Reprovado->value)->count(),
        ];

        $recent = ClientRegistration::query()
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard.index', [
            'stats' => $stats,
            'recent' => $recent,
            'publicUrl' => route('client-registrations.create'),
        ]);
    }
}
