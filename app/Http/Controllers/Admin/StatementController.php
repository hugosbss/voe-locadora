<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientRegistration;
use App\Services\ClientRegistrationStatementService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Módulo de Extratos: área central de consulta dos extratos dos clientes.
 *
 * O módulo não possui entidade própria — a listagem reúne os extratos
 * derivados dos cadastros já existentes (apenas os aprovados) e cada item
 * abre exatamente a mesma página de extrato disponível pelo cadastro.
 */
class StatementController extends Controller
{
    public function __construct(
        private readonly ClientRegistrationStatementService $statements,
    ) {}

    /**
     * Lista os extratos dos cadastros aprovados, do mais recente para o mais
     * antigo. A ordenação e a ordenação do histórico usam a mesma data de
     * referência (início da cota; sem período, a data do cadastro).
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ClientRegistration::class);

        $search = $request->query('q');
        $search = is_string($search) ? trim(mb_substr($search, 0, 100)) : '';
        $digits = preg_replace('/\D/', '', $search);

        $statements = $this->statements->approvedStatements()
            ->with(['vehicle', 'quotaType'])
            ->when($search !== '', function ($query) use ($search, $digits): void {
                $query->where(function ($query) use ($search, $digits): void {
                    $query->where('full_name', 'like', '%'.$search.'%');

                    if ($digits !== '') {
                        $query->orWhere('cpf', 'like', '%'.$digits.'%');
                    }
                });
            })
            ->paginate(10)
            ->withQueryString()
            ->through(fn (ClientRegistration $registration): array => $this->statements->summaryFor($registration));

        return view('admin.statements.index', [
            'statements' => $statements,
            'currentSearch' => $search,
            'hasActiveFilters' => $search !== '',
        ]);
    }
}
