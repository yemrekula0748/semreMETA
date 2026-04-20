@extends('layouts.app')
@section('title', 'İzin Yönetimi')
@section('page-title', 'İzin Yönetimi')

@section('content')
<div class="space-y-4">

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p>Bu sayfada <strong>Staff</strong> rolündeki kullanıcıların neye erişebileceğini ve hangi Instagram hesaplarını görebileceğini ayarlayabilirsiniz. Admin kullanıcılar her şeye erişebilir.</p>
        </div>
    </div>

    @if($users->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 p-12 text-center text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <p>Henüz staff kullanıcı yok. Önce Kullanıcılar sayfasından bir staff ekleyin.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($users as $user)
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                {{-- User Header --}}
                <div class="px-5 py-4 bg-gray-50 border-b border-gray-200 flex items-center gap-3">
                    <div class="w-9 h-9 bg-gradient-to-br from-purple-400 to-pink-400 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">{{ $user->name }}</p>
                        <p class="text-xs text-gray-500">{{ $user->email }}</p>
                    </div>
                </div>

                <form action="{{ route('permissions.update', $user) }}" method="POST" class="p-5 space-y-5">
                    @csrf
                    @method('PUT')

                    {{-- Sayfa İzinleri --}}
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Sayfa Erişim İzinleri</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach($permissions as $permName => $permLabel)
                            <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition-colors has-[:checked]:border-purple-300 has-[:checked]:bg-purple-50">
                                <input type="checkbox"
                                       name="permissions[]"
                                       value="{{ $permName }}"
                                       {{ $user->hasPermissionTo($permName) ? 'checked' : '' }}
                                       class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                <span class="text-sm text-gray-700">{{ $permLabel }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Instagram Hesap Erişimi --}}
                    @if($instagramAccounts->isNotEmpty())
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Erişebileceği Instagram Hesapları</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach($instagramAccounts as $account)
                            <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition-colors has-[:checked]:border-purple-300 has-[:checked]:bg-purple-50">
                                <input type="checkbox"
                                       name="instagram_accounts[]"
                                       value="{{ $account->id }}"
                                       {{ $user->instagramAccounts->contains($account->id) ? 'checked' : '' }}
                                       class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                <div class="flex items-center gap-2 min-w-0">
                                    @if($account->profile_picture_url)
                                        <img src="{{ $account->profile_picture_url }}" class="w-6 h-6 rounded-full object-cover flex-shrink-0" alt="">
                                    @endif
                                    <span class="text-sm text-gray-700 truncate">{{ $account->name }}</span>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="flex justify-end pt-1">
                        <button type="submit"
                                class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2.5 rounded-xl text-sm font-medium transition-colors shadow-sm">
                            Kaydet
                        </button>
                    </div>
                </form>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
