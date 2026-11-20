<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // 1. Seed Roles
        $roles = [
            [
                'name'        => 'Human Owner',
                'slug'        => 'owner',
                'description' => 'Tertinggi dalam kepemilikan dan otoritas final perusahaan. Memegang hak veto dan otorisasi Level A.',
                'is_system'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'Executive Board / Rapat Direksi',
                'slug'        => 'executive',
                'description' => 'Continuous Executive Board pemegang kepemimpinan strategis kolektif.',
                'is_system'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'Director AI',
                'slug'        => 'director_ai',
                'description' => 'Koordinator operasional utama AI Workforce yang mengevaluasi dan mendelegasikan tugas.',
                'is_system'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'Division Head',
                'slug'        => 'division_head',
                'description' => 'Pimpinan unit divisi operasional atau teknis.',
                'is_system'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'System Administrator',
                'slug'        => 'admin',
                'description' => 'Pengelola administrasi sistem, akun pengguna, role, dan konfigurasi teknis.',
                'is_system'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'Operations Staff',
                'slug'        => 'operator',
                'description' => 'Operator sistem yang menjalankan pemantauan dan interaksi tugas operasional harian.',
                'is_system'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'Compliance & Security Auditor',
                'slug'        => 'auditor',
                'description' => 'Auditor independen dengan akses baca ke log kepatuhan, keamanan, dan jejak audit.',
                'is_system'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];

        foreach ($roles as $r) {
            $existing = $this->db->table('roles')->where('slug', $r['slug'])->get()->getRow();
            if (!$existing) {
                $this->db->table('roles')->insert($r);
            }
        }

        // 2. Seed Permissions
        $permissions = [
            // User Management
            ['name' => 'View Users', 'slug' => 'users.view', 'module' => 'users', 'description' => 'Melihat daftar dan detail pengguna aplikasi.'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'module' => 'users', 'description' => 'Membuat akun pengguna baru.'],
            ['name' => 'Update Users', 'slug' => 'users.update', 'module' => 'users', 'description' => 'Memperbarui data dan profil pengguna.'],
            ['name' => 'Disable Users', 'slug' => 'users.disable', 'module' => 'users', 'description' => 'Menonaktifkan atau menangguhkan akun pengguna.'],

            // Role & Permission Management
            ['name' => 'View Roles', 'slug' => 'roles.view', 'module' => 'roles', 'description' => 'Melihat daftar role dan permission.'],
            ['name' => 'Assign Roles', 'slug' => 'roles.assign', 'module' => 'roles', 'description' => 'Menugaskan role kepada pengguna.'],
            ['name' => 'View Permissions', 'slug' => 'permissions.view', 'module' => 'roles', 'description' => 'Melihat katalog permission.'],
            ['name' => 'Manage Permissions', 'slug' => 'permissions.manage', 'module' => 'roles', 'description' => 'Mengatur pemetaan permission ke role.'],

            // Audit & Logs
            ['name' => 'View Audit Logs', 'slug' => 'audit.view', 'module' => 'audit', 'description' => 'Melihat log audit dan security events.'],

            // Governance & Company
            ['name' => 'View Governance', 'slug' => 'governance.view', 'module' => 'governance', 'description' => 'Melihat kebijakan tata kelola dan DoA.'],
            ['name' => 'Manage Governance', 'slug' => 'governance.manage', 'module' => 'governance', 'description' => 'Mengatur kebijakan tata kelola dan direktif.'],
            ['name' => 'View Company', 'slug' => 'company.view', 'module' => 'company', 'description' => 'Melihat profil perusahaan dan divisi.'],
            ['name' => 'Update Company', 'slug' => 'company.update', 'module' => 'company', 'description' => 'Memperbarui profil perusahaan.'],

            // Agent Workforce
            ['name' => 'View Agents', 'slug' => 'agents.view', 'module' => 'agents', 'description' => 'Melihat direktori dan status AI agent.'],
            ['name' => 'Manage Agents', 'slug' => 'agents.manage', 'module' => 'agents', 'description' => 'Mengatur parameter dan status AI agent.'],

            // Tasks & Approvals
            ['name' => 'View Tasks', 'slug' => 'tasks.view', 'module' => 'tasks', 'description' => 'Melihat daftar tugas proyek.'],
            ['name' => 'Create Tasks', 'slug' => 'tasks.create', 'module' => 'tasks', 'description' => 'Membuat dan mendistribusikan tugas baru.'],
            ['name' => 'Update Tasks', 'slug' => 'tasks.update', 'module' => 'tasks', 'description' => 'Memperbarui progres dan hasil tugas.'],
            ['name' => 'View Approvals', 'slug' => 'approvals.view', 'module' => 'approvals', 'description' => 'Melihat daftar permohonan persetujuan.'],
            ['name' => 'Decide Approvals', 'slug' => 'approvals.decide', 'module' => 'approvals', 'description' => 'Mengesahkan atau menolak persetujuan (Level A/B/C).'],

            // System Settings
            ['name' => 'View System Settings', 'slug' => 'system.settings.view', 'module' => 'system', 'description' => 'Melihat konfigurasi teknis dan diagnostik sistem.'],
            // Runtime & Model Gateway
            ['name' => 'View Runtime Infrastructure', 'slug' => 'runtime.view', 'module' => 'runtime', 'description' => 'View Hermes runtime, 9Router gateway, execution sessions and tool registry.'],
            ['name' => 'Manage Runtime Infrastructure', 'slug' => 'runtime.manage', 'module' => 'runtime', 'description' => 'Provision, suspend and validate agents; dispatch and cancel executions.'],
            ['name' => 'Invoke Runtime Tools', 'slug' => 'runtime.tools.invoke', 'module' => 'runtime', 'description' => 'Authorize and invoke controlled runtime tools in the execution sandbox.'],
        ];

        foreach ($permissions as $p) {
            $existing = $this->db->table('permissions')->where('slug', $p['slug'])->get()->getRow();
            if (!$existing) {
                $this->db->table('permissions')->insert(array_merge($p, ['created_at' => $now]));
            }
        }

        // Fetch DB IDs for mapping
        $roleMap = [];
        foreach ($this->db->table('roles')->get()->getResult() as $r) {
            $roleMap[$r->slug] = $r->id;
        }

        $permMap = [];
        foreach ($this->db->table('permissions')->get()->getResult() as $p) {
            $permMap[$p->slug] = $p->id;
        }

        // 3. Map Role Permissions
        $rolePermRules = [
            // Owner gets ALL permissions
            'owner' => array_keys($permMap),

            // Admin gets user, role, audit, system, and general views
            'admin' => [
                'users.view', 'users.create', 'users.update', 'users.disable',
                'roles.view', 'roles.assign', 'permissions.view', 'permissions.manage',
                'audit.view', 'system.settings.view', 'company.view', 'agents.view',
                'tasks.view', 'approvals.view',
                'runtime.view', 'runtime.manage', 'runtime.tools.invoke',
            ],

            // Executive gets governance, approvals, company, agent/task views
            'executive' => [
                'company.view', 'company.update', 'governance.view', 'governance.manage',
                'agents.view', 'tasks.view', 'approvals.view', 'approvals.decide', 'audit.view',
            ],

            // Director AI gets agent management, tasks, views
            'director_ai' => [
                'company.view', 'governance.view', 'agents.view', 'agents.manage',
                'tasks.view', 'tasks.create', 'tasks.update', 'approvals.view', 'audit.view',
            ],

            // Division Head gets agent/task handling
            'division_head' => [
                'company.view', 'agents.view', 'tasks.view', 'tasks.create', 'tasks.update', 'approvals.view',
            ],

            // Operator gets task view & update
            'operator' => [
                'company.view', 'agents.view', 'tasks.view', 'tasks.update',
                'runtime.view', 'runtime.manage',
            ],

            // Auditor gets read-only compliance access
            'auditor' => [
                'audit.view', 'users.view', 'roles.view', 'permissions.view',
                'company.view', 'governance.view', 'agents.view', 'tasks.view', 'approvals.view',
            ],
        ];

        foreach ($rolePermRules as $roleSlug => $permSlugs) {
            if (!isset($roleMap[$roleSlug])) continue;
            $roleId = $roleMap[$roleSlug];

            foreach ($permSlugs as $pSlug) {
                if (!isset($permMap[$pSlug])) continue;
                $permId = $permMap[$pSlug];

                $exists = $this->db->table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permId)
                    ->countAllResults();

                if ($exists === 0) {
                    $this->db->table('role_permissions')->insert([
                        'role_id'       => $roleId,
                        'permission_id' => $permId,
                        'created_at'    => $now,
                    ]);
                }
            }
        }

        // 4. Assign Owner User (ID 1) to Owner Role
        $ownerUser = $this->db->table('users')->where('username', 'owner')->get()->getRow();
        if ($ownerUser && isset($roleMap['owner'])) {
            $hasRole = $this->db->table('user_roles')
                ->where('user_id', $ownerUser->id)
                ->where('role_id', $roleMap['owner'])
                ->countAllResults();

            if ($hasRole === 0) {
                $this->db->table('user_roles')->insert([
                    'user_id'    => $ownerUser->id,
                    'role_id'    => $roleMap['owner'],
                    'created_at' => $now,
                ]);
            }
        }

        // 5. Seed Bootstrap Administrator if not exists
        $adminUser = $this->db->table('users')->where('username', 'admin')->get()->getRow();
        if (!$adminUser) {
            $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
            $adminId = $this->db->table('users')->insert([
                'username'      => 'admin',
                'email'         => 'admin@mce.local',
                'password_hash' => password_hash('admin123', $algo),
                'full_name'     => 'System Administrator',
                'role'          => 'admin',
                'status'        => 'active',
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            if (isset($roleMap['admin'])) {
                $this->db->table('user_roles')->insert([
                    'user_id'    => $adminId,
                    'role_id'    => $roleMap['admin'],
                    'created_at' => $now,
                ]);
            }
        }
    }
}
