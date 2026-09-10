<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientRegistration;
use Illuminate\View\View;

/**
 * Rota da ferramenta "Link de cadastro": página exibida ao administrador
 * com o endereço público a ser compartilhado com o cliente.
 *
 * Toda a página exige autenticação e autorização administrativa. O link
 * em si aponta para o formulário público (sem autenticação).
 */
class LinkController extends Controller
{
    public function show(): View
    {
        $this->authorize('viewAny', ClientRegistration::class);

        return view('admin.link.index', [
            'publicUrl' => route('client-registrations.create'),
        ]);
    }
}
