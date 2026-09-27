<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (! DB::table('permissions')->where('slug', 'manage_settings')->exists()) {
            DB::table('permissions')->insert([
                'name' => 'Kelola Pengaturan',
                'slug' => 'manage_settings',
                'description' => 'Mengatur identitas pengirim, penawaran default, follow-up, dan blacklist',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionId = DB::table('permissions')->where('slug', 'manage_settings')->value('id');
        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');

        if ($permissionId && $adminRoleId && ! DB::table('permission_role')->where(['permission_id' => $permissionId, 'role_id' => $adminRoleId])->exists()) {
            DB::table('permission_role')->insert(['permission_id' => $permissionId, 'role_id' => $adminRoleId]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('slug', 'manage_settings')->value('id');

        if ($permissionId) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
