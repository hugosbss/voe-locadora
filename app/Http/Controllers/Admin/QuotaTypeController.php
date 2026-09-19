<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuotaType;
use App\Models\Vehicle;
use App\Models\VehicleQuotaConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuotaTypeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', QuotaType::class);

        $quotaTypes = QuotaType::query()
            ->with(['configurations' => fn ($query) => $query->with('vehicle')])
            ->latest()
            ->get();

        return view('admin.quotas.index', [
            'quotaTypes' => $quotaTypes,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', QuotaType::class);

        return view('admin.quotas.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', QuotaType::class);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:quota_types,code'],
            'name' => ['required', 'string', 'max:255'],
            'days' => ['required', 'integer', 'min:1'],
            'active' => ['nullable', 'boolean'],
        ], [
            'code.unique' => 'Já existe um tipo de cota com este código.',
            'days.min' => 'A quantidade de dias deve ser maior que zero.',
        ]);

        QuotaType::query()->create([
            'code' => strtoupper((string) $validated['code']),
            'name' => $validated['name'],
            'days' => $validated['days'],
            'active' => (bool) ($validated['active'] ?? true),
        ]);

        return redirect()->route('admin.quotas.index')->with('success', 'Tipo de cota cadastrado com sucesso.');
    }

    public function show(QuotaType $quotaType): View
    {
        $this->authorize('view', $quotaType);

        $quotaType->load(['configurations' => fn ($query) => $query->with('vehicle')]);

        $vehicles = Vehicle::query()
            ->where('active', true)
            ->orderBy('model')
            ->get();

        return view('admin.quotas.show', [
            'quotaType' => $quotaType,
            'vehicles' => $vehicles,
        ]);
    }

    public function edit(QuotaType $quotaType): View
    {
        $this->authorize('update', $quotaType);

        return view('admin.quotas.edit', [
            'quotaType' => $quotaType,
        ]);
    }

    public function update(Request $request, QuotaType $quotaType): RedirectResponse
    {
        $this->authorize('update', $quotaType);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('quota_types', 'code')->ignore($quotaType->id)],
            'name' => ['required', 'string', 'max:255'],
            'days' => ['required', 'integer', 'min:1'],
            'active' => ['nullable', 'boolean'],
        ], [
            'code.unique' => 'Já existe um tipo de cota com este código.',
            'days.min' => 'A quantidade de dias deve ser maior que zero.',
        ]);

        $quotaType->update([
            'code' => strtoupper((string) $validated['code']),
            'name' => $validated['name'],
            'days' => $validated['days'],
            'active' => (bool) ($validated['active'] ?? $quotaType->active),
        ]);

        return redirect()->route('admin.quotas.show', $quotaType)->with('success', 'Tipo de cota atualizado com sucesso.');
    }

    public function toggleStatus(QuotaType $quotaType): RedirectResponse
    {
        $this->authorize('update', $quotaType);

        $quotaType->update(['active' => ! $quotaType->active]);

        return back()->with('success', $quotaType->active ? 'Tipo de cota ativado.' : 'Tipo de cota inativado.');
    }

    public function destroy(QuotaType $quotaType): RedirectResponse
    {
        $this->authorize('delete', $quotaType);

        if ($quotaType->registrations()->exists()) {
            $quotaType->update(['active' => false]);

            return redirect()->route('admin.quotas.index')->with('success', 'Tipo de cota inativado porque já existe histórico de vendas associado.');
        }

        $quotaType->delete();

        return redirect()->route('admin.quotas.index')->with('success', 'Tipo de cota removido.');
    }

    public function addVehicle(Request $request, QuotaType $quotaType): RedirectResponse
    {
        $this->authorize('update', $quotaType);

        $validated = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'quantity' => ['required', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        $exists = $quotaType->configurations()->where('vehicle_id', $validated['vehicle_id'])->exists();

        if ($exists) {
            return back()->withErrors(['vehicle_id' => 'Este veículo já está associado a este tipo de cota.']);
        }

        $quotaType->configurations()->create([
            'vehicle_id' => $validated['vehicle_id'],
            'quantity' => $validated['quantity'],
            'active' => (bool) ($validated['active'] ?? true),
        ]);

        return back()->with('success', 'Veículo associado ao tipo de cota.');
    }

    public function updateVehicleConfiguration(Request $request, QuotaType $quotaType, VehicleQuotaConfiguration $configuration): RedirectResponse
    {
        $this->authorize('update', $quotaType);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        $configuration->update([
            'quantity' => $validated['quantity'],
            'active' => (bool) ($validated['active'] ?? $configuration->active),
        ]);

        return back()->with('success', 'Configuração atualizada.');
    }

    public function removeVehicleConfiguration(QuotaType $quotaType, VehicleQuotaConfiguration $configuration): RedirectResponse
    {
        $this->authorize('update', $quotaType);

        if ($configuration->soldCount() > 0) {
            $configuration->update(['active' => false]);

            return back()->with('success', 'Configuração desativada porque já existem vendas registradas.');
        }

        $configuration->delete();

        return back()->with('success', 'Associação removida.');
    }
}
