@php
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
            'vehicles' => [
                'label' => 'Veículos',
                'route' => route('admin.vehicles.index'),
                'active' => request()->routeIs('admin.vehicles.*'),
                'icon' => 'M3 15.75V8.25A2.25 2.25 0 0 1 5.25 6h13.5A2.25 2.25 0 0 1 21 8.25v7.5M5.25 15.75h13.5M7.5 10.5h9M8.25 18.75v1.5m7.5-1.5v1.5',
            ],
            'quotas' => [
                'label' => 'Cotas',
                'route' => route('admin.quotas.index'),
                'active' => request()->routeIs('admin.quotas.*'),
                'icon' => 'M8.25 6.75h7.5m-7.5 0a2.25 2.25 0 0 1-2.25-2.25M8.25 6.75a2.25 2.25 0 0 0 2.25 2.25h4.5a2.25 2.25 0 0 0 2.25-2.25M8.25 6.75v10.5m7.5-10.5v10.5m-7.5 0a2.25 2.25 0 0 1-2.25-2.25m9.75 2.25a2.25 2.25 0 0 0 2.25-2.25M3 17.25A2.25 2.25 0 0 1 5.25 15h13.5A2.25 2.25 0 0 1 21 17.25v.75',
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
            // Item "Segurança" temporariamente oculto da interface (sidebar desktop e drawer mobile).
            // A rota/controller/views/permissões continuam intactos — basta descomentar para reexibir.
            // 'security' => [
            //     'label' => 'Segurança',
            //     'route' => route('admin.security.index'),
            //     'active' => request()->routeIs('admin.security.*'),
            //     'icon' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
            // ],
            'users' => [
                'label' => 'Usuários',
                'route' => route('admin.users.index'),
                'active' => request()->routeIs('admin.users.*'),
                'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
            ],
        ],
    ];

@endphp

<ul class="space-y-1">
    @foreach ($groups as $groupLabel => $items)
        <li class="pt-4 first:pt-0">
            <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-zinc-600">{{ $groupLabel }}</p>
            <ul class="space-y-1">
                @foreach ($items as $key => $item)
                    <li>
                        <a
                            href="{{ $item['route'] }}"
                            data-mobile-nav-link
                            @class([
                                'relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors duration-100',
                                'bg-brand text-black shadow-md shadow-black/20' => $item['active'],
                                'text-gray-300 hover:bg-white/5 hover:text-white' => ! $item['active'],
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