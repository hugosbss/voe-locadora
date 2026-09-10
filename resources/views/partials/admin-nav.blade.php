@php
    use Illuminate\Support\Facades\Auth;

    $groups = [
        'Principal' => [
            'dashboard' => [
                'label' => 'Dashboard',
                'route' => route('admin.dashboard'),
                'active' => request()->routeIs('admin.dashboard'),
                'icon' => 'M2.25 12 21.75 3l-4.5 18-4.5-6-5.25-2.25L9 10.5 2.25 12Z',
            ],
        ],
        'Cadastros' => [
            'registrations' => [
                'label' => 'Cadastros',
                'route' => route('admin.registrations.index'),
                'active' => request()->routeIs('admin.registrations.*'),
                'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12H3m15 0h-3.75M12 15.75v1.5m-3.75-1.5v1.5m6-1.5v1.5M7.5 18h9m-10.5-6H21M3 18V6.75a2.25 2.25 0 0 1 2.25-2.25h9',
            ],
        ],
        'Ferramentas' => [
            'link' => [
                'label' => 'Link de cadastro',
                'route' => route('admin.registration-link'),
                'active' => request()->routeIs('admin.registration-link'),
                'icon' => 'M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244',
            ],
        ],
        'Sistema' => [
            'security' => [
                'label' => 'Segurança',
                'route' => route('admin.security.index'),
                'active' => request()->routeIs('admin.security.*'),
                'icon' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
            ],
        ],
    ];

    $logoutRoute = route('admin.logout');
@endphp

<ul class="space-y-1">
    @foreach ($groups as $groupLabel => $items)
        <li class="pt-4 first:pt-0">
            <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ $groupLabel }}</p>
            <ul class="space-y-1">
                @foreach ($items as $key => $item)
                    <li>
                        <a
                            href="{{ $item['route'] }}"
                            data-mobile-nav-link
                            @class([
                                'relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors duration-100',
                                'bg-gradient-to-r from-indigo-500 to-sky-500 text-white shadow-md shadow-indigo-950/30' => $item['active'],
                                'text-slate-300 hover:bg-slate-800 hover:text-white' => ! $item['active'],
                            ])
                            @if ($item['active']) aria-current="page" @endif>
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                            </svg>
                            <span class="truncate">{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </li>
    @endforeach
</ul>

@if (Auth::check())
    <div class="pt-5">
        <form method="POST" action="{{ $logoutRoute }}">
            @csrf
            <button
                type="submit"
                class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-400 transition-colors duration-100 hover:bg-slate-800 hover:text-slate-200">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>Sair</span>
            </button>
        </form>
    </div>
@endif