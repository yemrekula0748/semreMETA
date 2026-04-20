<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SemreMETA') — Instagram DM Paneli</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fdf4ff',
                            100: '#fae8ff',
                            500: '#a855f7',
                            600: '#9333ea',
                            700: '#7e22ce',
                            900: '#581c87',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-transition { transition: transform 0.3s ease-in-out; }
    </style>
    @stack('head')
</head>
<body class="bg-gray-50 text-gray-900 antialiased">

<div class="flex h-screen overflow-hidden">

    <!-- Sidebar Overlay (mobile) -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 w-64 bg-gradient-to-b from-purple-900 to-purple-800 text-white flex flex-col z-30 transform -translate-x-full lg:translate-x-0 sidebar-transition">

        <!-- Logo -->
        <div class="p-5 border-b border-purple-700">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-white bg-opacity-20 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                    </svg>
                </div>
                <div>
                    <div class="font-bold text-sm leading-tight">SemreMETA</div>
                    <div class="text-purple-300 text-xs">Instagram DM Panel</div>
                </div>
            </div>
        </div>

        <!-- Nav -->
        <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
            @can('view_dashboard')
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-white bg-opacity-20 text-white' : 'text-purple-200 hover:bg-white hover:bg-opacity-10 hover:text-white' }} transition-colors">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
                Dashboard
            </a>
            @endcan

            @can('view_inbox')
            <a href="{{ route('inbox.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('inbox.*') ? 'bg-white bg-opacity-20 text-white' : 'text-purple-200 hover:bg-white hover:bg-opacity-10 hover:text-white' }} transition-colors">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                DM Kutusu
            </a>
            @endcan

            @can('view_accounts')
            <a href="{{ route('accounts.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('accounts.*') ? 'bg-white bg-opacity-20 text-white' : 'text-purple-200 hover:bg-white hover:bg-opacity-10 hover:text-white' }} transition-colors">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Instagram Hesapları
            </a>
            @endcan

            @can('view_users')
            <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('users.*') ? 'bg-white bg-opacity-20 text-white' : 'text-purple-200 hover:bg-white hover:bg-opacity-10 hover:text-white' }} transition-colors">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                Kullanıcılar
            </a>
            @endcan

            @can('manage_permissions')
            <a href="{{ route('permissions.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('permissions.*') ? 'bg-white bg-opacity-20 text-white' : 'text-purple-200 hover:bg-white hover:bg-opacity-10 hover:text-white' }} transition-colors">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                İzin Yönetimi
            </a>
            @endcan
        </nav>

        <!-- User info -->
        <div class="p-3 border-t border-purple-700">
            <div class="flex items-center gap-3 px-2 py-2">
                <div class="w-8 h-8 bg-purple-600 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-purple-300 truncate">{{ auth()->user()->getRoleNames()->first() }}</p>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Çıkış" class="text-purple-300 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        <!-- Top Bar -->
        <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3 flex-shrink-0">
            <button onclick="toggleSidebar()" class="lg:hidden p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="text-lg font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h1>
            <div class="ml-auto flex items-center gap-2">
                @yield('header-actions')
            </div>
        </header>

        <!-- Alerts -->
        <div class="px-4 pt-3 space-y-2 flex-shrink-0">
            @if(session('success'))
                <div class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                    <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                    <svg class="w-4 h-4 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    {{ session('error') }}
                </div>
            @endif
        </div>

        <!-- Page Content -->
        <main class="flex-1 overflow-auto @yield('no-padding', 'p-4')">
            @yield('content')
        </main>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const isHidden = sidebar.classList.contains('-translate-x-full');
    if (isHidden) {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    } else {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }
}
</script>
@stack('scripts')
</body>
</html>
