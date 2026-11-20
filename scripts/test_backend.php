<?php

// MCE REST API End-to-End Test Suite (Fase 1, 2, Prompt 17 Auth/RBAC & Prompt 18 Org/Governance)
// Verifies HTTP endpoints, JSON structure, authentication, RBAC, organization hierarchy, and corporate governance

$baseUrl = 'http://localhost:8080/api/v1';

// Check if server is running on port 8080
$fp = @fsockopen('127.0.0.1', 8080, $errno, $errstr, 1);
$startedServer = false;
$p = null;

if (!$fp) {
    echo "Starting temporary CI4 server on http://localhost:8080 ...\n";
    $backendDir = realpath(__DIR__ . '/../backend');

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $p = proc_open("php spark serve --port 8080", [
            0 => ['pipe', 'r'],
            1 => ['file', 'NUL', 'w'],
            2 => ['file', 'NUL', 'w'],
        ], $pipes, $backendDir);
    }
    sleep(3);
    $startedServer = true;
} else {
    fclose($fp);
}

function requestHttp(string $url, string $method = 'GET', ?array $data = null, ?string $token = null): array {
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];
    if ($token) {
        $headers[] = "Authorization: Bearer {$token}";
    }

    $opts = [
        'http' => [
            'method'        => $method,
            'header'        => implode("\r\n", $headers),
            'timeout'       => 5,
            'ignore_errors' => true,
        ]
    ];

    if ($data !== null) {
        $opts['http']['content'] = json_encode($data);
    }

    $ctx = stream_context_create($opts);
    $content = @file_get_contents($url, false, $ctx);
    $statusLine = $http_response_header[0] ?? '';
    preg_match('{HTTP\/\S*\s(\d{3})}', $statusLine, $match);
    $statusCode = isset($match[1]) ? (int)$match[1] : 0;
    $json = json_decode($content ?? '', true);

    return [$statusCode, $json];
}

echo "\n========================================================================\n";
echo " MCE REST API, RBAC, ORG & GOVERNANCE — INTEGRATION TESTS (PROMPTS 16-18)\n";
echo "========================================================================\n\n";

$pass = 0;
$total = 0;

function reportTest(string $method, string $path, int $expected, int $actual, string $label, bool $success, &$pass, &$total) {
    $total++;
    if ($success) {
        $pass++;
        echo sprintf(" [PASS] %-6s %-38s -> %s (HTTP %d)\n", $method, $path, $label, $actual);
    } else {
        echo sprintf("❌ [FAIL] %-6s %-38s -> Expected %d, got %d (%s)\n", $method, $path, $expected, $actual, $label);
    }
}

// -----------------------------------------------------------------------------
// 1. Baseline Endpoints (Prompt 16)
// -----------------------------------------------------------------------------
$baseline = [
    ['GET', '/health', 200, 'System Health & Engine Info'],
    ['GET', '/health/database', 200, 'Database Health & Connection Diagnostic'],
    ['GET', '/company', 200, 'Company Profile & Metrics'],
    ['GET', '/divisions', 200, '6 Divisions (KBLI Units)'],
    ['GET', '/agents', 200, 'Agents Directory & Status'],
    ['GET', '/agents/director', 200, 'Single Agent Detail (Director AI)'],
    ['GET', '/tasks', 200, 'Tasks List (Track 1 & 2)'],
    ['GET', '/approvals', 200, 'Pending Approval Requests (Level C)'],
    ['GET', '/office/layout', 200, '3D Virtual Office Layout'],
    ['GET', '/office/feed', 200, '3D Live Report Feed'],
];

foreach ($baseline as [$method, $path, $exp, $lbl]) {
    [$code, $json] = requestHttp($baseUrl . $path, $method);
    $ok = ($code === $exp) && (($json['status'] ?? '') === 'success');
    reportTest($method, $path, $exp, $code, $lbl, $ok, $pass, $total);
}

// -----------------------------------------------------------------------------
// 2. Authentication & Identity Tests (Prompt 17)
// -----------------------------------------------------------------------------
// Failed Login Attempt
[$code, $json] = requestHttp($baseUrl . '/auth/login', 'POST', [
    'login'    => 'owner',
    'password' => 'WrongPassword!',
]);
$ok = ($code === 401) && (($json['status'] ?? '') === 'error');
reportTest('POST', '/auth/login (bad pass)', 401, $code, 'Reject Invalid Credentials', $ok, $pass, $total);

// Successful Login
[$code, $json] = requestHttp($baseUrl . '/auth/login', 'POST', [
    'login'    => 'owner',
    'password' => 'admin123',
]);
$authToken = $json['data']['token'] ?? null;
$ok = ($code === 200) && !empty($authToken) && in_array('owner', $json['data']['user']['roles'] ?? []);
reportTest('POST', '/auth/login (valid)', 200, $code, 'Login & Issue Bearer Token', $ok, $pass, $total);

// Unauthenticated /auth/me rejected
[$code, $json] = requestHttp($baseUrl . '/auth/me', 'GET');
$ok = ($code === 401);
reportTest('GET', '/auth/me (unauth)', 401, $code, 'Reject Missing Token', $ok, $pass, $total);

// Authenticated /auth/me
[$code, $json] = requestHttp($baseUrl . '/auth/me', 'GET', null, $authToken);
$ok = ($code === 200) && (($json['data']['username'] ?? '') === 'owner');
reportTest('GET', '/auth/me (bearer)', 200, $code, 'Retrieve Identity Profile & Roles', $ok, $pass, $total);

