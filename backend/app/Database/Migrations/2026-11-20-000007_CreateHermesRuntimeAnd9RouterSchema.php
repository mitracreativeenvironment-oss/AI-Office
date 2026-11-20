<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHermesRuntimeAnd9RouterSchema extends Migration
{
    public function up()
    {
        // 1. Runtime Environments Table (Hermes runtime nodes & endpoints)
        $this->forge->addField([
            'id'                     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'env_code'               => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'                   => ['type' => 'VARCHAR', 'constraint' => 150],
            'runtime_type'           => [
                'type'       => 'ENUM',
                'constraint' => ['hermes_docker', 'hermes_k8s', 'hermes_standalone', 'hermes_simulation'],
                'default'    => 'hermes_docker',
            ],
            'base_url'               => ['type' => 'VARCHAR', 'constraint' => 255],
            'api_version'            => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'v1'],
            'status'                 => [
                'type'       => 'ENUM',
                'constraint' => ['connected', 'disconnected', 'degraded', 'simulated', 'maintenance'],
                'default'    => 'simulated',
            ],
            'is_active'              => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'health_last_checked_at' => ['type' => 'DATETIME', 'null' => true],
            'health_details'         => ['type' => 'TEXT', 'null' => true],
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('runtime_type');
        $this->forge->addKey('status');
        $this->forge->createTable('runtime_environments', true);

        // 2. 9Router Model Routing Profiles (Sole model gateway routing rules)
        $this->forge->addField([
            'id'                            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'profile_code'                  => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'                          => ['type' => 'VARCHAR', 'constraint' => 150],
            'description'                   => ['type' => 'TEXT', 'null' => true],
            'primary_model'                 => ['type' => 'VARCHAR', 'constraint' => 100],
            'fallback_model'                => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'gateway_endpoint'              => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => 'http://127.0.0.1:9090/v1'],
            'temperature'                   => ['type' => 'DECIMAL', 'constraint' => '3,2', 'default' => 0.70],
            'max_tokens'                    => ['type' => 'INT', 'default' => 4096],
            'cost_per_1k_input'             => ['type' => 'DECIMAL', 'constraint' => '10,4', 'default' => 0.00],
            'cost_per_1k_output'            => ['type' => 'DECIMAL', 'constraint' => '10,4', 'default' => 0.00],
            'requires_approval_above_tokens'=> ['type' => 'INT', 'default' => 16384],
            'is_active'                     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'                    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'                    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('primary_model');
        $this->forge->createTable('model_routing_profiles', true);

        // 3. Agent Runtime Profiles Table (Hermes initialization & policy configuration)
        $this->forge->addField([
            'id'                        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'profile_code'              => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'                      => ['type' => 'VARCHAR', 'constraint' => 150],
            'description'               => ['type' => 'TEXT', 'null' => true],
            'version'                   => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'v1.0.0'],
            'model_routing_profile_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'execution_limits_json'     => ['type' => 'TEXT', 'null' => true],
            'timeout_seconds'           => ['type' => 'INT', 'default' => 300],
            'concurrency_limit'         => ['type' => 'INT', 'default' => 2],
            'tool_policy_json'          => ['type' => 'TEXT', 'null' => true],
            'memory_policy_json'        => ['type' => 'TEXT', 'null' => true],
            'is_active'                 => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'                => ['type' => 'DATETIME', 'null' => true],
            'updated_at'                => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('model_routing_profile_id', 'model_routing_profiles', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('runtime_profiles', true);

        // 4. Runtime Tools Registry Table (Controlled skill & tool definitions)
        $this->forge->addField([
            'id'                       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tool_code'                => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'                     => ['type' => 'VARCHAR', 'constraint' => 150],
            'category'                 => [
                'type'       => 'ENUM',
                'constraint' => ['coding', 'research', 'analysis', 'filesystem', 'governance', 'hardware', 'system'],
                'default'    => 'research',
            ],
            'description'              => ['type' => 'TEXT'],
            'input_schema_json'        => ['type' => 'TEXT'],
            'output_schema_json'       => ['type' => 'TEXT', 'null' => true],
            'required_permission'      => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'tasks.execute'],
            'risk_level'               => [
                'type'       => 'ENUM',
                'constraint' => ['low', 'medium', 'high', 'critical'],
                'default'    => 'low',
            ],
            'requires_human_approval'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active'                => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'               => ['type' => 'DATETIME', 'null' => true],
            'updated_at'               => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('category');
        $this->forge->addKey('risk_level');
        $this->forge->createTable('runtime_tools_registry', true);

        // 5. Execution Sessions Table (Hermes runtime execution tracking)
        $this->forge->addField([
            'id'                        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'session_uuid'              => ['type' => 'VARCHAR', 'constraint' => 36, 'unique' => true],
            'task_id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'agent_id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'runtime_profile_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'model_routing_profile_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'environment_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'hermes_session_ref'        => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'status'                    => [
                'type'       => 'ENUM',
                'constraint' => ['initialized', 'dispatched', 'running', 'paused', 'completed', 'failed', 'cancelled'],
                'default'    => 'initialized',
            ],
            'dispatch_payload_json'     => ['type' => 'LONGTEXT', 'null' => true],
            'total_tokens_used'         => ['type' => 'INT', 'default' => 0],
            'total_cost_idr'            => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0.00],
            'hermes_result_summary'     => ['type' => 'TEXT', 'null' => true],
            'error_message'             => ['type' => 'TEXT', 'null' => true],
            'dispatched_at'             => ['type' => 'DATETIME', 'null' => true],
            'completed_at'              => ['type' => 'DATETIME', 'null' => true],
            'idempotency_key'           => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'created_at'                => ['type' => 'DATETIME', 'null' => true],
            'updated_at'                => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('task_id');
        $this->forge->addKey('agent_id');
        $this->forge->addKey('status');
        $this->forge->addKey('idempotency_key');
        $this->forge->addForeignKey('task_id', 'workforce_tasks', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('runtime_profile_id', 'runtime_profiles', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('model_routing_profile_id', 'model_routing_profiles', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('environment_id', 'runtime_environments', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('execution_sessions', true);

        // 6. Runtime Execution Events Table (Audit of runtime lifecycle & callbacks)
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'session_id'     => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'task_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'agent_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'event_type'     => [
                'type'       => 'ENUM',
                'constraint' => [
                    'session_initialized', 'session_dispatched', 'runtime_connected',
                    'model_request_started', 'model_request_completed',
                    'tool_invoked', 'tool_completed', 'output_generated',
                    'execution_completed', 'execution_failed', 'execution_cancelled'
                ],
            ],
            'event_source'   => [
                'type'       => 'ENUM',
                'constraint' => ['hermes_runtime', '9router_gateway', 'ci4_control_plane'],
                'default'    => 'hermes_runtime',
            ],
            'payload_json'   => ['type' => 'LONGTEXT', 'null' => true],
            'correlation_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'created_at'     => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('session_id');
        $this->forge->addKey('event_type');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('session_id', 'execution_sessions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('runtime_execution_events', true);

        // 7. Runtime Tool Invocations Table
        $this->forge->addField([
            'id'                    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'session_id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'task_id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'agent_id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tool_id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tool_code'             => ['type' => 'VARCHAR', 'constraint' => 50],
            'status'                => [
                'type'       => 'ENUM',
                'constraint' => ['requested', 'authorized', 'executing', 'completed', 'rejected', 'failed'],
                'default'    => 'requested',
            ],
            'input_payload_json'    => ['type' => 'LONGTEXT', 'null' => true],
            'output_payload_json'   => ['type' => 'LONGTEXT', 'null' => true],
            'execution_duration_ms' => ['type' => 'INT', 'default' => 0],
            'error_details'         => ['type' => 'TEXT', 'null' => true],
            'is_authorized'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'rejection_reason'      => ['type' => 'TEXT', 'null' => true],
            'created_at'            => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('session_id');
        $this->forge->addKey('tool_id');
        $this->forge->addForeignKey('session_id', 'execution_sessions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('tool_id', 'runtime_tools_registry', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('runtime_tool_invocations', true);

        // 8. 9Router Model Requests & Usage Table (Authoritative model accounting)
        $this->forge->addField([
            'id'                 => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'session_id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'task_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'agent_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'correlation_id'     => ['type' => 'VARCHAR', 'constraint' => 64],
            'gateway_endpoint'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'model_route'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'provider'           => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => '9router_multiplexer'],
            'prompt_tokens'      => ['type' => 'INT', 'default' => 0],
            'completion_tokens'  => ['type' => 'INT', 'default' => 0],
            'total_tokens'       => ['type' => 'INT', 'default' => 0],
            'latency_ms'         => ['type' => 'INT', 'default' => 0],
            'estimated_cost_idr' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'status'             => [
                'type'       => 'ENUM',
                'constraint' => ['success', 'timeout', 'rejected', 'failed', 'fallback_used'],
                'default'    => 'success',
            ],
            'fallback_reason'    => ['type' => 'TEXT', 'null' => true],
            'error_message'      => ['type' => 'TEXT', 'null' => true],
            'created_at'         => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('correlation_id');
        $this->forge->addKey('agent_id');
        $this->forge->addKey('model_route');
        $this->forge->createTable('model_requests_usage', true);

        // 9. Runtime Health Snapshots Table (Hermes & 9Router observability)
        $this->forge->addField([
            'id'                      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'component'               => [
                'type'       => 'ENUM',
                'constraint' => ['hermes_runtime', '9router_gateway'],
            ],
            'status'                  => [
                'type'       => 'ENUM',
                'constraint' => ['healthy', 'degraded', 'offline', 'simulated'],
                'default'    => 'simulated',
            ],
            'endpoint_probed'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'latency_ms'              => ['type' => 'INT', 'default' => 0],
            'diagnostic_details_json' => ['type' => 'TEXT', 'null' => true],
            'created_at'              => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['component', 'status']);
        $this->forge->addKey('created_at');
        $this->forge->createTable('runtime_health_snapshots', true);
    }

    public function down()
    {
        $this->forge->dropTable('runtime_health_snapshots', true);
        $this->forge->dropTable('model_requests_usage', true);
        $this->forge->dropTable('runtime_tool_invocations', true);
        $this->forge->dropTable('runtime_execution_events', true);
        $this->forge->dropTable('execution_sessions', true);
        $this->forge->dropTable('runtime_tools_registry', true);
        $this->forge->dropTable('runtime_profiles', true);
        $this->forge->dropTable('model_routing_profiles', true);
        $this->forge->dropTable('runtime_environments', true);
    }
}
