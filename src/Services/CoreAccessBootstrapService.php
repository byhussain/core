<?php

namespace SmartTill\Core\Services;

use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use SmartTill\Core\Models\Permission;
use SmartTill\Core\Models\Role;
use SmartTill\Core\Support\CorePermissionCatalog;

class CoreAccessBootstrapService
{
    public function ensureCoreAccess(): void
    {
        $definitions = $this->getPermissionDefinitions();

        $this->syncPermissionsForPanel('store', $definitions['store'] ?? []);
        $this->ensureSuperAdminRole('store');
    }

    public function assignStoreSuperAdmin(User $user, Store $store): void
    {
        $storeSuperAdminRole = Role::query()
            ->where('name', 'Super Admin')
            ->where('panel', 'store')
            ->whereNull('store_id')
            ->where('is_system', true)
            ->first();

        if (! $storeSuperAdminRole) {
            return;
        }

        if (Schema::hasTable('store_user') && ! DB::table('store_user')
            ->where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->exists()) {
            $storeUserData = [
                'store_id' => $store->id,
                'user_id' => $user->id,
            ];

            if (Schema::hasColumn('store_user', 'cash_in_hand')) {
                $storeUserData['cash_in_hand'] = 0;
            }

            if (Schema::hasColumn('store_user', 'role_id')) {
                $storeUserData['role_id'] = $storeSuperAdminRole->id;
            }

            if (Schema::hasColumn('store_user', 'created_at')) {
                $storeUserData['created_at'] = now();
            }

            if (Schema::hasColumn('store_user', 'updated_at')) {
                $storeUserData['updated_at'] = now();
            }

            DB::table('store_user')->insert($storeUserData);
        }

        if (! DB::table('user_role')
            ->where('user_id', $user->id)
            ->where('role_id', $storeSuperAdminRole->id)
            ->where('store_id', $store->id)
            ->exists()) {
            DB::table('user_role')->insert([
                'user_id' => $user->id,
                'role_id' => $storeSuperAdminRole->id,
                'store_id' => $store->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function syncPermissionsForPanel(string $panel, array $groups): void
    {
        foreach ($groups as $group => $permissions) {
            foreach ($permissions as $permissionData) {
                $name = $permissionData['name'] ?? null;
                if (! is_string($name) || $name === '') {
                    continue;
                }

                Permission::query()->updateOrCreate(
                    ['name' => $name],
                    [
                        'name' => $name,
                        'group' => is_string($group) ? $group : null,
                        'description' => $permissionData['description'] ?? null,
                        'panel' => $panel,
                    ]
                );
            }
        }
    }

    private function ensureSuperAdminRole(string $panel): void
    {
        $role = Role::query()->firstOrCreate(
            [
                'name' => 'Super Admin',
                'panel' => $panel,
                'store_id' => null,
            ],
            [
                'description' => "Super Admin role for {$panel} panel with all permissions",
                'is_system' => true,
            ]
        );

        $permissionIds = Permission::query()
            ->where('panel', $panel)
            ->pluck('id');

        $role->permissions()->sync($permissionIds);
    }

    private function getPermissionDefinitions(): array
    {
        $configPermissions = config('permissions');

        if (is_array($configPermissions) && isset($configPermissions['store']) && is_array($configPermissions['store'])) {
            return [
                'store' => $configPermissions['store'],
                'admin' => is_array($configPermissions['admin'] ?? null) ? $configPermissions['admin'] : [],
            ];
        }

        return CorePermissionCatalog::definitions();
    }
}
