<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuotaType;
use App\Models\Vehicle;
use App\Models\VehicleQuotaConfiguration;
use App\Services\QuotaAvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function __construct(
        private readonly QuotaAvailabilityService $quotaAvailability,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Vehicle::class);

        $vehicles = Vehicle::query()
            ->with(['quotaConfigurations' => fn ($query) => $query->with('quotaType')])
            ->latest()
            ->get();

        $quotaStats = $vehicles->mapWithKeys(
            fn (Vehicle $vehicle): array => [$vehicle->id => $this->quotaAvailability->statsForVehicle($vehicle)],
        );

        return view('admin.vehicles.index', [
            'vehicles' => $vehicles,
            'quotaStats' => $quotaStats,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Vehicle::class);

        return view('admin.vehicles.create', [
            'quotaTypes' => QuotaType::query()->where('active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Vehicle::class);

        $validated = $request->validate([
            'model' => ['required', 'string', 'max:255'],
            'plate' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9-]+$/', 'unique:vehicles,plate'],
            'active' => ['nullable', 'boolean'],
        ], [
            'plate.regex' => 'A placa contém caracteres inválidos.',
            'plate.unique' => 'Já existe um veículo com esta placa.',
        ]);

        $vehicle = Vehicle::query()->create([
            'model' => $validated['model'],
            'plate' => strtoupper((string) $validated['plate']),
            'active' => (bool) ($validated['active'] ?? true),
        ]);

        if ($request->filled('quota_type_id') && $request->filled('quota_quantity')) {
            $vehicle->quotaConfigurations()->create([
                'quota_type_id' => $request->integer('quota_type_id'),
                'quantity' => max(0, (int) $request->input('quota_quantity')),
                'active' => true,
            ]);
        }

        return redirect()->route('admin.vehicles.index')->with('success', 'Veículo cadastrado com sucesso.');
    }

    public function show(Vehicle $vehicle): View
    {
        $this->authorize('view', $vehicle);

        $vehicle->load(['quotaConfigurations' => fn ($query) => $query->with('quotaType')]);

        $configurationStats = $vehicle->quotaConfigurations->mapWithKeys(
            fn (VehicleQuotaConfiguration $configuration): array => [
                $configuration->id => $this->quotaAvailability->statsForConfiguration($configuration),
            ],
        );

        return view('admin.vehicles.show', [
            'vehicle' => $vehicle,
            'quotaTypes' => QuotaType::query()->where('active', true)->get(),
            'configurationStats' => $configurationStats,
            'vehicleStats' => $this->quotaAvailability->statsForVehicle($vehicle),
        ]);
    }

    public function edit(Vehicle $vehicle): View
    {
        $this->authorize('update', $vehicle);

        return view('admin.vehicles.edit', [
            'vehicle' => $vehicle,
        ]);
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('update', $vehicle);

        $validated = $request->validate([
            'model' => ['required', 'string', 'max:255'],
            'plate' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9-]+$/', Rule::unique('vehicles', 'plate')->ignore($vehicle->id)],
            'active' => ['nullable', 'boolean'],
        ], [
            'plate.regex' => 'A placa contém caracteres inválidos.',
            'plate.unique' => 'Já existe um veículo com esta placa.',
        ]);

        $vehicle->update([
            'model' => $validated['model'],
            'plate' => strtoupper((string) $validated['plate']),
            'active' => (bool) ($validated['active'] ?? $vehicle->active),
        ]);

        return redirect()->route('admin.vehicles.show', $vehicle)->with('success', 'Veículo atualizado com sucesso.');
    }

    public function toggleStatus(Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('update', $vehicle);

        $vehicle->update(['active' => ! $vehicle->active]);

        return back()->with('success', $vehicle->active ? 'Veículo ativado.' : 'Veículo inativado.');
    }

    public function addQuotaConfiguration(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('update', $vehicle);

        $validated = $request->validate([
            'quota_type_id' => ['required', 'exists:quota_types,id'],
            'quantity' => ['required', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        $exists = $vehicle->quotaConfigurations()->where('quota_type_id', $validated['quota_type_id'])->exists();

        if ($exists) {
            return back()->withErrors(['quota_type_id' => 'Este tipo de cota já está configurado para o veículo.']);
        }

        $vehicle->quotaConfigurations()->create([
            'quota_type_id' => $validated['quota_type_id'],
            'quantity' => $validated['quantity'],
            'active' => (bool) ($validated['active'] ?? true),
        ]);

        return back()->with('success', 'Configuração de cota adicionada.');
    }

    public function updateQuotaConfiguration(Request $request, Vehicle $vehicle, VehicleQuotaConfiguration $configuration): RedirectResponse
    {
        $this->authorize('update', $vehicle);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        $peak = $this->quotaAvailability->peakConcurrentReservations($configuration);

        if ((int) $validated['quantity'] < $peak) {
            return back()->withErrors([
                'quantity' => "A quantidade não pode ser menor que o pico de reservas simultâneas ({$peak}).",
            ]);
        }

        $configuration->update([
            'quantity' => $validated['quantity'],
            'active' => (bool) ($validated['active'] ?? $configuration->active),
        ]);

        return back()->with('success', 'Configuração atualizada.');
    }

    public function removeQuotaConfiguration(Vehicle $vehicle, VehicleQuotaConfiguration $configuration): RedirectResponse
    {
        $this->authorize('update', $vehicle);

        if ($this->quotaAvailability->hasActiveReservations($configuration)) {
            $configuration->update(['active' => false]);

            return back()->with('success', 'Configuração desativada porque já existem reservas ativas no período.');
        }

        $configuration->delete();

        return back()->with('success', 'Configuração removida.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('delete', $vehicle);

        if ($vehicle->registrations()->exists()) {
            return back()->withErrors(['vehicle' => 'Este veículo possui histórico e não pode ser excluído fisicamente.']);
        }

        $vehicle->delete();

        return redirect()->route('admin.vehicles.index')->with('success', 'Veículo removido.');
    }
}
