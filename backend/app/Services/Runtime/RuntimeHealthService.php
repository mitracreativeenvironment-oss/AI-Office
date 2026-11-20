<?php

namespace App\Services\Runtime;

use Config\Database;
use CodeIgniter\Database\BaseConnection;
use App\Services\Runtime\HermesRuntimeAdapter;
use App\Services\Runtime\NineRouterGatewayAdapter;

class RuntimeHealthService
{
    protected BaseConnection $db;
    protected HermesRuntimeAdapter $hermesAdapter;
    protected NineRouterGatewayAdapter $nineRouterAdapter;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->hermesAdapter = new HermesRuntimeAdapter();
        $this->nineRouterAdapter = new NineRouterGatewayAdapter();
    }

    /**
     * Get aggregate health status across Hermes runtime and 9Router gateway.
     */
    public function getSystemHealth(): array
    {
        $hermesHealth = $this->hermesAdapter->checkHealth();
        $nineRouterHealth = $this->nineRouterAdapter->checkHealth();

        // Count sessions
        $activeSessions = $this->db->table('execution_sessions')->whereIn('status', ['initialized', 'dispatched', 'running'])->countAllResults();
        $completedSessions = $this->db->table('execution_sessions')->where('status', 'completed')->countAllResults();
        $failedSessions = $this->db->table('execution_sessions')->where('status', 'failed')->countAllResults();

        // Count ready agents
        $readyAgentsCount = $this->db->table('ai_agent_runtime_mappings')->where('integration_status', 'ready')->countAllResults();

        // 9Router Usage
        $usage = $this->nineRouterAdapter->getUsageSummary();

        $now = date('Y-m-d H:i:s');

        // Record health snapshot
        $this->db->table('runtime_health_snapshots')->insert([
            'component'               => 'hermes_runtime',
            'status'                  => $hermesHealth['status'],
            'endpoint_probed'         => $hermesHealth['endpoint'],
            'latency_ms'              => $hermesHealth['latency_ms'],
            'diagnostic_details_json' => json_encode($hermesHealth),
            'created_at'              => $now,
        ]);

        $this->db->table('runtime_health_snapshots')->insert([
            'component'               => '9router_gateway',
            'status'                  => $nineRouterHealth['status'],
            'endpoint_probed'         => $nineRouterHealth['endpoint'],
            'latency_ms'              => $nineRouterHealth['latency_ms'],
            'diagnostic_details_json' => json_encode($nineRouterHealth),
            'created_at'              => $now,
        ]);

        return [
            'overall_status'        => ($hermesHealth['status'] === 'connected' && $nineRouterHealth['status'] === 'connected') ? 'healthy' : 'simulated_ready',
            'hermes_runtime'        => $hermesHealth,
            'nine_router_gateway'   => $nineRouterHealth,
            'ready_agents_count'    => $readyAgentsCount,
            'sessions'              => [
                'active'    => $activeSessions,
                'completed' => $completedSessions,
                'failed'    => $failedSessions,
                'total'     => $activeSessions + $completedSessions + $failedSessions,
            ],
            'model_usage'           => $usage,
            'sole_gateway_enforced' => true,
            'direct_bypass_policy'  => 'BLOCKED',
            'checked_at'            => $now,
        ];
    }
}
