<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleAndAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Permissions
        $permissions = [
            [
                'name' => 'Kelola Leads',
                'slug' => 'manage_leads',
                'description' => 'Mencari bisnis (scraping) dan merayap website untuk email/kontak bisnis 🌐',
            ],
            [
                'name' => 'Kelola Campaign',
                'slug' => 'manage_campaigns',
                'description' => 'Membuat, mengedit, dan mengelola target campaign outreach 🎯',
            ],
            [
                'name' => 'Kelola Template',
                'slug' => 'manage_templates',
                'description' => 'Mengelola master template outreach berdasarkan niche bisnis 📝',
            ],
            [
                'name' => 'Kirim Outreach',
                'slug' => 'send_outreach',
                'description' => 'Mengirim pesan outreach real melalui email SMTP atau WhatsApp ✉️',
            ],
            [
                'name' => 'Kelola Pengguna',
                'slug' => 'manage_users',
                'description' => 'Mengelola pendaftaran akun pengguna, CRUD user sistem 👤',
            ],
            [
                'name' => 'Kelola Role & Akses',
                'slug' => 'manage_roles',
                'description' => 'Mengatur peran (roles) dan perizinan hak wewenang akses peran (permissions) 🛡️',
            ],
        ];

        $permissionInstances = [];
        foreach ($permissions as $perm) {
            $permissionInstances[$perm['slug']] = \App\Models\Permission::firstOrCreate(
                ['slug' => $perm['slug']],
                [
                    'name' => $perm['name'],
                    'description' => $perm['description'],
                ]
            );
        }

        // 2. Create Roles
        $roles = [
            [
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'Full access to system features, role configurations, and user accounts management 👑',
            ],
            [
                'name' => 'User',
                'slug' => 'user',
                'description' => 'Standard access to lead scraping, email campaign pipelines, template builders, and outreach generators 👤',
            ],
        ];

        $roleInstances = [];
        foreach ($roles as $roleData) {
            $roleInstances[$roleData['slug']] = Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                [
                    'name' => $roleData['name'],
                    'description' => $roleData['description'],
                ]
            );
        }

        // Sync permissions with Admin (Admin gets all permissions)
        if (isset($roleInstances['admin'])) {
            $roleInstances['admin']->permissions()->sync(array_values(array_map(fn($inst) => $inst->id, $permissionInstances)));
        }

        // Sync permissions with User (Standard user gets standard access)
        if (isset($roleInstances['user'])) {
            $userPerms = [
                $permissionInstances['manage_leads']->id,
                $permissionInstances['manage_campaigns']->id,
                $permissionInstances['manage_templates']->id,
                $permissionInstances['send_outreach']->id,
            ];
            $roleInstances['user']->permissions()->sync($userPerms);
        }

        // 3. Create Default Admin User
        $adminUser = User::where('email', 'admin@leadhunter.com')->first();
        if (!$adminUser) {
            $adminUser = User::create([
                'name' => 'Admin LeadHunter',
                'email' => 'admin@leadhunter.com',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]);
        }

        // Assign admin role
        $adminUser->assignRole('admin');

        // 4. Assign User role to any existing test users
        $testUser = User::where('email', 'test@example.com')->first();
        if ($testUser) {
            $testUser->assignRole('user');
        }
    }
}