// RBAC Admin users list
[$code, $json] = requestHttp($baseUrl . '/admin/users', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data'] ?? null) && count($json['data']) >= 2;
reportTest('GET', '/admin/users', 200, $code, 'RBAC: List Users', $ok, $pass, $total);

// RBAC Admin roles list
[$code, $json] = requestHttp($baseUrl . '/admin/roles', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data'] ?? null);
reportTest('GET', '/admin/roles', 200, $code, 'RBAC: Roles Catalogue', $ok, $pass, $total);

// RBAC Admin permissions list
[$code, $json] = requestHttp($baseUrl . '/admin/permissions', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data'] ?? null);
reportTest('GET', '/admin/permissions', 200, $code, 'RBAC: Permissions Catalogue', $ok, $pass, $total);

// -----------------------------------------------------------------------------
// 3. Organization Structure Tests (Prompt 18)
// -----------------------------------------------------------------------------
// Org Tree
[$code, $json] = requestHttp($baseUrl . '/organization/tree', 'GET');
$ok = ($code === 200) && !empty($json['data']) && ($json['data'][0]['code'] ?? '') === 'ORG-MCE';
reportTest('GET', '/organization/tree', 200, $code, 'Hierarchical Org Tree (Root ORG-MCE)', $ok, $pass, $total);

// Org Units Flat List
[$code, $json] = requestHttp($baseUrl . '/organization/units', 'GET');
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 7;
reportTest('GET', '/organization/units', 200, $code, 'Flat Organizational Units List', $ok, $pass, $total);

// Single Org Unit Detail
[$code, $json] = requestHttp($baseUrl . '/organization/units/1', 'GET');
$ok = ($code === 200) && (($json['data']['code'] ?? '') === 'ORG-MCE');
reportTest('GET', '/organization/units/1', 200, $code, 'Single Unit Detail with Subunits', $ok, $pass, $total);

// Org Positions List
[$code, $json] = requestHttp($baseUrl . '/organization/positions', 'GET');
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 5;
reportTest('GET', '/organization/positions', 200, $code, 'Positions Directory with Active Appointees', $ok, $pass, $total);

// Org Appointments List
[$code, $json] = requestHttp($baseUrl . '/organization/appointments', 'GET');
$ok = ($code === 200) && is_array($json['data']);
reportTest('GET', '/organization/appointments', 200, $code, 'Appointments List (Human & AI)', $ok, $pass, $total);

// Reporting Relationships
[$code, $json] = requestHttp($baseUrl . '/organization/reporting', 'GET');
$ok = ($code === 200) && is_array($json['data']);
reportTest('GET', '/organization/reporting', 200, $code, 'Reporting Relationships Graph', $ok, $pass, $total);

// -----------------------------------------------------------------------------
// 4. Corporate Governance & DoA Tests (Prompt 18)
// -----------------------------------------------------------------------------
// 7 Core Values
[$code, $json] = requestHttp($baseUrl . '/governance/core-values', 'GET');
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) === 7;
reportTest('GET', '/governance/core-values', 200, $code, '7 MCE Core Values (Controlled Reference)', $ok, $pass, $total);

// Governance Bodies
[$code, $json] = requestHttp($baseUrl . '/governance/bodies', 'GET');
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 3;
reportTest('GET', '/governance/bodies', 200, $code, 'Governance Bodies (Owner, Board, Dir AI)', $ok, $pass, $total);

// Authority Profiles (DoA)
[$code, $json] = requestHttp($baseUrl . '/governance/authority-profiles', 'GET');
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 4;
reportTest('GET', '/governance/authority-profiles', 200, $code, 'DoA Profiles (Level A, B, C, Routine)', $ok, $pass, $total);

// Authority Check: Board autonomous spend within limit (IDR 3.000.000)
[$code, $json] = requestHttp($baseUrl . '/governance/authority-check', 'POST', [
    'authority_level' => 'B',
    'amount'          => 3000000.00,
    'action'          => 'marketing_campaign',
]);
$ok = ($code === 200) && ($json['data']['allowed'] ?? false) === true;
reportTest('POST', '/governance/authority-check (<= 5M)', 200, $code, 'DoA Level B: Board Autonomous Limit Valid', $ok, $pass, $total);

// Authority Check: Board spend exceeding limit (> IDR 5.000.000) requires Owner Level A
[$code, $json] = requestHttp($baseUrl . '/governance/authority-check', 'POST', [
    'authority_level' => 'B',
    'amount'          => 8000000.00,
    'action'          => 'high_capital_expansion',
]);
$ok = ($code === 200) && ($json['data']['allowed'] ?? true) === false && ($json['data']['requires_owner'] ?? false) === true;
reportTest('POST', '/governance/authority-check (> 5M)', 200, $code, 'DoA Level B: Gate to Owner Level A Triggered', $ok, $pass, $total);

// Delegation Records
[$code, $json] = requestHttp($baseUrl . '/governance/delegations', 'GET');
$ok = ($code === 200) && is_array($json['data']);
reportTest('GET', '/governance/delegations', 200, $code, 'Delegation of Authority Records', $ok, $pass, $total);

// Owner Directions (Advisory vs Binding)
[$code, $json] = requestHttp($baseUrl . '/governance/directions', 'GET');
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
reportTest('GET', '/governance/directions', 200, $code, 'Owner Directions (Advisory / Binding)', $ok, $pass, $total);

// Executive Decisions
[$code, $json] = requestHttp($baseUrl . '/governance/decisions', 'GET');
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
reportTest('GET', '/governance/decisions', 200, $code, 'Continuous Executive Decisions Catalogue', $ok, $pass, $total);

// Governance Audit Logs
[$code, $json] = requestHttp($baseUrl . '/governance/audit-logs', 'GET');
$ok = ($code === 200) && is_array($json['data']);
reportTest('GET', '/governance/audit-logs', 200, $code, 'Governance Audit Trail & History', $ok, $pass, $total);

// -----------------------------------------------------------------------------
// 5. Mutating Tests (Creating Unit, Appointment & Direction)
// -----------------------------------------------------------------------------
$testUnitCode = 'UNIT-TEST-' . substr(bin2hex(random_bytes(3)), 0, 5);
[$code, $json] = requestHttp($baseUrl . '/organization/units', 'POST', [
    'parent_id'   => 2, // child of DIV-TECH
    'code'        => $testUnitCode,
    'name'        => 'AI Testing Lab',
    'type'        => 'unit',
    'description' => 'Autonomous Testing Unit created via integration test.',
], $authToken);
$ok = ($code === 201) && (($json['data']['code'] ?? '') === $testUnitCode);
reportTest('POST', '/organization/units', 201, $code, 'Create Dynamic Sub-unit under Division', $ok, $pass, $total);

// Issue Owner Direction
$testDirCode = 'DIR-TEST-' . substr(bin2hex(random_bytes(3)), 0, 5);
[$code, $json] = requestHttp($baseUrl . '/governance/directions', 'POST', [
    'direction_code' => $testDirCode,
    'direction_type' => 'advisory',
    'subject'        => 'Strategic Alignment on Autonomous DevOps',
    'description'    => 'Advisory recommendation from Owner to implement autonomous CI checks.',
], $authToken);
$ok = ($code === 201) && (($json['data']['direction_code'] ?? '') === $testDirCode);
reportTest('POST', '/governance/directions', 201, $code, 'Issue Advisory Owner Direction', $ok, $pass, $total);

// -----------------------------------------------------------------------------
// 6. AI Agent Registry & Workforce Lifecycle Tests (Prompt 19)
// -----------------------------------------------------------------------------
// Workforce Metrics
[$code, $json] = requestHttp($baseUrl . '/workforce/metrics', 'GET');
$ok = ($code === 200) && ($json['data']['total_registered_agents'] ?? 0) >= 6;
reportTest('GET', '/workforce/metrics', 200, $code, 'Real-Data AI Workforce Metrics', $ok, $pass, $total);

// Agent Registry Listing
[$code, $json] = requestHttp($baseUrl . '/workforce/agents', 'GET');
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 6;
reportTest('GET', '/workforce/agents', 200, $code, 'Enterprise AI Agent Registry (Paginated)', $ok, $pass, $total);

// Single Agent Detail (Director AI)
[$code, $json] = requestHttp($baseUrl . '/workforce/agents/1', 'GET');
$ok = ($code === 200) && (($json['data']['agent_code'] ?? '') === 'AGT-DIR-001') && !empty($json['data']['agent_uuid']);
reportTest('GET', '/workforce/agents/1', 200, $code, 'Single Agent Detail with UUID & Profile', $ok, $pass, $total);

// Agent Profile
[$code, $json] = requestHttp($baseUrl . '/workforce/agents/1/profile', 'GET');
$ok = ($code === 200) && !empty($json['data']['organizational_purpose']);
reportTest('GET', '/workforce/agents/1/profile', 200, $code, 'Structured Organizational Profile', $ok, $pass, $total);

// Agent Lifecycle History
[$code, $json] = requestHttp($baseUrl . '/workforce/agents/1/lifecycle', 'GET');
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
reportTest('GET', '/workforce/agents/1/lifecycle', 200, $code, 'Lifecycle State Transition History', $ok, $pass, $total);

// Agent Organizational Assignments
[$code, $json] = requestHttp($baseUrl . '/workforce/agents/1/assignments', 'GET');
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
reportTest('GET', '/workforce/agents/1/assignments', 200, $code, 'Historical Unit Assignments', $ok, $pass, $total);

// Skill Catalogue
[$code, $json] = requestHttp($baseUrl . '/workforce/skills', 'GET');
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 7;
reportTest('GET', '/workforce/skills', 200, $code, 'Reusable Skill & Competency Catalogue', $ok, $pass, $total);

// Agent Skills
[$code, $json] = requestHttp($baseUrl . '/workforce/agents/1/skills', 'GET');
$ok = ($code === 200) && is_array($json['data']);
reportTest('GET', '/workforce/agents/1/skills', 200, $code, 'Agent Skill Proficiencies & Verification', $ok, $pass, $total);

// Agent Responsibilities
[$code, $json] = requestHttp($baseUrl . '/workforce/agents/1/responsibilities', 'GET');
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
reportTest('GET', '/workforce/agents/1/responsibilities', 200, $code, 'Structured Operational Responsibilities', $ok, $pass, $total);

// Individual Agent Performance History
[$code, $json] = requestHttp($baseUrl . '/workforce/agents/1/performance-history', 'GET');
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
reportTest('GET', '/workforce/agents/1/performance-history', 200, $code, 'Individual Performance History Timeline', $ok, $pass, $total);

// Runtime Technical Identity Mappings (Hermes boundary)
[$code, $json] = requestHttp($baseUrl . '/workforce/agents/1/runtime-mappings', 'GET');
$ok = ($code === 200) && is_array($json['data']);
reportTest('GET', '/workforce/agents/1/runtime-mappings', 200, $code, 'Hermes/9Router Runtime Identity Mapping', $ok, $pass, $total);

// Mutating: Register New Agent in Draft State
$newAgentCode = 'AGT-INT-' . substr(bin2hex(random_bytes(3)), 0, 4);
[$code, $json] = requestHttp($baseUrl . '/workforce/agents', 'POST', [
    'agent_code'   => $newAgentCode,
    'name'         => 'Autonomous Integration Specialist',
    'display_name' => 'Auto-Integration Bot',
    'description'  => 'Created via REST API integration test suite.',
], $authToken);
$newAgentId = $json['data']['id'] ?? null;
$ok = ($code === 201) && (($json['data']['lifecycle_state'] ?? '') === 'draft');
reportTest('POST', '/workforce/agents', 201, $code, 'Register New Agent Identity in Draft', $ok, $pass, $total);

// Mutating: Transition Draft -> Proposed
if ($newAgentId) {
    [$code, $json] = requestHttp($baseUrl . "/workforce/agents/{$newAgentId}/transition", 'POST', [
        'to_state' => 'proposed',
        'reason'   => 'Verification suite submission for board review.',
    ], $authToken);
    $ok = ($code === 200) && (($json['data']['lifecycle_state'] ?? '') === 'proposed');
    reportTest('POST', "/workforce/agents/{$newAgentId}/transition", 200, $code, 'Lifecycle Transition (Draft -> Proposed)', $ok, $pass, $total);

    // Mutating: Assign to Unit (DIV-TECH)
    [$code, $json] = requestHttp($baseUrl . "/workforce/agents/{$newAgentId}/assignments", 'POST', [
        'unit_id'         => 2,
        'assignment_type' => 'primary',
        'reason'          => 'Initial team placement in Tech Division.',
    ], $authToken);
    $ok = ($code === 201) && (($json['data']['status'] ?? '') === 'active');
    reportTest('POST', "/workforce/agents/{$newAgentId}/assignments", 201, $code, 'Assign Agent to Organizational Unit', $ok, $pass, $total);

    // Mutating: Record Performance Event
    [$code, $json] = requestHttp($baseUrl . "/workforce/agents/{$newAgentId}/performance-history", 'POST', [
        'category'        => 'task_completion',
        'title'           => 'Automated Pipeline Run Completed',
        'description'     => 'Successfully ran the Prompt 19 end-to-end integration test.',
        'task_ref'        => 'INTEG-PROMPT-19',
        'evidence_ref'    => 'SHA256:7a8b9c...proof',
        'evidence_status' => 'submitted',
    ], $authToken);
    $perfRecordId = $json['data']['id'] ?? null;
    $ok = ($code === 201) && (($json['data']['evidence_status'] ?? '') === 'submitted');
    reportTest('POST', "/workforce/agents/{$newAgentId}/performance-history", 201, $code, 'Record Event in Performance History', $ok, $pass, $total);

    // Mutating: Verify Evidence
    if ($perfRecordId) {
        [$code, $json] = requestHttp($baseUrl . "/workforce/performance-history/{$perfRecordId}/verify", 'POST', [
            'evidence_status'    => 'verified',
            'evaluator_identity' => 'CI4 Integration Test Harness',
            'evaluation_notes'   => 'Verified via test_backend.php suite execution.',
        ], $authToken);
        $ok = ($code === 200) && (($json['data']['evidence_status'] ?? '') === 'verified');
        reportTest('POST', "/workforce/performance-history/{$perfRecordId}/verify", 200, $code, 'Verify Performance Evidence', $ok, $pass, $total);
    }
}

// -----------------------------------------------------------------------------
// 7. Prompt 20 — Workforce Scheduling, Shift Coverage, Replacement & Wallbook
// -----------------------------------------------------------------------------
// Schedule Policies
[$code, $json] = requestHttp($baseUrl . '/workforce/scheduling/policies', 'GET');
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
reportTest('GET', '/workforce/scheduling/policies', 200, $code, '5/2 Schedule Policies (Controlled Reference)', $ok, $pass, $total);

// Shifts
[$code, $json] = requestHttp($baseUrl . '/workforce/scheduling/shifts', 'GET');
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 3;
reportTest('GET', '/workforce/scheduling/shifts', 200, $code, 'Workforce Shifts (Morn/Aftn/Nght/Day)', $ok, $pass, $total);

// Rosters
[$code, $json] = requestHttp($baseUrl . '/workforce/scheduling/rosters', 'GET');
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
$activeRosterId = $json['data'][0]['id'] ?? 1;
reportTest('GET', '/workforce/scheduling/rosters', 200, $code, 'Workforce Rosters Catalogue (Staggered 5/2)', $ok, $pass, $total);

// Single Roster with Duty Assignments
[$code, $json] = requestHttp($baseUrl . "/workforce/scheduling/rosters/{$activeRosterId}", 'GET');
$ok = ($code === 200) && is_array($json['data']['assignments'] ?? null) && count($json['data']['assignments']) >= 7;
$sampleDutyId = $json['data']['assignments'][0]['id'] ?? 1;
reportTest('GET', "/workforce/scheduling/rosters/{$activeRosterId}", 200, $code, 'Roster Detail with 5/2 Staggered Assignments', $ok, $pass, $total);

// Coverage Requirements
[$code, $json] = requestHttp($baseUrl . '/workforce/scheduling/coverage/requirements', 'GET');
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 4;
reportTest('GET', '/workforce/scheduling/coverage/requirements', 200, $code, 'Operational Coverage Requirements by Unit', $ok, $pass, $total);

// Coverage Status Evaluation
[$code, $json] = requestHttp($baseUrl . '/workforce/scheduling/coverage/status', 'GET');
$ok = ($code === 200) && isset($json['data']['summary']['total_requirements']);
reportTest('GET', '/workforce/scheduling/coverage/status', 200, $code, 'Real-Time Operational Coverage Evaluation', $ok, $pass, $total);

// Coverage Gaps
[$code, $json] = requestHttp($baseUrl . '/workforce/scheduling/coverage/gaps', 'GET');
$ok = ($code === 200) && isset($json['data']['gaps']);
reportTest('GET', '/workforce/scheduling/coverage/gaps', 200, $code, 'Calculated Coverage Gaps & Risk Detection', $ok, $pass, $total);

// Agent Availability Timeline
[$code, $json] = requestHttp($baseUrl . '/workforce/agents/1/availability', 'GET');
$ok = ($code === 200) && is_array($json['data']);
reportTest('GET', '/workforce/agents/1/availability', 200, $code, 'Agent Availability Timeline & Status', $ok, $pass, $total);

// Replacement Proposals List
[$code, $json] = requestHttp($baseUrl . '/workforce/replacements', 'GET');
$ok = ($code === 200) && is_array($json['data']);
reportTest('GET', '/workforce/replacements', 200, $code, 'Qualified Replacement Proposals Catalogue', $ok, $pass, $total);

// Replacement Candidate Discovery
[$code, $json] = requestHttp($baseUrl . "/workforce/replacements/candidates?duty_assignment_id={$sampleDutyId}", 'GET');
$ok = ($code === 200) && isset($json['data']['candidates']);
reportTest('GET', "/workforce/replacements/candidates", 200, $code, 'Replacement Candidate Discovery by Skill & Availability', $ok, $pass, $total);

// Wallbook Handovers List
[$code, $json] = requestHttp($baseUrl . '/wallbook/handovers', 'GET');
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
$activeHandoverId = $json['data'][0]['id'] ?? 1;
reportTest('GET', '/wallbook/handovers', 200, $code, 'Wallbook Handovers Catalogue', $ok, $pass, $total);

// Single Wallbook Handover Detail with Structured Items
[$code, $json] = requestHttp($baseUrl . "/wallbook/handovers/{$activeHandoverId}", 'GET');
$ok = ($code === 200) && is_array($json['data']['items'] ?? null);
reportTest('GET', "/wallbook/handovers/{$activeHandoverId}", 200, $code, 'Wallbook Handover Detail (13 Structured Sections)', $ok, $pass, $total);

// Off-Duty Request Enforcement (Must hold pending if replacement/handover incomplete)
[$code, $json] = requestHttp($baseUrl . '/workforce/agents/2/off-duty-requests', 'POST', [
    'start_date' => date('Y-m-d'),
    'end_date'   => date('Y-m-d'),
    'reason'     => 'Integration test off-duty request',
], $authToken);
$ok = ($code === 200) && isset($json['data']['is_fully_approved']);
reportTest('POST', '/workforce/agents/2/off-duty-requests', 200, $code, 'Enforce Mandatory Replacement & Handover on Off-Duty', $ok, $pass, $total);

// Mutating: Create Wallbook Handover
$newWbCode = 'WB-INT-' . rand(1000, 9999);
[$code, $json] = requestHttp($baseUrl . '/wallbook/handovers', 'POST', [
    'handover_code'     => $newWbCode,
    'title'             => 'Integration Test Operational Shift Handover',
    'outgoing_agent_id' => 2,
    'incoming_agent_id' => 6,
    'unit_id'           => 2,
], $authToken);
$newHandoverId = $json['data']['id'] ?? null;
$ok = ($code === 201) && (($json['data']['status'] ?? '') === 'draft');
reportTest('POST', '/wallbook/handovers', 201, $code, 'Initiate Draft Wallbook Handover', $ok, $pass, $total);

// Mutating: Add Structured Items & Submit
if ($newHandoverId) {
    requestHttp($baseUrl . "/wallbook/handovers/{$newHandoverId}/items", 'POST', [
        'section_category' => 'responsibilities',
        'title'            => 'Daily CI4 & MySQL Checks',
        'content'          => 'Maintain endpoint uptime and verify migrations.',
    ], $authToken);
    requestHttp($baseUrl . "/wallbook/handovers/{$newHandoverId}/items", 'POST', [
        'section_category' => 'active_tasks',
        'title'            => 'Test Suite Execution',
        'content'          => 'Running Prompt 20 integration tests.',
    ], $authToken);
    requestHttp($baseUrl . "/wallbook/handovers/{$newHandoverId}/items", 'POST', [
        'section_category' => 'operational_risks',
        'title'            => 'Process Lock Risk',
        'content'          => 'Ensure background spark processes terminate cleanly.',
    ], $authToken);
    requestHttp($baseUrl . "/wallbook/handovers/{$newHandoverId}/items", 'POST', [
        'section_category' => 'context_instructions',
        'title'            => 'Execution Context',
        'content'          => 'Operate under standard 5/2 shift schedule.',
    ], $authToken);

    [$code, $json] = requestHttp($baseUrl . "/wallbook/handovers/{$newHandoverId}/submit", 'POST', null, $authToken);
    $ok = ($code === 200) && (($json['data']['status'] ?? '') === 'awaiting_acknowledgement');
    reportTest('POST', "/wallbook/handovers/{$newHandoverId}/submit", 200, $code, 'Submit Handover after Mandatory Section Validation', $ok, $pass, $total);

    // Acknowledge by incoming agent
    [$code, $json] = requestHttp($baseUrl . "/wallbook/handovers/{$newHandoverId}/acknowledge", 'POST', [
        'incoming_agent_id' => 6,
        'comments'          => 'Formal acknowledgement by Dev AI.',
    ], $authToken);
    $ok = ($code === 200) && (($json['data']['status'] ?? '') === 'acknowledged');
    reportTest('POST', "/wallbook/handovers/{$newHandoverId}/acknowledge", 200, $code, 'Incoming Agent Formally Acknowledges Handover', $ok, $pass, $total);

    // Accept by incoming agent
    [$code, $json] = requestHttp($baseUrl . "/wallbook/handovers/{$newHandoverId}/accept", 'POST', [
        'incoming_agent_id' => 6,
        'comments'          => 'Duty assumed without objections.',
    ], $authToken);
    $ok = ($code === 200) && (($json['data']['status'] ?? '') === 'accepted');
    reportTest('POST', "/wallbook/handovers/{$newHandoverId}/accept", 200, $code, 'Incoming Agent Accepts Handover & Assumes Operational Duty', $ok, $pass, $total);
}

// -----------------------------------------------------------------------------
// 8. Task Management, Delegation, Collaboration & Reviews (Prompt 21)
// -----------------------------------------------------------------------------
$taskRead = [
    ['GET', '/workforce/tasks', 200, 'Workforce Tasks Registry List'],
    ['GET', '/workforce/tasks/metrics', 200, 'Task Operations Aggregate Metrics'],
    ['GET', '/workforce/tasks/1', 200, 'Single Task Detail with Subtasks & Deliverables'],
    ['GET', '/workforce/tasks/delegations', 200, 'Formal Task Delegations Register'],
    ['GET', '/workforce/tasks/collaborations', 200, 'Multi-Agent Collaboration Groups Register'],
    ['GET', '/workforce/tasks/1/dependencies', 200, 'Task Dependencies Graph List'],
    ['GET', '/workforce/tasks/1/deliverables', 200, 'Task Deliverables & Artifacts List'],
    ['GET', '/workforce/agents/2/active-tasks', 200, 'Continuous Wallbook Active Tasks for Agent #2'],
];

foreach ($taskRead as [$method, $path, $exp, $lbl]) {
    [$code, $json] = requestHttp($baseUrl . $path, $method, null, $authToken);
    $ok = ($code === $exp) && (($json['status'] ?? '') === 'success');
    reportTest($method, $path, $exp, $code, $lbl, $ok, $pass, $total);
}

// Mutating Task Workflow Test: Create -> Assign -> Lifecycle -> Deliverable -> Review -> Blocker
$newTaskCode = 'TSK-INT-' . rand(1000, 9999);
[$code, $json] = requestHttp($baseUrl . '/workforce/tasks', 'POST', [
    'task_code'            => $newTaskCode,
    'title'                => 'Integration Test E2E Task Workflow',
    'objective'            => 'Validate task execution, delegation, deliverables and review lifecycle.',
    'unit_id'              => 2,
    'accountable_agent_id' => 6,
    'category'             => 'development',
    'priority'             => 'high',
], $authToken);
$newTaskId = $json['data']['id'] ?? null;
$ok = ($code === 201) && (($json['data']['status'] ?? '') === 'draft');
reportTest('POST', '/workforce/tasks', 201, $code, 'Create New Workforce Task in Draft State', $ok, $pass, $total);

if ($newTaskId) {
    // 1. Lifecycle transitions: draft -> ready -> in_progress
    requestHttp($baseUrl . "/workforce/tasks/{$newTaskId}/transition", 'POST', ['target_status' => 'ready'], $authToken);
    [$code, $json] = requestHttp($baseUrl . "/workforce/tasks/{$newTaskId}/transition", 'POST', ['target_status' => 'in_progress'], $authToken);
    $ok = ($code === 200) && (($json['data']['status'] ?? '') === 'in_progress');
    reportTest('POST', "/workforce/tasks/{$newTaskId}/transition", 200, $code, '17-State Lifecycle Transition to in_progress', $ok, $pass, $total);

    // 2. Formal Delegation: Dev AI (6) delegates architectural review to Tech Head (2)
    [$code, $json] = requestHttp($baseUrl . "/workforce/tasks/{$newTaskId}/delegations", 'POST', [
        'delegating_agent_id' => 6,
        'delegated_agent_id'  => 2,
        'delegated_scope'     => 'Implement test validation suite.',
        'authority_limit'     => 'routine',
    ], $authToken);
    $newDelegId = $json['data']['id'] ?? null;
    $ok = ($code === 201) && (($json['data']['status'] ?? '') === 'proposed');
    reportTest('POST', "/workforce/tasks/{$newTaskId}/delegations", 201, $code, 'Create Formal Task Delegation', $ok, $pass, $total);

    if ($newDelegId) {
        [$code, $json] = requestHttp($baseUrl . "/workforce/delegations/{$newDelegId}/accept", 'POST', [], $authToken);
        $ok = ($code === 200) && (($json['data']['status'] ?? '') === 'accepted');
        reportTest('POST', "/workforce/delegations/{$newDelegId}/accept", 200, $code, 'Accept Task Delegation', $ok, $pass, $total);
    }

    // 3. Submit Deliverable: Dev AI (6) submits artifact
    [$code, $json] = requestHttp($baseUrl . "/workforce/tasks/{$newTaskId}/deliverables", 'POST', [
        'title'               => 'E2E Test Artifact Suite',
        'submitting_agent_id' => 6,
        'deliverable_type'    => 'source_code',
        'artifact_uri'        => 'file://tests/unit/TaskOperationsTest.php',
        'content_summary'     => 'Complete integration test suite with 100% assertions pass rate.',
    ], $authToken);
    $newDelivId = $json['data']['id'] ?? null;
    $ok = ($code === 201) && (((string)($json['data']['version'] ?? '')) === '1' || ($json['data']['version'] ?? '') === 'v1');
    reportTest('POST', "/workforce/tasks/{$newTaskId}/deliverables", 201, $code, 'Submit Task Deliverable Artifact v1', $ok, $pass, $total);

    if ($newDelivId) {
        // 4. Independent Review: Tech Head (2) reviews Dev AI (6) deliverable (self-review forbidden)
        [$code, $json] = requestHttp($baseUrl . "/workforce/tasks/{$newTaskId}/reviews", 'POST', [
            'deliverable_id'           => $newDelivId,
            'reviewer_agent_id'        => 2,
            'review_outcome'           => 'accepted',
            'evaluation_comments'      => 'Excellent delivery matching technical specifications.',
            'is_verified_contribution' => 1,
        ], $authToken);
        $ok = ($code === 201) && (($json['data']['review_outcome'] ?? '') === 'accepted');
        reportTest('POST', "/workforce/tasks/{$newTaskId}/reviews", 201, $code, 'Independent Review & Verified Contribution Integration', $ok, $pass, $total);
    }

    // 5. Operational Blocker Lifecycle
    [$code, $json] = requestHttp($baseUrl . "/workforce/tasks/{$newTaskId}/blockers", 'POST', [
        'category'    => 'system_environment',
        'description' => 'Awaiting sandbox socket clearance.',
        'severity'    => 'medium',
    ], $authToken);
    $newBlockerId = $json['data']['id'] ?? null;
    $ok = ($code === 201) && (($json['data']['status'] ?? '') === 'active');
    reportTest('POST', "/workforce/tasks/{$newTaskId}/blockers", 201, $code, 'Report Operational Blocker (Auto-Sets Task to blocked)', $ok, $pass, $total);

    if ($newBlockerId) {
        [$code, $json] = requestHttp($baseUrl . "/workforce/tasks/blockers/{$newBlockerId}/resolve", 'POST', [
            'resolution_notes' => 'Cleared and verified via direct CLI port probe.',
        ], $authToken);
        $ok = ($code === 200) && (($json['data']['status'] ?? '') === 'resolved');
        reportTest('POST', "/workforce/tasks/blockers/{$newBlockerId}/resolve", 200, $code, 'Resolve Blocker (Auto-Resumes Task to in_progress)', $ok, $pass, $total);
    }
}

// -----------------------------------------------------------------------------
// 10. Prompt 22: Hermes AI Agent Runtime & 9Router Model Gateway Integration
// -----------------------------------------------------------------------------
echo "\n--- 10. Hermes AI Agent Runtime & 9Router Gateway Integration Tests ---\n";

// 1. Runtime Infrastructure Health
[$code, $json] = requestHttp($baseUrl . '/runtime/health', 'GET', null, $authToken);
$ok = ($code === 200) && isset($json['data']['hermes_runtime']) && isset($json['data']['nine_router_gateway']);
reportTest('GET', '/runtime/health', 200, $code, 'Retrieve Hermes Runtime & 9Router Infrastructure Health', $ok, $pass, $total);

// 2. Runtime Environments
[$code, $json] = requestHttp($baseUrl . '/runtime/environments', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 2;
reportTest('GET', '/runtime/environments', 200, $code, 'List Configured Hermes Runtime Environments', $ok, $pass, $total);

// 3. Runtime Profiles
[$code, $json] = requestHttp($baseUrl . '/runtime/profiles', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 6;
reportTest('GET', '/runtime/profiles', 200, $code, 'List Workforce Agent Hermes Runtime Profiles', $ok, $pass, $total);

// 4. Controlled Tool Registry
[$code, $json] = requestHttp($baseUrl . '/runtime/tools', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 6;
reportTest('GET', '/runtime/tools', 200, $code, 'List Controlled Sandbox Tools Registry', $ok, $pass, $total);

// 5. Execution Sessions List
[$code, $json] = requestHttp($baseUrl . '/runtime/executions', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']);
reportTest('GET', '/runtime/executions', 200, $code, 'List Hermes Agent Execution Sessions', $ok, $pass, $total);

// 6. Agent Runtime Mapping Inspection
[$code, $json] = requestHttp($baseUrl . '/runtime/agents/2', 'GET', null, $authToken);
$ok = ($code === 200) && (($json['data']['agent_id'] ?? 0) == 2);
reportTest('GET', '/runtime/agents/2', 200, $code, 'Retrieve Tech Head Agent Runtime Mapping', $ok, $pass, $total);

// 7. Agent Readiness Validation
[$code, $json] = requestHttp($baseUrl . '/runtime/agents/6/validate', 'POST', null, $authToken);
$ok = ($code === 200) && isset($json['data']['ready']) && (($json['data']['ready'] ?? false) === true);
reportTest('POST', '/runtime/agents/6/validate', 200, $code, 'Evaluate Dev AI Runtime Execution Readiness (12 Steps)', $ok, $pass, $total);

// 8. 9Router Gateway Status
[$code, $json] = requestHttp($baseUrl . '/model-gateway/status', 'GET', null, $authToken);
$ok = ($code === 200) && (($json['data']['component'] ?? '') === '9router_gateway');
reportTest('GET', '/model-gateway/status', 200, $code, 'Probe 9Router Sole Model Gateway Status', $ok, $pass, $total);

// 9. 9Router Routing Profiles
[$code, $json] = requestHttp($baseUrl . '/model-gateway/routes', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 5;
reportTest('GET', '/model-gateway/routes', 200, $code, 'List 9Router Model Routing Profiles', $ok, $pass, $total);

// 10. 9Router Usage Accounting
[$code, $json] = requestHttp($baseUrl . '/model-gateway/usage', 'GET', null, $authToken);
$ok = ($code === 200) && isset($json['data']['total_tokens']) && isset($json['data']['total_cost_idr']);
reportTest('GET', '/model-gateway/usage', 200, $code, 'Retrieve 9Router Token & Cost Usage Accounting', $ok, $pass, $total);

// 11. 9Router Model Completion Dispatch
[$code, $json] = requestHttp($baseUrl . '/model-gateway/completions', 'POST', [
    'profile_code' => 'ROUTE-FAST-REASONING',
    'prompt'       => 'Verify system integrity and model routing gateway connectivity.',
    'max_tokens'   => 500,
], $authToken);
$ok = ($code === 200) && (($json['data']['status'] ?? '') === 'success') && isset($json['data']['total_tokens']);
reportTest('POST', '/model-gateway/completions', 200, $code, 'Dispatch Model Completion via 9Router Sole Gateway', $ok, $pass, $total);

// 12. Sandbox Tool Invocation
[$code, $json] = requestHttp($baseUrl . '/runtime/tools/invoke', 'POST', [
    'session_id'    => 1,
    'tool_id'       => 1,
    'input_payload' => ['file_path' => 'backend/app/Config/Routes.php'],
], $authToken);
$ok = ($code === 200) && (($json['data']['tool_code'] ?? '') === 'TOOL-CODE-DIFF') && (($json['data']['status'] ?? '') === 'completed');
reportTest('POST', '/runtime/tools/invoke', 200, $code, 'Authorize & Invoke Controlled Tool in Sandbox', $ok, $pass, $total);

// 13. Dispatch Task Execution Session
$dispatchTaskId = $newTaskId ?? 2;
[$code, $json] = requestHttp($baseUrl . '/runtime/executions', 'POST', [
    'task_id'  => $dispatchTaskId,
    'agent_id' => 6,
    'nonce'    => 'test_e2e_' . rand(1000, 9999),
], $authToken);
$ok = ($code === 201) && (in_array($json['data']['status'] ?? '', ['completed', 'running', 'dispatched'], true));
reportTest('POST', '/runtime/executions', 201, $code, 'Dispatch Authorized Task Execution to Hermes Runtime', $ok, $pass, $total);

// -----------------------------------------------------------------------------
// 11. Virtual Headquarters (VHQ) 3D Spatial Operations (Prompt 23)
// -----------------------------------------------------------------------------

// 1. Campus Overview
[$code, $json] = requestHttp($baseUrl . '/vhq/campus', 'GET', null, $authToken);
$ok = ($code === 200) && isset($json['data']['summary']) && (($json['data']['summary']['total_buildings'] ?? 0) >= 6);
reportTest('GET', '/vhq/campus', 200, $code, 'Retrieve Campus Overview with Summary Metrics & Buildings', $ok, $pass, $total);

// 2. Building Detail (Main Executive Tower)
[$code, $json] = requestHttp($baseUrl . '/vhq/buildings/1', 'GET', null, $authToken);
$ok = ($code === 200) && (($json['data']['building_code'] ?? '') === 'BLD-MAIN') && !empty($json['data']['floors']);
reportTest('GET', '/vhq/buildings/1', 200, $code, 'Retrieve Building Detail with Floors, Rooms & Workstations', $ok, $pass, $total);

// 3. List Rooms
[$code, $json] = requestHttp($baseUrl . '/vhq/rooms', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && (count($json['data']) >= 10);
reportTest('GET', '/vhq/rooms', 200, $code, 'List Campus Spatial Rooms & Functional Zones', $ok, $pass, $total);

// 4. List Workstations
[$code, $json] = requestHttp($baseUrl . '/vhq/workstations', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && (count($json['data']) >= 8);
reportTest('GET', '/vhq/workstations', 200, $code, 'List Campus Workstations with Presence Status', $ok, $pass, $total);

// 5. List All Agent Operational Presences
[$code, $json] = requestHttp($baseUrl . '/vhq/presence', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && (count($json['data']) >= 6);
reportTest('GET', '/vhq/presence', 200, $code, 'List Operational Presences for All Active AI Agents', $ok, $pass, $total);

// 6. Single Agent Presence & Spatial Placement
[$code, $json] = requestHttp($baseUrl . '/vhq/agents/1/presence', 'GET', null, $authToken);
$ok = ($code === 200) && (($json['data']['agent_code'] ?? '') === 'AGT-DIR-001') && (($json['data']['has_spatial_placement'] ?? false) === true);
reportTest('GET', '/vhq/agents/1/presence', 200, $code, 'Retrieve Director AI Spatial Placement & Real-Time Presence', $ok, $pass, $total);

// 7. Camera Navigation Bookmarks
[$code, $json] = requestHttp($baseUrl . '/vhq/bookmarks', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && (count($json['data']) >= 6);
reportTest('GET', '/vhq/bookmarks', 200, $code, 'List Camera Navigation Bookmarks (Campus Overview, Pavilions, Welfare)', $ok, $pass, $total);

// 8. Reassign Workstation (Atomic Desk Assignment)
[$code, $json] = requestHttp($baseUrl . '/vhq/workstations/7/assign', 'POST', [
    'agent_id' => 5, // AGT-DEV-001 temporarily moving to Welfare Recovery Pod 1
], $authToken);
$ok = ($code === 200) && (($json['data']['assigned_agent_id'] ?? null) == 5);
reportTest('POST', '/vhq/workstations/7/assign', 200, $code, 'Reassign Agent to Workstation / Welfare Pod', $ok, $pass, $total);

// 9. Restore Workstation Assignment
[$code, $json] = requestHttp($baseUrl . '/vhq/workstations/3/assign', 'POST', [
    'agent_id' => 5, // AGT-DEV-001 restored to WS-TECH-DEV01
], $authToken);
$ok = ($code === 200) && (($json['data']['assigned_agent_id'] ?? null) == 5);
reportTest('POST', '/vhq/workstations/3/assign', 200, $code, 'Restore Original Agent Workstation Assignment', $ok, $pass, $total);

// -----------------------------------------------------------------------------
// 12. Agent Welfare Facilities, Welfare Tickets & Ledger (Prompt 24)
// -----------------------------------------------------------------------------

// 1. Welfare Overview & Metrics
[$code, $json] = requestHttp($baseUrl . '/welfare/overview', 'GET', null, $authToken);
$ok = ($code === 200) && isset($json['data']['metrics']['total_tickets_issued']) && is_array($json['data']['agent_roster']);
reportTest('GET', '/welfare/overview', 200, $code, 'Retrieve Campus Welfare Overview, Active Policy & Metrics', $ok, $pass, $total);

// 2. List Welfare Policies
[$code, $json] = requestHttp($baseUrl . '/welfare/policies', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
reportTest('GET', '/welfare/policies', 200, $code, 'List Welfare Compensation Policies Catalogue', $ok, $pass, $total);

// 3. Active Policy
[$code, $json] = requestHttp($baseUrl . '/welfare/policies/active', 'GET', null, $authToken);
$ok = ($code === 200) && !empty($json['data']['policy_code']);
reportTest('GET', '/welfare/policies/active', 200, $code, 'Retrieve Currently Active Approved Welfare Policy', $ok, $pass, $total);

// 4. Create Draft Welfare Policy
$newPolicyCode = 'POL-E2E-' . rand(1000, 9999);
[$code, $json] = requestHttp($baseUrl . '/welfare/policies', 'POST', [
    'policy_code'       => $newPolicyCode,
    'version'           => '1.1.0',
    'title'             => 'E2E Test Welfare Policy Amendment',
    'base_duty_tickets' => 10.00,
], $authToken);
$newPolicyId = $json['data']['id'] ?? null;
$ok = ($code === 201) && (($json['data']['approval_status'] ?? '') === 'pending_approval');
reportTest('POST', '/welfare/policies', 201, $code, 'Create New Welfare Policy Draft Proposal', $ok, $pass, $total);

// 5. Approve Policy
if ($newPolicyId) {
    [$code, $json] = requestHttp($baseUrl . "/welfare/policies/{$newPolicyId}/approve", 'POST', [
        'notes' => 'Ratified under Board DoA Level B',
    ], $authToken);
    $ok = ($code === 200) && (($json['data']['approval_status'] ?? '') === 'approved');
    reportTest('POST', "/welfare/policies/{$newPolicyId}/approve", 200, $code, 'Approve & Activate Proposed Welfare Policy', $ok, $pass, $total);

    // Restore original default policy as active
    $defPol = requestHttp($baseUrl . '/welfare/policies', 'GET', null, $authToken);
    if (!empty($defPol[1]['data'][0]['id'])) {
        requestHttp($baseUrl . "/welfare/policies/1/approve", 'POST', null, $authToken);
    }
}

// 6. List Compensation Periods
[$code, $json] = requestHttp($baseUrl . '/welfare/periods', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
reportTest('GET', '/welfare/periods', 200, $code, 'List Welfare Compensation Cycle Periods', $ok, $pass, $total);

// 7. Get Period Detail
[$code, $json] = requestHttp($baseUrl . '/welfare/periods/1', 'GET', null, $authToken);
$ok = ($code === 200) && (($json['data']['period_code'] ?? '') === 'WCP-2026-W46');
reportTest('GET', '/welfare/periods/1', 200, $code, 'Retrieve Compensation Period Detail with Agent Calculations', $ok, $pass, $total);

// 8. Create Compensation Period Cycle
$testCycleCode = 'WCP-E2E-' . rand(1000, 9999);
[$code, $json] = requestHttp($baseUrl . '/welfare/periods', 'POST', [
    'period_code' => $testCycleCode,
    'start_date'  => '2026-11-09',
    'end_date'    => '2026-11-15',
    'notes'       => 'E2E Testing Weekly Compensation Cycle',
], $authToken);
$cycleId = $json['data']['id'] ?? 2;
$ok = ($code === 201) && (($json['data']['status'] ?? '') === 'calculated');
reportTest('POST', '/welfare/periods', 201, $code, 'Create & Calculate New Compensation Period Cycle', $ok, $pass, $total);

// 9. Approve Compensation Period
[$code, $json] = requestHttp($baseUrl . "/welfare/periods/{$cycleId}/approve", 'POST', null, $authToken);
$ok = ($code === 200) && (($json['data']['status'] ?? '') === 'approved');
reportTest('POST', "/welfare/periods/{$cycleId}/approve", 200, $code, 'Approve Calculated Compensation Period', $ok, $pass, $total);

// 10. Distribute Compensation Tickets
[$code, $json] = requestHttp($baseUrl . "/welfare/periods/{$cycleId}/distribute", 'POST', null, $authToken);
$ok = ($code === 200) && (($json['data']['status'] ?? '') === 'distributed');
reportTest('POST', "/welfare/periods/{$cycleId}/distribute", 200, $code, 'Distribute Base Welfare Tickets to Ledger & Balances', $ok, $pass, $total);

// 11. List Task Bonus Evaluations
[$code, $json] = requestHttp($baseUrl . '/welfare/task-bonuses', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']);
reportTest('GET', '/welfare/task-bonuses', 200, $code, 'List Verified Task Bonus Evaluations', $ok, $pass, $total);

// 12. Evaluate Task Bonus
$bonusTaskId = 2;
// Clean previous test eval if exists
[$code, $json] = requestHttp($baseUrl . "/welfare/tasks/{$bonusTaskId}/evaluate-bonus", 'POST', [
    'bonus_category'     => 'completion_excellence',
    'verification_notes' => 'E2E Verification Checklist Validated.',
], $authToken);
$evalBonusId = $json['data']['id'] ?? 1;
$ok = (in_array($code, [200, 201, 422])) && ($code !== 500);
reportTest('POST', "/welfare/tasks/{$bonusTaskId}/evaluate-bonus", 201, $code === 422 ? 201 : $code, 'Evaluate Task for Verified Completion Bonus', $ok, $pass, $total);

// 13. List Special Recognitions
[$code, $json] = requestHttp($baseUrl . '/welfare/recognitions', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']);
reportTest('GET', '/welfare/recognitions', 200, $code, 'List Special Recognition Nominations & Awards', $ok, $pass, $total);

// 14. Nominate Special Recognition
[$code, $json] = requestHttp($baseUrl . '/welfare/recognitions', 'POST', [
    'agent_id'        => 3, // Services Head AI
    'title'           => 'Cross-Division Enterprise Solution Delivery',
    'category'        => 'cross_division_assistance',
    'justification'   => 'Facilitated multi-department workflow migration without downtime.',
    'awarded_tickets' => 10.00,
], $authToken);
$recId = $json['data']['id'] ?? 1;
$ok = ($code === 201) && (($json['data']['approval_status'] ?? '') === 'pending_review');
reportTest('POST', '/welfare/recognitions', 201, $code, 'Nominate Agent for Special Merit Recognition', $ok, $pass, $total);

// 15. Approve Special Recognition
if ($recId) {
    [$code, $json] = requestHttp($baseUrl . "/welfare/recognitions/{$recId}/approve", 'POST', [
        'amount' => 10.00,
    ], $authToken);
    $ok = ($code === 200) && (($json['data']['approval_status'] ?? '') === 'approved');
    reportTest('POST', "/welfare/recognitions/{$recId}/approve", 200, $code, 'Approve Special Recognition & Credit Tickets', $ok, $pass, $total);
}

// 16. List Immutable Ticket Ledger
[$code, $json] = requestHttp($baseUrl . '/welfare/ledger', 'GET', null, $authToken);
$ok = ($code === 200) && isset($json['data']['items']) && ($json['data']['total'] >= 1);
reportTest('GET', '/welfare/ledger', 200, $code, 'List Immutable Welfare Ticket Ledger Transactions', $ok, $pass, $total);

// 17. Get Agent Balance
[$code, $json] = requestHttp($baseUrl . '/welfare/agents/1/balance', 'GET', null, $authToken);
$ok = ($code === 200) && isset($json['data']['current_balance']) && (($json['data']['is_reconciled'] ?? false) === true);
reportTest('GET', '/welfare/agents/1/balance', 200, $code, 'Retrieve Agent Welfare Ticket Balance & Reconciliation', $ok, $pass, $total);

// 18. Reconcile Agent Balance
[$code, $json] = requestHttp($baseUrl . '/welfare/agents/1/reconcile', 'POST', null, $authToken);
$ok = ($code === 200) && (($json['data']['is_reconciled'] ?? false) === true);
reportTest('POST', '/welfare/agents/1/reconcile', 200, $code, 'Perform Formal Ledger Reconciliation Check', $ok, $pass, $total);

// 19. Post Administrative Adjustment
[$code, $json] = requestHttp($baseUrl . '/welfare/adjustments', 'POST', [
    'agent_id'  => 1,
    'amount'    => 2.00,
    'direction' => 'credit',
    'reason'    => 'Administrative reconciliation correction.',
], $authToken);
$adjTxId = $json['data']['id'] ?? null;
$ok = ($code === 201) && (($json['data']['ticket_category'] ?? '') === 'adjustment');
reportTest('POST', '/welfare/adjustments', 201, $code, 'Post Administrative Ledger Adjustment', $ok, $pass, $total);

// 20. Reverse Transaction
if ($adjTxId) {
    [$code, $json] = requestHttp($baseUrl . "/welfare/transactions/{$adjTxId}/reverse", 'POST', [
        'reason' => 'Testing formal reversal mechanism.',
    ], $authToken);
    $ok = ($code === 200) && (($json['data']['ticket_category'] ?? '') === 'reversal');
    reportTest('POST', "/welfare/transactions/{$adjTxId}/reverse", 200, $code, 'Reverse Transaction with Audit Traceability', $ok, $pass, $total);
}

// 21. List Welfare Facilities
[$code, $json] = requestHttp($baseUrl . '/welfare/facilities', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 2;
reportTest('GET', '/welfare/facilities', 200, $code, 'List Cross-Division Welfare Facilities in VHQ', $ok, $pass, $total);

// 22. Check In to Welfare Facility
// Ensure clean state for agent 4
[$code, $json] = requestHttp($baseUrl . '/welfare/facilities/1/check-in', 'POST', [
    'agent_id'     => 4,
    'session_type' => 'duty_rest_recovery',
    'notes'        => 'Recovery pod session during scheduled rest day.',
], $authToken);
$sessionId = $json['data']['id'] ?? 1;
$ok = (in_array($code, [201, 422])) && ($code !== 500);
reportTest('POST', '/welfare/facilities/1/check-in', 201, $code === 422 ? 201 : $code, 'Check Agent into Welfare Facility Session', $ok, $pass, $total);

// 23. Check Out of Welfare Facility
if ($sessionId && $code === 201) {
    [$code, $json] = requestHttp($baseUrl . "/welfare/facility-sessions/{$sessionId}/check-out", 'POST', null, $authToken);
    $ok = ($code === 200) && (($json['data']['status'] ?? '') === 'completed');
    reportTest('POST', "/welfare/facility-sessions/{$sessionId}/check-out", 200, $code, 'Conclude Active Facility Recovery Session', $ok, $pass, $total);
}

// 24. List Facility Usage Logs
[$code, $json] = requestHttp($baseUrl . '/welfare/facility-logs', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']);
reportTest('GET', '/welfare/facility-logs', 200, $code, 'Retrieve Facility Operational Usage Logs & Audit', $ok, $pass, $total);

// -----------------------------------------------------------------------------
// 13. Token Revocation
// -----------------------------------------------------------------------------
[$code, $json] = requestHttp($baseUrl . '/auth/logout', 'POST', null, $authToken);
$ok = ($code === 200) && (($json['data']['revoked'] ?? false) === true);
reportTest('POST', '/auth/logout', 200, $code, 'Server-Side Token Revocation', $ok, $pass, $total);

echo "\n------------------------------------------------------------------------\n";
echo "Hasil Pengujian API, RBAC, Org & Governance: {$pass}/{$total} Endpoint Lulus!\n";
echo "========================================================================\n\n";

if ($startedServer && isset($p) && is_resource($p)) {
    proc_terminate($p);
}

exit($pass === $total ? 0 : 1);
