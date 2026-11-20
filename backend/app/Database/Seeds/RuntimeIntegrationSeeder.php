<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RuntimeIntegrationSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // ---------------------------------------------------------------------
        // 1. Runtime Environments
        // ---------------------------------------------------------------------
        $environments = [
            [
                'id'                     => 1,
                'env_code'               => 'ENV-HERMES-LOCAL',
                'name'                   => 'Local Hermes Runtime Node (Workstation)',
                'runtime_type'           => 'hermes_simulation',
                'base_url'               => 'http://127.0.0.1:8686/v1',
                'api_version'            => 'v1',
                'status'                 => 'simulated',
                'is_active'              => 1,
                'health_last_checked_at' => $now,
                'health_details'         => json_encode([
                    'mode'            => 'simulation_ready',
                    'daemon_endpoint' => 'http://127.0.0.1:8686/v1',
                    'probe_status'    => 'adapter_active',
                    'socket_live'     => false,
                    'latency_ms'      => 4,
                ]),
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
            [
                'id'                     => 2,
                'env_code'               => 'ENV-HERMES-PROD',
                'name'                   => 'Production Hermes Cluster (K8s Dedicated Pods)',
                'runtime_type'           => 'hermes_k8s',
                'base_url'               => 'http://hermes-cluster.mce.internal:8686/v1',
                'api_version'            => 'v1',
                'status'                 => 'disconnected',
                'is_active'              => 0,
                'health_last_checked_at' => $now,
                'health_details'         => json_encode([
                    'mode'         => 'awaiting_deployment',
                    'probe_status' => 'standby',
                ]),
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
        ];

        foreach ($environments as $env) {
            $exists = $this->db->table('runtime_environments')->where('env_code', $env['env_code'])->countAllResults();
            if ($exists === 0) {
                $this->db->table('runtime_environments')->insert($env);
            }
        }

        // ---------------------------------------------------------------------
        // 2. 9Router Model Routing Profiles (Sole Gateway)
        // ---------------------------------------------------------------------
        $modelRoutes = [
            [
                'id'                            => 1,
                'profile_code'                  => 'ROUTE-FAST-REASONING',
                'name'                          => 'Fast Operational Reasoning (9Router)',
                'description'                   => 'High-throughput operational triage, routing, and fast classification.',
                'primary_model'                 => 'gemini-2.5-flash',
                'fallback_model'                => 'gpt-4o-mini',
                'gateway_endpoint'              => 'http://127.0.0.1:9090/v1',
                'temperature'                   => 0.40,
                'max_tokens'                    => 4096,
                'cost_per_1k_input'             => 15.00,
                'cost_per_1k_output'            => 45.00,
                'requires_approval_above_tokens'=> 16384,
                'is_active'                     => 1,
                'created_at'                    => $now,
                'updated_at'                    => $now,
            ],
            [
                'id'                            => 2,
                'profile_code'                  => 'ROUTE-DEEP-RESEARCH',
                'name'                          => 'Deep Research & Strategic Synthesis (9Router)',
                'description'                   => 'Comprehensive domain analysis, architecture synthesis, and governance drafting.',
                'primary_model'                 => 'gemini-2.5-pro',
                'fallback_model'                => 'claude-3-5-sonnet',
                'gateway_endpoint'              => 'http://127.0.0.1:9090/v1',
                'temperature'                   => 0.70,
                'max_tokens'                    => 8192,
                'cost_per_1k_input'             => 75.00,
                'cost_per_1k_output'            => 225.00,
                'requires_approval_above_tokens'=> 32768,
                'is_active'                     => 1,
                'created_at'                    => $now,
                'updated_at'                    => $now,
            ],
            [
                'id'                            => 3,
                'profile_code'                  => 'ROUTE-CODE-GEN',
                'name'                          => 'Production Code & Test Harness Generation (9Router)',
                'description'                   => 'Rigorous implementation, unit test writing, and bug reproduction harness.',
                'primary_model'                 => 'claude-3-5-sonnet',
                'fallback_model'                => 'deepseek-v3',
                'gateway_endpoint'              => 'http://127.0.0.1:9090/v1',
                'temperature'                   => 0.20,
                'max_tokens'                    => 8192,
                'cost_per_1k_input'             => 90.00,
                'cost_per_1k_output'            => 270.00,
                'requires_approval_above_tokens'=> 32768,
                'is_active'                     => 1,
                'created_at'                    => $now,
                'updated_at'                    => $now,
            ],
            [
                'id'                            => 4,
                'profile_code'                  => 'ROUTE-HARDWARE-AUDIT',
                'name'                          => 'Hardware Telemetry & Acceleration Audit (9Router)',
                'description'                   => 'Thermal diagnostics, compute cluster scheduling, and edge hardware telemetry parsing.',
                'primary_model'                 => 'gemini-2.5-pro',
                'fallback_model'                => 'deepseek-v3',
                'gateway_endpoint'              => 'http://127.0.0.1:9090/v1',
                'temperature'                   => 0.30,
                'max_tokens'                    => 4096,
                'cost_per_1k_input'             => 60.00,
                'cost_per_1k_output'            => 180.00,
                'requires_approval_above_tokens'=> 16384,
                'is_active'                     => 1,
                'created_at'                    => $now,
                'updated_at'                    => $now,
            ],
            [
                'id'                            => 5,
                'profile_code'                  => 'ROUTE-GOVERNANCE',
                'name'                          => 'Executive Governance & DoA Audit (9Router)',
                'description'                   => 'Continuous Executive Board policy evaluation and compliance verification.',
                'primary_model'                 => 'gemini-2.5-pro',
                'fallback_model'                => 'gpt-4o',
                'gateway_endpoint'              => 'http://127.0.0.1:9090/v1',
                'temperature'                   => 0.20,
                'max_tokens'                    => 8192,
                'cost_per_1k_input'             => 100.00,
                'cost_per_1k_output'            => 300.00,
                'requires_approval_above_tokens'=> 32768,
                'is_active'                     => 1,
                'created_at'                    => $now,
                'updated_at'                    => $now,
            ],
        ];

        foreach ($modelRoutes as $mr) {
            $exists = $this->db->table('model_routing_profiles')->where('profile_code', $mr['profile_code'])->countAllResults();
            if ($exists === 0) {
                $this->db->table('model_routing_profiles')->insert($mr);
            }
        }

        // ---------------------------------------------------------------------
        // 3. Runtime Profiles (Hermes Initialization & Policy)
        // ---------------------------------------------------------------------
        $runtimeProfiles = [
            [
                'id'                       => 1,
                'profile_code'             => 'PROF-EXEC-LEAD',
                'name'                     => 'Executive Leadership & Direction Profile',
                'description'              => 'Runtime profile for Director AI (Level B autonomous executive dispatch).',
                'version'                  => 'v1.0.0',
                'model_routing_profile_id' => 5, // ROUTE-GOVERNANCE
                'execution_limits_json'    => json_encode(['max_tokens' => 8192, 'max_steps' => 20]),
                'timeout_seconds'          => 600,
                'concurrency_limit'        => 4,
                'tool_policy_json'         => json_encode(['allowed_categories' => ['governance', 'research', 'analysis', 'system']]),
                'memory_policy_json'       => json_encode(['isolation' => 'strict_isolated', 'retention_days' => 90]),
                'is_active'                => 1,
                'created_at'               => $now,
                'updated_at'               => $now,
            ],
            [
                'id'                       => 2,
                'profile_code'             => 'PROF-TECH-ARCH',
                'name'                     => 'Technical Architecture & Lead Engineering Profile',
                'description'              => 'Runtime profile for Tech Head AI (System architecture and engineering leadership).',
                'version'                  => 'v1.0.0',
                'model_routing_profile_id' => 2, // ROUTE-DEEP-RESEARCH
                'execution_limits_json'    => json_encode(['max_tokens' => 8192, 'max_steps' => 25]),
                'timeout_seconds'          => 450,
                'concurrency_limit'        => 3,
                'tool_policy_json'         => json_encode(['allowed_categories' => ['coding', 'research', 'analysis', 'filesystem']]),
                'memory_policy_json'       => json_encode(['isolation' => 'strict_isolated', 'retention_days' => 60]),
                'is_active'                => 1,
                'created_at'               => $now,
                'updated_at'               => $now,
            ],
            [
                'id'                       => 3,
                'profile_code'             => 'PROF-DEV-CODER',
                'name'                     => 'Full-Stack Developer & Test Harness Profile',
                'description'              => 'Runtime profile for Dev AI (Implementation, unit testing, and bug fixing).',
                'version'                  => 'v1.0.0',
                'model_routing_profile_id' => 3, // ROUTE-CODE-GEN
                'execution_limits_json'    => json_encode(['max_tokens' => 8192, 'max_steps' => 30]),
                'timeout_seconds'          => 300,
                'concurrency_limit'        => 2,
                'tool_policy_json'         => json_encode(['allowed_categories' => ['coding', 'filesystem', 'analysis']]),
                'memory_policy_json'       => json_encode(['isolation' => 'strict_isolated', 'retention_days' => 30]),
                'is_active'                => 1,
                'created_at'               => $now,
                'updated_at'               => $now,
            ],
            [
                'id'                       => 4,
                'profile_code'             => 'PROF-HW-SPECIALIST',
                'name'                     => 'Hardware Infrastructure & Telemetry Specialist Profile',
                'description'              => 'Runtime profile for Hardware Head AI (Edge hardware, acceleration, temperature).',
                'version'                  => 'v1.0.0',
                'model_routing_profile_id' => 4, // ROUTE-HARDWARE-AUDIT
                'execution_limits_json'    => json_encode(['max_tokens' => 4096, 'max_steps' => 15]),
                'timeout_seconds'          => 300,
                'concurrency_limit'        => 2,
                'tool_policy_json'         => json_encode(['allowed_categories' => ['hardware', 'analysis', 'system']]),
                'memory_policy_json'       => json_encode(['isolation' => 'strict_isolated', 'retention_days' => 45]),
                'is_active'                => 1,
                'created_at'               => $now,
                'updated_at'               => $now,
            ],
            [
                'id'                       => 5,
                'profile_code'             => 'PROF-SERVICES-OPS',
                'name'                     => 'Services & Client Operations Profile',
                'description'              => 'Runtime profile for Services Head AI (B2B workflows, ticketing, operations).',
                'version'                  => 'v1.0.0',
                'model_routing_profile_id' => 1, // ROUTE-FAST-REASONING
                'execution_limits_json'    => json_encode(['max_tokens' => 4096, 'max_steps' => 20]),
                'timeout_seconds'          => 240,
                'concurrency_limit'        => 3,
                'tool_policy_json'         => json_encode(['allowed_categories' => ['research', 'analysis', 'system']]),
                'memory_policy_json'       => json_encode(['isolation' => 'strict_isolated', 'retention_days' => 45]),
                'is_active'                => 1,
                'created_at'               => $now,
                'updated_at'               => $now,
            ],
            [
                'id'                       => 6,
                'profile_code'             => 'PROF-ACADEMY-INSTR',
                'name'                     => 'AI Academy & Education Specialist Profile',
                'description'              => 'Runtime profile for Academy Head AI (Curricula drafting, educational materials).',
                'version'                  => 'v1.0.0',
                'model_routing_profile_id' => 2, // ROUTE-DEEP-RESEARCH
                'execution_limits_json'    => json_encode(['max_tokens' => 8192, 'max_steps' => 20]),
                'timeout_seconds'          => 300,
                'concurrency_limit'        => 2,
                'tool_policy_json'         => json_encode(['allowed_categories' => ['research', 'analysis']]),
                'memory_policy_json'       => json_encode(['isolation' => 'strict_isolated', 'retention_days' => 60]),
                'is_active'                => 1,
                'created_at'               => $now,
                'updated_at'               => $now,
            ],
        ];

        foreach ($runtimeProfiles as $rp) {
            $exists = $this->db->table('runtime_profiles')->where('profile_code', $rp['profile_code'])->countAllResults();
            if ($exists === 0) {
                $this->db->table('runtime_profiles')->insert($rp);
            }
        }

        // ---------------------------------------------------------------------
        // 4. Controlled Tools Registry
        // ---------------------------------------------------------------------
        $tools = [
            [
                'id'                      => 1,
                'tool_code'               => 'TOOL-CODE-DIFF',
                'name'                    => 'Git Workspace Code Diff Inspection',
                'category'                => 'coding',
                'description'             => 'Inspects unified code diffs against local repository working tree.',
                'input_schema_json'       => json_encode(['type' => 'object', 'properties' => ['path' => ['type' => 'string']]]),
                'output_schema_json'      => json_encode(['type' => 'object', 'properties' => ['diff' => ['type' => 'string'], 'files_changed' => ['type' => 'integer']]]),
                'required_permission'     => 'tasks.execute',
                'risk_level'              => 'low',
                'requires_human_approval' => 0,
                'is_active'               => 1,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'id'                      => 2,
                'tool_code'               => 'TOOL-DOC-AUDIT',
                'name'                    => 'MCE Governance Document Compliance Auditor',
                'category'                => 'governance',
                'description'             => 'Cross-checks task objectives against DoA thresholds and Core Values.',
                'input_schema_json'       => json_encode(['type' => 'object', 'properties' => ['document_text' => ['type' => 'string']]]),
                'output_schema_json'      => json_encode(['type' => 'object', 'properties' => ['is_compliant' => ['type' => 'boolean'], 'violations' => ['type' => 'array']]]),
                'required_permission'     => 'governance.view',
                'risk_level'              => 'low',
                'requires_human_approval' => 0,
                'is_active'               => 1,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'id'                      => 3,
                'tool_code'               => 'TOOL-SCHEMA-VALIDATE',
                'name'                    => 'JSON Schema & Data Model Validator',
                'category'                => 'analysis',
                'description'             => 'Validates data structures and input payloads against declared JSON schemas.',
                'input_schema_json'       => json_encode(['type' => 'object', 'properties' => ['schema' => ['type' => 'object'], 'payload' => ['type' => 'object']]]),
                'output_schema_json'      => json_encode(['type' => 'object', 'properties' => ['valid' => ['type' => 'boolean'], 'errors' => ['type' => 'array']]]),
                'required_permission'     => 'tasks.execute',
                'risk_level'              => 'low',
                'requires_human_approval' => 0,
                'is_active'               => 1,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'id'                      => 4,
                'tool_code'               => 'TOOL-METRICS-QUERY',
                'name'                    => 'Operational Workforce Metrics Aggregator',
                'category'                => 'system',
                'description'             => 'Queries system uptime, active sessions, and agent coverage statuses.',
                'input_schema_json'       => json_encode(['type' => 'object', 'properties' => ['metric_type' => ['type' => 'string']]]),
                'output_schema_json'      => json_encode(['type' => 'object', 'properties' => ['metrics' => ['type' => 'object']]]),
                'required_permission'     => 'workforce.view',
                'risk_level'              => 'low',
                'requires_human_approval' => 0,
                'is_active'               => 1,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'id'                      => 5,
                'tool_code'               => 'TOOL-GPU-HEALTH',
                'name'                    => 'Edge GPU Accelerator & Thermal Telemetry Probe',
                'category'                => 'hardware',
                'description'             => 'Reads temperature, VRAM allocation, and tensor core utilization from accelerator node.',
                'input_schema_json'       => json_encode(['type' => 'object', 'properties' => ['node_id' => ['type' => 'string']]]),
                'output_schema_json'      => json_encode(['type' => 'object', 'properties' => ['temp_c' => ['type' => 'number'], 'vram_free_mb' => ['type' => 'number']]]),
                'required_permission'     => 'tasks.execute',
                'risk_level'              => 'medium',
                'requires_human_approval' => 0,
                'is_active'               => 1,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'id'                      => 6,
                'tool_code'               => 'TOOL-DB-MUTATE',
                'name'                    => 'Privileged Database Structure Mutation Gateway',
                'category'                => 'system',
                'description'             => 'Executes controlled schema alterations. Requires strict human Owner approval.',
                'input_schema_json'       => json_encode(['type' => 'object', 'properties' => ['migration_file' => ['type' => 'string']]]),
                'output_schema_json'      => json_encode(['type' => 'object', 'properties' => ['migrated' => ['type' => 'boolean']]]),
                'required_permission'     => 'admin.super',
                'risk_level'              => 'critical',
                'requires_human_approval' => 1,
                'is_active'               => 1,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
        ];

        foreach ($tools as $t) {
            $exists = $this->db->table('runtime_tools_registry')->where('tool_code', $t['tool_code'])->countAllResults();
            if ($exists === 0) {
                $this->db->table('runtime_tools_registry')->insert($t);
            }
        }

        // ---------------------------------------------------------------------
        // 5. Update Runtime Mappings for 6 Verified Workforce Agents
        // ---------------------------------------------------------------------
        $agentMappings = [
            1 => ['inst' => 'hermes-inst-dir-01',  'prof' => 'PROF-EXEC-LEAD'],
            2 => ['inst' => 'hermes-inst-tech-01', 'prof' => 'PROF-TECH-ARCH'],
            3 => ['inst' => 'hermes-inst-srv-01',  'prof' => 'PROF-SERVICES-OPS'],
            4 => ['inst' => 'hermes-inst-acad-01', 'prof' => 'PROF-ACADEMY-INSTR'],
            5 => ['inst' => 'hermes-inst-hw-01',   'prof' => 'PROF-HW-SPECIALIST'],
            6 => ['inst' => 'hermes-inst-dev-01',  'prof' => 'PROF-DEV-CODER'],
        ];

        foreach ($agentMappings as $agentId => $m) {
            $existing = $this->db->table('ai_agent_runtime_mappings')
                ->where('agent_id', $agentId)
                ->get()
                ->getRowArray();

            if ($existing) {
                $this->db->table('ai_agent_runtime_mappings')->where('id', $existing['id'])->update([
                    'provider'                  => 'hermes_simulation',
                    'runtime_profile_reference' => $m['prof'],
                    'runtime_instance_id'       => $m['inst'],
                    'model_gateway_preference'  => '9router',
                    'integration_status'        => 'ready',
                    'notes'                     => 'Runtime mapping verified and linked to Hermes execution engine via 9Router gateway.',
                    'updated_at'                => $now,
                ]);
            } else {
                $this->db->table('ai_agent_runtime_mappings')->insert([
                    'agent_id'                  => $agentId,
                    'provider'                  => 'hermes_simulation',
                    'runtime_profile_reference' => $m['prof'],
                    'runtime_instance_id'       => $m['inst'],
                    'model_gateway_preference'  => '9router',
                    'configuration_version'     => 'v1.0.0',
                    'integration_status'        => 'ready',
                    'effective_start_date'      => $now,
                    'notes'                     => 'Initial runtime mapping for verified agent.',
                    'created_at'                => $now,
                    'updated_at'                => $now,
                ]);
            }
        }

        // ---------------------------------------------------------------------
        // 6. Seed Initial Execution Session for TSK-2026-002 (Hermes Runtime Setup)
        // ---------------------------------------------------------------------
        $sessionUuid = '4a1f6a1e-7b2c-4d3e-8f5a-1b2c3d4e5f60';
        $sessionExists = $this->db->table('execution_sessions')->where('session_uuid', $sessionUuid)->countAllResults();

        if ($sessionExists === 0) {
            $this->db->table('execution_sessions')->insert([
                'session_uuid'             => $sessionUuid,
                'task_id'                  => 2, // TSK-2026-002
                'agent_id'                 => 2, // Tech Head AI
                'runtime_profile_id'       => 2, // PROF-TECH-ARCH
                'model_routing_profile_id' => 2, // ROUTE-DEEP-RESEARCH
                'environment_id'           => 1, // ENV-HERMES-LOCAL
                'hermes_session_ref'       => 'hrm-sess-tech-20261120-001',
                'status'                   => 'completed',
                'dispatch_payload_json'    => json_encode([
                    'task_code'      => 'TSK-2026-002',
                    'objective'      => 'Design and implement Hermes Agent Runtime adapter & 9Router model gateway integration layer.',
                    'allowed_tools'  => ['TOOL-CODE-DIFF', 'TOOL-SCHEMA-VALIDATE'],
                    'model_route'    => 'ROUTE-DEEP-RESEARCH',
                ]),
                'total_tokens_used'        => 3850,
                'total_cost_idr'           => 1155.00,
                'hermes_result_summary'    => 'Hermes runtime adapter and 9Router gateway interface successfully synthesized and validated with zero circular dependencies.',
                'dispatched_at'            => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'completed_at'             => date('Y-m-d H:i:s', strtotime('-1 hour 45 minutes')),
                'idempotency_key'          => hash('sha256', 'TSK-2026-002-TECH-001-DISPATCH'),
                'created_at'               => $now,
                'updated_at'               => $now,
            ]);
            $sessionId = (int)$this->db->insertID();

            // Seed Execution Events
            $events = [
                ['session_initialized', 'ci4_control_plane', 'Session initialized by CI4 task dispatcher.'],
                ['session_dispatched', 'ci4_control_plane', 'Dispatched to Hermes runtime adapter with scoped execution context.'],
                ['runtime_connected', 'hermes_runtime', 'Hermes runtime instance hermes-inst-tech-01 assumed session.'],
                ['model_request_started', '9router_gateway', 'Prompt forwarded to 9Router gateway route ROUTE-DEEP-RESEARCH (gemini-2.5-pro).'],
                ['model_request_completed', '9router_gateway', 'Model response returned via 9Router. Tokens: 3850.'],
                ['tool_invoked', 'hermes_runtime', 'Agent invoked TOOL-CODE-DIFF for workspace verification.'],
                ['tool_completed', 'hermes_runtime', 'TOOL-CODE-DIFF execution completed successfully in 45ms.'],
                ['output_generated', 'hermes_runtime', 'Technical architecture draft artifact generated.'],
                ['execution_completed', 'hermes_runtime', 'Execution session completed with code 0.'],
            ];

            foreach ($events as [$evType, $evSource, $evMsg]) {
                $this->db->table('runtime_execution_events')->insert([
                    'session_id'     => $sessionId,
                    'task_id'        => 2,
                    'agent_id'       => 2,
                    'event_type'     => $evType,
                    'event_source'   => $evSource,
                    'payload_json'   => json_encode(['message' => $evMsg]),
                    'correlation_id' => 'CORR-TECH-20261120-' . substr(md5($evType), 0, 8),
                    'created_at'     => $now,
                ]);
            }

            // Seed Tool Invocation Record
            $this->db->table('runtime_tool_invocations')->insert([
                'session_id'            => $sessionId,
                'task_id'               => 2,
                'agent_id'              => 2,
                'tool_id'               => 1, // TOOL-CODE-DIFF
                'tool_code'             => 'TOOL-CODE-DIFF',
                'status'                => 'completed',
                'input_payload_json'    => json_encode(['path' => 'backend/app/Services/Runtime']),
                'output_payload_json'   => json_encode(['diff' => 'clean', 'files_changed' => 5]),
                'execution_duration_ms' => 45,
                'is_authorized'         => 1,
                'created_at'            => $now,
            ]);

            // Seed 9Router Model Requests Usage
            $this->db->table('model_requests_usage')->insert([
                'session_id'         => $sessionId,
                'task_id'            => 2,
                'agent_id'           => 2,
                'correlation_id'     => '9R-REQ-20261120-TECH01',
                'gateway_endpoint'   => 'http://127.0.0.1:9090/v1',
                'model_route'        => 'ROUTE-DEEP-RESEARCH',
                'provider'           => '9router_multiplexer',
                'prompt_tokens'      => 1250,
                'completion_tokens'  => 2600,
                'total_tokens'       => 3850,
                'latency_ms'         => 820,
                'estimated_cost_idr' => 1155.00,
                'status'             => 'success',
                'created_at'         => $now,
            ]);
        }

        // ---------------------------------------------------------------------
        // 7. Initial Runtime Health Snapshot
        // ---------------------------------------------------------------------
        $this->db->table('runtime_health_snapshots')->insert([
            'component'               => 'hermes_runtime',
            'status'                  => 'simulated',
            'endpoint_probed'         => 'http://127.0.0.1:8686/v1/health',
            'latency_ms'              => 4,
            'diagnostic_details_json' => json_encode([
                'adapter_mode'   => 'simulation_fallback',
                'connected'      => false,
                'active_workers' => 6,
                'notes'          => 'Hermes runtime adapter active; daemon fallback ready.',
            ]),
            'created_at'              => $now,
        ]);

        $this->db->table('runtime_health_snapshots')->insert([
            'component'               => '9router_gateway',
            'status'                  => 'simulated',
            'endpoint_probed'         => 'http://127.0.0.1:9090/v1/health',
            'latency_ms'              => 2,
            'diagnostic_details_json' => json_encode([
                'adapter_mode'      => 'simulation_fallback',
                'active_routes'     => 5,
                'sole_gateway_rule' => 'ENFORCED',
                'direct_bypass'     => 'BLOCKED',
                'notes'             => '9Router gateway adapter active; model routing enforced.',
            ]),
            'created_at'              => $now,
        ]);
    }
}
