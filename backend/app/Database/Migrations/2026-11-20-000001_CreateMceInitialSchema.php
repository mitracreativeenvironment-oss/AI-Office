<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMceInitialSchema extends Migration
{
    public function up()
    {
        // 1. Companies Table
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 150],
            'slug'        => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'tagline'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'email'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'phone'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('companies', true);

        // 2. Divisions Table
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 1],
            'key'         => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'label'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'kbli_codes'  => ['type' => 'TEXT', 'null' => true],
            'color'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '#3a86ff'],
            'rug_color'   => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '#2a9d8f'],
            'x0'          => ['type' => 'FLOAT', 'default' => -7.0],
            'x1'          => ['type' => 'FLOAT', 'default' => -1.8],
            'description' => ['type' => 'TEXT', 'null' => true],
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('divisions', true);

        // 3. Agents Table
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'key'            => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'role_title'     => ['type' => 'VARCHAR', 'constraint' => 150],
            'division_key'   => ['type' => 'VARCHAR', 'constraint' => 50],
            'level'          => ['type' => 'ENUM', 'constraint' => ['owner', 'director', 'division_head', 'specialist'], 'default' => 'specialist'],
            'color'          => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '#e4572e'],
            'pos_x'          => ['type' => 'FLOAT', 'default' => 0.0],
            'pos_z'          => ['type' => 'FLOAT', 'default' => 0.0],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'idle'],
            'current_task'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'approval_level' => ['type' => 'ENUM', 'constraint' => ['A', 'B', 'C'], 'default' => 'A'],
            'is_online'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('agents', true);

        // 4. Tasks Table
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'task_code'       => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'title'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'     => ['type' => 'TEXT', 'null' => true],
            'division_key'    => ['type' => 'VARCHAR', 'constraint' => 50],
            'agent_key'       => ['type' => 'VARCHAR', 'constraint' => 50],
            'track'           => ['type' => 'ENUM', 'constraint' => ['project_delivery', 'business_growth'], 'default' => 'business_growth'],
            'priority'        => ['type' => 'ENUM', 'constraint' => ['critical', 'project', 'growth', 'maintenance', 'research'], 'default' => 'growth'],
            'approval_level'  => ['type' => 'ENUM', 'constraint' => ['A', 'B', 'C'], 'default' => 'A'],
            'status'          => ['type' => 'ENUM', 'constraint' => ['draft', 'pending', 'assigned', 'working', 'reviewing', 'waiting_approval', 'completed', 'failed'], 'default' => 'pending'],
            'progress'        => ['type' => 'INT', 'constraint' => 3, 'default' => 0],
            'due_date'        => ['type' => 'DATETIME', 'null' => true],
            'result_summary'  => ['type' => 'TEXT', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tasks', true);

        // 5. Approvals Table
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'task_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'requester_agent'    => ['type' => 'VARCHAR', 'constraint' => 50],
            'title'              => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'        => ['type' => 'TEXT'],
            'reason'             => ['type' => 'TEXT', 'null' => true],
            'risk_level'         => ['type' => 'ENUM', 'constraint' => ['low', 'medium', 'high', 'critical'], 'default' => 'medium'],
            'financial_amount'   => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'status'             => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'rejected'], 'default' => 'pending'],
            'decision_notes'     => ['type' => 'TEXT', 'null' => true],
            'decided_by'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'decided_at'         => ['type' => 'DATETIME', 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('approval_requests', true);

        // 6. Users Table
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'username'      => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 100, 'unique' => true],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'full_name'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'role'          => ['type' => 'ENUM', 'constraint' => ['owner', 'admin', 'supervisor', 'operator', 'auditor'], 'default' => 'admin'],
            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('users', true);

        // 7. Audit Logs Table
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'agent_key'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'action'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'entity_id'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'details'     => ['type' => 'TEXT', 'null' => true],
            'ip_address'  => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('audit_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('audit_logs', true);
        $this->forge->dropTable('users', true);
        $this->forge->dropTable('approval_requests', true);
        $this->forge->dropTable('tasks', true);
        $this->forge->dropTable('agents', true);
        $this->forge->dropTable('divisions', true);
        $this->forge->dropTable('companies', true);
    }
}
