@extends('layouts.app')
@section('title', 'Instagram Hesapları')
@section('page-title', 'Instagram Hesapları')

@section('header-actions')
    @can('manage_accounts')
    <a href="{{ route('accounts.connect') }}"
       class="flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition-colors shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Hesap Bağla
    </a>
    @endcan
@endsection

@section('content')
<div class="space-y-4">

    {{-- Bilgi kutusu --}}
    @can('manage_accounts')
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <p class="font-semibold">Hesap Nasıl Bağlanır?</p>
                <p class="mt-1 text-blue-700">"Hesap Bağla" butonuna tıklayarak Facebook oturumunузla giriş yapın. Instagram Business hesabınızın bağlı olduğu Facebook sayfasına erişim verin. Sistem otomatik olarak Instagram hesabınızı bulacaktır.</p>
            </div>
        </div>
    </div>
    @endcan

    @if($accounts->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 p-16 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069z"/></svg>
            <p class="text-gray-500 font-medium">Henüz Instagram hesabı bağlanmamış</p>
            @can('manage_accounts')
            <a href="{{ route('accounts.connect') }}" class="mt-4 inline-flex items-center gap-2 bg-purple-600 text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-purple-700 transition">
                İlk Hesabı Bağla
            </a>
            @endcan
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($accounts as $account)
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-4 flex items-center gap-3">
                    @if($account->profile_picture_url)
                        <img src="{{ $account->profile_picture_url }}" class="w-14 h-14 rounded-full border-2 border-white object-cover" alt="">
                    @else
                        <div class="w-14 h-14 bg-white bg-opacity-20 rounded-full flex items-center justify-center text-white text-2xl font-bold border-2 border-white">
                            {{ strtoupper(substr($account->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-white truncate">{{ $account->name }}</p>
                        @if($account->username)
                        <p class="text-purple-200 text-sm">@{{ $account->username }}</p>
                        @endif
                    </div>
                </div>

                {{-- Stats --}}
                <div class="px-4 py-3 grid grid-cols-2 gap-3 border-b border-gray-100">
                    <div class="text-center">
                        <p class="text-xl font-bold text-gray-900">{{ $account->conversations_count }}</p>
                        <p class="text-xs text-gray-500">Konuşma</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-bold text-gray-900">{{ $account->users_count }}</p>
                        <p class="text-xs text-gray-500">Yetkili</p>
                    </div>
                </div>

                {{-- Status + Actions --}}
                <div class="px-4 py-3 flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-sm {{ $account->is_active ? 'text-green-600' : 'text-gray-400' }}">
                        <span class="w-2 h-2 rounded-full {{ $account->is_active ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                        {{ $account->is_active ? 'Aktif' : 'Pasif' }}
                    </span>
                    @can('manage_accounts')
                    <div class="flex gap-2">
                        @if($account->is_active)
                        <form action="{{ route('accounts.disconnect', $account) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs px-3 py-1.5 border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition">
                                Duraklat
                            </button>
                        </form>
                        @else
                        <form action="{{ route('accounts.activate', $account) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs px-3 py-1.5 border border-green-200 rounded-lg text-green-600 hover:bg-green-50 transition">
                                Aktifleştir
                            </button>
                        </form>
                        @endif
                        <form action="{{ route('accounts.destroy', $account) }}" method="POST"
                              onsubmit="return confirm('{{ $account->name }} hesabını silmek istediğinizden emin misiniz? Tüm konuşmalar da silinir.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs px-3 py-1.5 border border-red-200 rounded-lg text-red-600 hover:bg-red-50 transition">
                                Sil
                            </button>
                        </form>
                    </div>
                    @endcan
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
