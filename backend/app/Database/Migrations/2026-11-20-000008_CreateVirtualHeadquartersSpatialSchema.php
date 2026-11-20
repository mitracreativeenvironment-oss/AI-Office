<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVirtualHeadquartersSpatialSchema extends Migration
{
    public function up()
    {
        // 1. HQ Buildings
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'building_code'  => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'description'    => ['type' => 'TEXT', 'null' => true],
            'campus_zone'    => [
                'type'       => 'ENUM',
                'constraint' => ['executive_core', 'technology_wing', 'services_pavilion', 'academy_center', 'hardware_labs', 'welfare_commons', 'general_campus'],
                'default'    => 'general_campus'
            ],
            'building_type'  => [
                'type'       => 'ENUM',
                'constraint' => ['tower', 'pavilion', 'hall', 'facility', 'welfare_center'],
                'default'    => 'tower'
            ],
            'pos_x'          => ['type' => 'FLOAT', 'default' => 0.0],
            'pos_y'          => ['type' => 'FLOAT', 'default' => 0.0],
            'pos_z'          => ['type' => 'FLOAT', 'default' => 0.0],
            'width'          => ['type' => 'FLOAT', 'default' => 20.0],
            'depth'          => ['type' => 'FLOAT', 'default' => 16.0],
            'height'         => ['type' => 'FLOAT', 'default' => 12.0],
            'theme_color'    => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => '#1e293b'],
            'accent_color'   => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => '#10b981'],
            'total_floors'   => ['type' => 'INT', 'constraint' => 5, 'default' => 1],
            'is_active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order'     => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('hq_buildings', true);

        // 2. HQ Floors
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'building_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'floor_number'   => ['type' => 'INT', 'constraint' => 5, 'default' => 1],
            'floor_code'     => ['type' => 'VARCHAR', 'constraint' => 50],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'elevation_y'    => ['type' => 'FLOAT', 'default' => 0.0],
            'width'          => ['type' => 'FLOAT', 'default' => 20.0],
            'depth'          => ['type' => 'FLOAT', 'default' => 16.0],
            'is_active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['building_id', 'floor_number']);
        $this->forge->addForeignKey('building_id', 'hq_buildings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('hq_floors', true);

        // 3. HQ Rooms & Functional Zones
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'building_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'floor_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'unit_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'room_code'      => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'category'       => [
                'type'       => 'ENUM',
                'constraint' => [
                    'reception',
                    'executive',
                    'workspace',
                    'collaboration',
                    'project_ops',
                    'monitoring',
                    'welfare_rest',
                    'welfare_learning',
                    'facility'
                ],
                'default'    => 'workspace'
            ],
            'description'    => ['type' => 'TEXT', 'null' => true],
            'pos_x'          => ['type' => 'FLOAT', 'default' => 0.0],
            'pos_y'          => ['type' => 'FLOAT', 'default' => 0.0],
            'pos_z'          => ['type' => 'FLOAT', 'default' => 0.0],
            'width'          => ['type' => 'FLOAT', 'default' => 8.0],
            'depth'          => ['type' => 'FLOAT', 'default' => 6.0],
            'height'         => ['type' => 'FLOAT', 'default' => 4.0],
            'floor_color'    => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => '#334155'],
            'accent_color'   => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => '#38bdf8'],
            'max_capacity'   => ['type' => 'INT', 'constraint' => 5, 'default' => 6],
            'is_active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('building_id', 'hq_buildings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('floor_id', 'hq_floors', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('unit_id', 'organization_units', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('hq_rooms', true);

        // 4. HQ Workstations
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'room_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'workstation_code'  => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'label'             => ['type' => 'VARCHAR', 'constraint' => 100],
            'assigned_agent_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'pos_x'             => ['type' => 'FLOAT', 'default' => 0.0],
            'pos_y'             => ['type' => 'FLOAT', 'default' => 0.0],
            'pos_z'             => ['type' => 'FLOAT', 'default' => 0.0],
            'rotation_y'        => ['type' => 'FLOAT', 'default' => 0.0],
            'equipment_type'    => [
                'type'       => 'ENUM',
                'constraint' => ['executive_desk', 'standard_desk', 'dual_monitor_station', 'collaboration_pod', 'monitoring_console', 'welfare_pod'],
                'default'    => 'standard_desk'
            ],
            'is_active'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('room_id', 'hq_rooms', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('assigned_agent_id', 'ai_agent_registry', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('hq_workstations', true);

        // 5. HQ Camera Navigation Bookmarks
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'bookmark_code' => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'title'         => ['type' => 'VARCHAR', 'constraint' => 100],
            'category'      => [
                'type'       => 'ENUM',
                'constraint' => ['overview', 'building', 'room', 'agent', 'facility'],
                'default'    => 'overview'
            ],
            'target_type'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'target_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'camera_pos_x'  => ['type' => 'FLOAT', 'default' => 0.0],
            'camera_pos_y'  => ['type' => 'FLOAT', 'default' => 15.0],
            'camera_pos_z'  => ['type' => 'FLOAT', 'default' => 25.0],
            'look_at_x'     => ['type' => 'FLOAT', 'default' => 0.0],
            'look_at_y'     => ['type' => 'FLOAT', 'default' => 0.0],
            'look_at_z'     => ['type' => 'FLOAT', 'default' => 0.0],
            'is_default'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'    => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('hq_camera_bookmarks', true);
    }

    public function down()
    {
        $this->forge->dropTable('hq_camera_bookmarks', true);
        $this->forge->dropTable('hq_workstations', true);
        $this->forge->dropTable('hq_rooms', true);
        $this->forge->dropTable('hq_floors', true);
        $this->forge->dropTable('hq_buildings', true);
    }
}
