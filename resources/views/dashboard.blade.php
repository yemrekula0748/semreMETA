@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">

    <!-- İstatistik Kartları -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Hesaplar</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['total_accounts'] }}</p>
                </div>
                <div class="w-11 h-11 bg-purple-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0 2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073z"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Konuşmalar</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['total_conversations'] }}</p>
                </div>
                <div class="w-11 h-11 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Okunmamış</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['unread_messages'] }}</p>
                </div>
                <div class="w-11 h-11 bg-red-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Bugün Mesaj</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['today_messages'] }}</p>
                </div>
                <div class="w-11 h-11 bg-green-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Son Konuşmalar -->
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Son Konuşmalar</h2>
                @can('view_inbox')
                <a href="{{ route('inbox.index') }}" class="text-sm text-purple-600 hover:text-purple-700 font-medium">Tümünü Gör →</a>
                @endcan
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($recentConversations as $conv)
                <a href="{{ route('inbox.show', $conv) }}" class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50 transition-colors">
                    <div class="relative flex-shrink-0">
                        @if($conv->participant_profile_pic)
                            <img src="{{ $conv->participant_profile_pic }}" class="w-10 h-10 rounded-full object-cover" alt="">
                        @else
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-pink-400 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                {{ strtoupper(substr($conv->display_name, 0, 1)) }}
                            </div>
                        @endif
                        @if($conv->unread_count > 0)
                            <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 rounded-full text-white text-xs flex items-center justify-center font-bold">{{ min($conv->unread_count, 9) }}</span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $conv->display_name }}</p>
                        <p class="text-xs text-gray-500 truncate">
                            @if($conv->latestMessage)
                                @if($conv->latestMessage->message_type === 'image') 📷 Resim
                                @else {{ $conv->latestMessage->message_text }}
                                @endif
                            @endif
                        </p>
                    </div>
                    <div class="text-xs text-gray-400 flex-shrink-0">
                        {{ $conv->last_message_at?->diffForHumans() }}
                    </div>
                </a>
                @empty
                <div class="px-5 py-8 text-center text-gray-400 text-sm">
                    Henüz konuşma yok
                </div>
                @endforelse
            </div>
        </div>

        <!-- Bağlı Hesaplar -->
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Bağlı Hesaplar</h2>
                @can('manage_accounts')
                <a href="{{ route('accounts.index') }}" class="text-sm text-purple-600 hover:text-purple-700 font-medium">Yönet →</a>
                @endcan
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($accounts as $account)
                <div class="flex items-center gap-3 px-5 py-3.5">
                    @if($account->profile_picture_url)
                        <img src="{{ $account->profile_picture_url }}" class="w-10 h-10 rounded-full object-cover" alt="">
                    @else
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                            {{ strtoupper(substr($account->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">{{ $account->name }}</p>
                        @if($account->username)
                        <p class="text-xs text-gray-500">@{{ $account->username }}</p>
                        @endif
                    </div>
                    <span class="w-2 h-2 rounded-full {{ $account->is_active ? 'bg-green-400' : 'bg-gray-300' }}"></span>
                </div>
                @empty
                <div class="px-5 py-8 text-center text-gray-400 text-sm">
                    Henüz hesap bağlanmamış
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
