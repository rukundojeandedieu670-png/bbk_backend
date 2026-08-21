<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'manage-users',
        'manage-system-settings',
        'manage-hubs',
        'manage-partners',
        'manage-programs',
        'manage-events',
        'manage-stories',
        'manage-news',
        'manage-media',
        'manage-inbox',
        'review-content',
        'publish-content',
        'view-audit-log',
    ];

    public function run(): void
    {
        $permissions = collect(self::PERMISSIONS)
            ->mapWithKeys(fn (string $name): array => [
                $name => Permission::findOrCreate($name, 'web'),
            ]);

        $owner = Role::findOrCreate('system-owner', 'web');
        $admin = Role::findOrCreate('admin', 'web');
        $publisher = Role::findOrCreate('publisher', 'web');

        $owner->syncPermissions($permissions->all());
        $admin->syncPermissions($permissions->only([
            'manage-hubs',
            'manage-partners',
            'manage-programs',
            'manage-events',
            'manage-stories',
            'manage-news',
            'manage-media',
            'manage-inbox',
            'review-content',
        ])->all());
        $publisher->syncPermissions($permissions->only([
            'manage-inbox',
            'review-content',
            'publish-content',
        ])->all());

        $this->seedUser('BBK_OWNER_EMAIL', 'BBK_OWNER_PASSWORD', 'BBK_OWNER_NAME', 'BBK System Owner', $owner);
        $this->seedUser('BBK_ADMIN_EMAIL', 'BBK_ADMIN_PASSWORD', 'BBK_ADMIN_NAME', 'BBK Content Admin', $admin);
        $this->seedUser('BBK_PUBLISHER_EMAIL', 'BBK_PUBLISHER_PASSWORD', 'BBK_PUBLISHER_NAME', 'BBK Publisher', $publisher);
    }

    private function seedUser(string $emailKey, string $passwordKey, string $nameKey, string $defaultName, Role $role): void
    {
        $email = env($emailKey);
        $password = env($passwordKey);

        if (! $email || ! $password) {
            return;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env($nameKey, $defaultName),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles([$role]);
    }
}