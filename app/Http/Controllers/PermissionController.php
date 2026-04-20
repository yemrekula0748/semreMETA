<?php

namespace App\Http\Controllers;

use App\Models\InstagramAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    private array $allPermissions = [
        'view_dashboard' => 'Dashboard Görüntüle',
        'view_inbox' => 'DM Kutusunu Görüntüle',
        'send_messages' => 'Mesaj Gönder',
        'view_accounts' => 'Instagram Hesaplarını Görüntüle',
        'manage_accounts' => 'Instagram Hesapları Yönet',
        'view_users' => 'Kullanıcıları Görüntüle',
        'manage_users' => 'Kullanıcıları Yönet',
        'manage_permissions' => 'İzinleri Yönet',
    ];

    public function index()
    {
        $users = User::with(['permissions', 'roles', 'instagramAccounts'])
            ->whereHas('roles', fn ($q) => $q->where('name', 'staff'))
            ->orderBy('name')
            ->get();

        $instagramAccounts = InstagramAccount::where('is_active', true)->get();
        $permissions = $this->allPermissions;

        return view('permissions.index', compact('users', 'instagramAccounts', 'permissions'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'instagram_accounts' => ['nullable', 'array'],
            'instagram_accounts.*' => ['integer', 'exists:instagram_accounts,id'],
        ]);

        // Sadece staff kullanıcıların izinleri güncellenir
        if (!$user->hasRole('staff')) {
            return redirect()->route('permissions.index')
                ->with('error', 'Admin kullanıcıların izinleri değiştirilemez.');
        }

        $permissions = $data['permissions'] ?? [];
        $user->syncPermissions($permissions);

        $accountIds = $data['instagram_accounts'] ?? [];
        $user->instagramAccounts()->sync($accountIds);

        return redirect()->route('permissions.index')
            ->with('success', "{$user->name} izinleri güncellendi.");
    }
}
