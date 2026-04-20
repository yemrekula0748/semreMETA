@extends('layouts.app')
@section('title', 'Kullanıcılar')
@section('page-title', 'Kullanıcılar')

@section('header-actions')
    @can('manage_users')
    <button onclick="document.getElementById('addUserModal').classList.remove('hidden')"
            class="flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition-colors shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Kullanıcı Ekle
    </button>
    @endcan
@endsection

@section('content')
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Kullanıcı</th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">E-posta</th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Rol</th>
                    <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Kayıt Tarihi</th>
                    @can('manage_users')
                    <th class="px-6 py-3.5"></th>
                    @endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $user)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-gradient-to-br from-purple-400 to-pink-400 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ $user->name }}</p>
                                @if($user->id === auth()->id())
                                    <span class="text-xs text-purple-600">Sen</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $user->email }}</td>
                    <td class="px-6 py-4">
                        @if($user->hasRole('admin'))
                            <span class="px-2.5 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-semibold">Admin</span>
                        @else
                            <span class="px-2.5 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-semibold">Staff</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-500">{{ $user->created_at->format('d.m.Y') }}</td>
                    @can('manage_users')
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2 justify-end">
                            @if($user->id !== auth()->id())
                            <button onclick="openEditModal({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->email }}', '{{ $user->getRoleNames()->first() }}')"
                                    class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition">
                                Düzenle
                            </button>
                            <form action="{{ route('users.destroy', $user) }}" method="POST"
                                  onsubmit="return confirm('{{ $user->name }} kullanıcısını silmek istediğinizden emin misiniz?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 text-xs border border-red-200 rounded-lg text-red-600 hover:bg-red-50 transition">
                                    Sil
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                    @endcan
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">Henüz kullanıcı yok</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('manage_users')
{{-- Kullanıcı Ekle Modal --}}
<div id="addUserModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Kullanıcı Ekle</h3>
            <button onclick="document.getElementById('addUserModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('users.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Ad Soyad</label>
                <input type="text" name="name" required class="w-full border border-gray-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">E-posta</label>
                <input type="email" name="email" required class="w-full border border-gray-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Şifre</label>
                <input type="password" name="password" required minlength="8" class="w-full border border-gray-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Şifre Tekrar</label>
                <input type="password" name="password_confirmation" required class="w-full border border-gray-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Rol</label>
                <select name="role" class="w-full border border-gray-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')"
                        class="flex-1 border border-gray-200 text-gray-700 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
                    İptal
                </button>
                <button type="submit" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-xl text-sm font-medium transition">
                    Ekle
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Kullanıcı Düzenle Modal --}}
<div id="editUserModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Kullanıcı Düzenle</h3>
            <button onclick="document.getElementById('editUserModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="editUserForm" action="" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Ad Soyad</label>
                <input type="text" name="name" id="editName" required class="w-full border border-gray-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">E-posta</label>
                <input type="email" name="email" id="editEmail" required class="w-full border border-gray-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Yeni Şifre <span class="text-gray-400 font-normal">(boş bırakın = değişmez)</span></label>
                <input type="password" name="password" minlength="8" class="w-full border border-gray-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Şifre Tekrar</label>
                <input type="password" name="password_confirmation" class="w-full border border-gray-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Rol</label>
                <select name="role" id="editRole" class="w-full border border-gray-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')"
                        class="flex-1 border border-gray-200 text-gray-700 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
                    İptal
                </button>
                <button type="submit" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-xl text-sm font-medium transition">
                    Güncelle
                </button>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script>
function openEditModal(id, name, email, role) {
    document.getElementById('editUserForm').action = '/users/' + id;
    document.getElementById('editName').value = name;
    document.getElementById('editEmail').value = email;
    document.getElementById('editRole').value = role;
    document.getElementById('editUserModal').classList.remove('hidden');
}
</script>
@endpush
