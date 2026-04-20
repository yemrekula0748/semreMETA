<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Cache temizle
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // İzinleri oluştur
        $permissions = [
            'view_dashboard',
            'view_inbox',
            'send_messages',
            'view_accounts',
            'manage_accounts',
            'view_users',
            'manage_users',
            'manage_permissions',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Admin rolü (tüm izinler)
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions($permissions);

        // Staff rolü (izinler yönetici tarafından atanır)
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        // Varsayılan Admin kullanıcısı
        $admin = User::firstOrCreate(
            ['email' => 'admin@semremeta.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Admin123!'),
            ]
        );
        $admin->assignRole('admin');

        $this->command->info('✓ Roller ve izinler oluşturuldu.');
        $this->command->info('✓ Admin kullanıcı: admin@semremeta.com / Admin123!');
        $this->command->warn('⚠ Giriş yaptıktan sonra şifreyi değiştirin!');
    }
}
