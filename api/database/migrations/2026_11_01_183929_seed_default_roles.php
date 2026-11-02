<?php

use App\Modules\Identity\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role as RoleModel;

/**
 * Role adalah data referensi yang wajib ada di semua environment (AUTH-04), bukan data demo.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (Role::cases() as $role) {
            RoleModel::findOrCreate($role->value, 'web');
        }
    }

    public function down(): void
    {
        RoleModel::whereIn('name', array_column(Role::cases(), 'value'))->delete();
    }
};
