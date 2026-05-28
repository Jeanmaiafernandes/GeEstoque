<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — @yield('title', 'Painel')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-slate-800 antialiased">
@php
    $navBase = 'flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition lg:justify-center lg:group-hover:justify-start';
    $navLabel = 'whitespace-nowrap lg:hidden lg:group-hover:inline';
    $navLink = fn (bool $active) => $navBase . ' ' . ($active ? 'bg-teal-900 text-white shadow-sm' : 'text-teal-50 hover:bg-teal-700');
@endphp

<a href="#conteudo" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-teal-700 focus:px-4 focus:py-2 focus:text-white">
    Pular para o conteúdo
</a>

<div class="min-h-full">
    <div id="sidebar-backdrop" class="fixed inset-0 z-20 hidden bg-slate-900/50 lg:hidden"></div>

    <aside id="sidebar"
           class="group fixed inset-y-0 left-0 z-30 flex w-64 -translate-x-full flex-col bg-teal-800 text-teal-50 transition-all duration-200 lg:w-16 lg:translate-x-0 lg:overflow-hidden lg:hover:w-64 lg:focus-within:w-64"
           aria-label="Menu lateral">
        <div class="flex h-14 items-center border-b border-teal-700/60 px-4 lg:justify-center lg:px-0 lg:group-hover:justify-start lg:group-hover:px-4">
            @if (! empty($companyLogo))
                <span class="grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-lg bg-white">
                    <img src="{{ $companyLogo }}" alt="Logo" class="h-9 w-9 object-contain p-0.5">
                </span>
            @else
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-teal-600 text-sm font-bold tracking-tight text-white" aria-hidden="true">Ge</span>
            @endif
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4 text-sm" aria-label="Navegação principal">
            <a href="{{ route('dashboard') }}" class="{{ $navLink(request()->routeIs('dashboard')) }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>
                <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12 11.2 3.05a1.13 1.13 0 0 1 1.6 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>
                <span class="{{ $navLabel }}">Painel</span>
            </a>
            <a href="{{ route('items.index') }}" class="{{ $navLink(request()->routeIs('items.*')) }}" @if (request()->routeIs('items.*')) aria-current="page" @endif>
                <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                <span class="{{ $navLabel }}">Materiais</span>
            </a>
            <a href="{{ route('movements.index') }}" class="{{ $navLink(request()->routeIs('movements.*')) }}" @if (request()->routeIs('movements.*')) aria-current="page" @endif>
                <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                <span class="{{ $navLabel }}">Movimentações</span>
            </a>
            <a href="{{ route('requisitions.index') }}" class="{{ $navLink(request()->routeIs('requisitions.*')) }}" @if (request()->routeIs('requisitions.*')) aria-current="page" @endif>
                <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/></svg>
                <span class="{{ $navLabel }}">Requisições</span>
            </a>
            <a href="{{ route('inventories.index') }}" class="{{ $navLink(request()->routeIs('inventories.*')) }}" @if (request()->routeIs('inventories.*')) aria-current="page" @endif>
                <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/></svg>
                <span class="{{ $navLabel }}">Inventário</span>
            </a>
            <a href="{{ route('reports.index') }}" class="{{ $navLink(request()->routeIs('reports.*')) }}" @if (request()->routeIs('reports.*')) aria-current="page" @endif>
                <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
                <span class="{{ $navLabel }}">Relatórios</span>
            </a>
            <a href="{{ route('labels.index') }}" class="{{ $navLink(request()->routeIs('labels.*')) }}" @if (request()->routeIs('labels.*')) aria-current="page" @endif>
                <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.008v.008H6.75V6.75Zm0 9.75h.008v.008H6.75v-.008Zm9.75-9.75h.008v.008H16.5V6.75Zm-3 6.75h.008v.008H13.5v-.008Zm0 6h.008v.008H13.5v-.008Zm6-6h.008v.008H19.5v-.008Zm0 6h.008v.008H19.5v-.008Zm-3-3h.008v.008H16.5v-.008Z"/></svg>
                <span class="{{ $navLabel }}">Etiquetas</span>
            </a>

            @if (auth()->user()?->isAdmin())
                <p class="px-3 pb-1 pt-5 text-xs font-semibold uppercase tracking-wider text-teal-300 lg:hidden lg:group-hover:block">Administração</p>
                <a href="{{ route('categories.index') }}" class="{{ $navLink(request()->routeIs('categories.*')) }}" @if (request()->routeIs('categories.*')) aria-current="page" @endif>
                    <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/></svg>
                    <span class="{{ $navLabel }}">Categorias</span>
                </a>
                <a href="{{ route('users.index') }}" class="{{ $navLink(request()->routeIs('users.*')) }}" @if (request()->routeIs('users.*')) aria-current="page" @endif>
                    <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                    <span class="{{ $navLabel }}">Usuários</span>
                </a>
            @endif
        </nav>

    </aside>

    <div class="flex min-h-screen flex-col lg:pl-16">
        @php
            $u = auth()->user();
            $initials = collect(explode(' ', trim($u?->name ?? '')))->filter()->take(2)
                ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
            $initials = $initials !== '' ? $initials : '?';
            $notifTotal = ($notifBelowMin ?? 0) + ($notifPendingReq ?? 0);
        @endphp
        <header class="flex h-14 items-center gap-3 bg-gradient-to-r from-teal-800 via-teal-700 to-teal-600 px-4 text-white shadow-sm lg:px-8">
            <button id="sidebar-toggle" type="button" class="rounded-lg p-2 text-white/90 hover:bg-white/10 lg:hidden"
                    aria-label="Abrir menu" aria-controls="sidebar" aria-expanded="false">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
            </button>
            <span class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</span>

            <div class="ml-auto flex items-center gap-1 sm:gap-2">
                {{-- Sino de notificações --}}
                <div class="relative">
                    <button id="notif-btn" type="button" class="relative rounded-lg p-2 text-white/90 hover:bg-white/10" aria-label="Notificações">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                        @if ($notifTotal > 0)
                            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">{{ $notifTotal > 9 ? '9+' : $notifTotal }}</span>
                        @endif
                    </button>
                    <div id="notif-menu" class="header-menu absolute right-0 z-20 mt-2 hidden w-72 rounded-lg bg-white py-2 text-slate-700 shadow-lg ring-1 ring-slate-200">
                        <p class="px-4 pb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Notificações</p>
                        @if (($notifBelowMin ?? 0) > 0)
                            <a href="{{ route('items.index', ['status' => 'below']) }}" class="flex items-center justify-between gap-2 px-4 py-2 text-sm hover:bg-slate-50">
                                <span>Itens abaixo do mínimo</span>
                                <span class="rounded-full bg-amber-100 px-2 text-xs font-semibold text-amber-700">{{ $notifBelowMin }}</span>
                            </a>
                        @endif
                        @if (($notifPendingReq ?? 0) > 0)
                            <a href="{{ route('requisitions.index') }}" class="flex items-center justify-between gap-2 px-4 py-2 text-sm hover:bg-slate-50">
                                <span>Requisições pendentes</span>
                                <span class="rounded-full bg-amber-100 px-2 text-xs font-semibold text-amber-700">{{ $notifPendingReq }}</span>
                            </a>
                        @endif
                        @if ($notifTotal === 0)
                            <p class="px-4 py-2 text-sm text-slate-400">Nenhuma notificação.</p>
                        @endif
                    </div>
                </div>

                {{-- Ajuda --}}
                <a href="{{ route('help') }}" class="rounded-lg p-2 text-white/90 hover:bg-white/10" aria-label="Ajuda" title="Ajuda">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"/></svg>
                </a>

                {{-- Configurações (apenas administradores) --}}
                @if (auth()->user()?->isAdmin())
                    <a href="{{ route('settings.index') }}" class="rounded-lg p-2 text-white/90 hover:bg-white/10" aria-label="Configurações" title="Configurações">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.397-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                    </a>
                @endif

                {{-- Menu do usuário --}}
                <div class="relative">
                    <button id="user-btn" type="button" class="flex items-center gap-2 rounded-lg p-1 pr-2 hover:bg-white/10">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-white/20 text-sm font-semibold text-white">{{ $initials }}</span>
                        <span class="hidden text-sm font-medium sm:block">{{ $u?->name }}</span>
                        <svg class="hidden h-4 w-4 text-white/80 sm:block" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div id="user-menu" class="header-menu absolute right-0 z-20 mt-2 hidden w-60 rounded-lg bg-white py-2 text-slate-700 shadow-lg ring-1 ring-slate-200">
                        <div class="px-4 pb-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Bem-vindo!</p>
                            <p class="mt-0.5 text-sm font-medium text-slate-800">{{ $u?->name }}</p>
                            <p class="text-xs text-slate-500">{{ $u?->role?->label() }}</p>
                        </div>
                        <div class="border-t border-slate-100"></div>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-slate-50">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                            Meu perfil
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                                Sair
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main id="conteudo" class="flex-1 px-4 py-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 flex items-start gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
    (function () {
        const toggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');

        const open = () => { sidebar.classList.remove('-translate-x-full'); backdrop.classList.remove('hidden'); toggle.setAttribute('aria-expanded', 'true'); };
        const close = () => { sidebar.classList.add('-translate-x-full'); backdrop.classList.add('hidden'); toggle.setAttribute('aria-expanded', 'false'); };

        toggle?.addEventListener('click', open);
        backdrop?.addEventListener('click', close);
        sidebar?.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => {
            if (window.innerWidth < 1024) close();
        }));

        // Dropdowns do header (sino e menu do usuário).
        function setupDropdown(btnId, menuId) {
            const btn = document.getElementById(btnId);
            const menu = document.getElementById(menuId);
            if (!btn || !menu) return;
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                document.querySelectorAll('.header-menu').forEach((m) => { if (m !== menu) m.classList.add('hidden'); });
                menu.classList.toggle('hidden');
            });
            menu.addEventListener('click', (e) => e.stopPropagation());
        }
        setupDropdown('notif-btn', 'notif-menu');
        setupDropdown('user-btn', 'user-menu');
        document.addEventListener('click', function () {
            document.querySelectorAll('.header-menu').forEach((m) => m.classList.add('hidden'));
        });
    })();
</script>
</body>
</html>
