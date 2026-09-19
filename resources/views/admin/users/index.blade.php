@extends('layouts.admin')

@section('title', 'Usuários')

@section('content')
    <div class="space-y-6">
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table w-full text-sm">
                    <thead class="bg-surface-850">
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Criado em</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td class="font-medium text-gray-100">{{ $user->name }}</td>
                                <td class="text-zinc-400">{{ $user->email }}</td>
                                <td class="text-zinc-400 whitespace-nowrap">
                                    {{ $user->created_at?->format('d/m/Y') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-5 sm:p-6">
            <h2 class="section-title">Criar usuário</h2>

            <form method="POST" action="{{ route('admin.users.store') }}" class="mt-5 space-y-4">
                @csrf

                <div class="space-y-1.5">
                    <label for="name" class="form-label">Nome</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                        class="form-input" autocomplete="name" required>
                    @error('name')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                        class="form-input" autocomplete="username" required>
                    @error('email')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="password" class="form-label">Senha</label>
                    <input type="password" name="password" id="password"
                        class="form-input" autocomplete="new-password" minlength="8" required>
                    @error('password')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="password_confirmation" class="form-label">Confirmar senha</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                        class="form-input" autocomplete="new-password" minlength="8" required>
                </div>

                <x-button type="submit" class="w-full">Criar usuário</x-button>
            </form>
        </div>
    </div>
@endsection