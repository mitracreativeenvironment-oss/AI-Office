<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIdentityAndRbacSchema extends Migration
{
    public function up()
    {
        // 1. Extend Users Table with security lifecycle attributes
        $userFields = [
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'locked', 'suspended', 'disabled'],
                'default'    => 'active',
                'after'      => 'role',
            ],
            'failed_login_attempts' => [
                'type'       => 'INT',
                'constraint' => 3,
                'default'    => 0,
                'after'      => 'status',
            ],
            'locked_until' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'after'      => 'failed_login_attempts',
            ],
            'last_login_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'after'      => 'locked_until',
            ],
        ];

        // Add columns if they do not exist
        if (!$this->db->fieldExists('status', 'users')) {
            $this->forge->addColumn('users', $userFields);
        }

        // 2. Roles Table
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'slug'        => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'is_system'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('roles', true);

        // 3. Permissions Table
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'slug'        => ['type' => 'VARCHAR', 'constraint' => 100, 'unique' => true],
            'module'      => ['type' => 'VARCHAR', 'constraint' => 50],
            'description' => ['type' => 'TEXT', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('permissions', true);

        // 4. User Roles Pivot Table
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'role_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'role_id'], 'uk_user_role');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_roles', true);

        // 5. Role Permissions Pivot Table
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'role_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'permission_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['role_id', 'permission_id'], 'uk_role_permission');
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('permission_id', 'permissions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('role_permissions', true);

        // 6. User Tokens Table (Opaque Bearer Tokens hashed with SHA-256)
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'token_hash'   => ['type' => 'VARCHAR', 'constraint' => 64],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'web_session'],
            'ip_address'   => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'expires_at'   => ['type' => 'DATETIME'],
            'last_used_at' => ['type' => 'DATETIME', 'null' => true],
            'is_revoked'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token_hash', 'uk_token_hash');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_tokens', true);

        // 7. Security Events Table
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'event_type'       => ['type' => 'VARCHAR', 'constraint' => 50],
            'actor_identifier' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'ip_address'       => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'           => ['type' => 'ENUM', 'constraint' => ['success', 'failure'], 'default' => 'success'],
            'metadata'         => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('event_type');
        $this->forge->addKey(['user_id', 'created_at']);
        $this->forge->createTable('security_events', true);
    }

    public function down()
    {
        $this->forge->dropTable('security_events', true);
        $this->forge->dropTable('user_tokens', true);
        $this->forge->dropTable('role_permissions', true);
        $this->forge->dropTable('user_roles', true);
        $this->forge->dropTable('permissions', true);
        $this->forge->dropTable('roles', true);

        if ($this->db->fieldExists('status', 'users')) {
            $this->forge->dropColumn('users', ['status', 'failed_login_attempts', 'locked_until', 'last_login_at']);
        }
    }
}
