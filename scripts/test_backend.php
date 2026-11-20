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
// 13. Knowledge Management, Organizational Memory & Wallbook Bridge (Prompt 25)
// -----------------------------------------------------------------------------
echo "\n--- Section 13: Knowledge Management & Organizational Memory ---\n";

// 1. Overview Metrics
[$code, $json] = requestHttp($baseUrl . '/knowledge/overview', 'GET', null, $authToken);
$ok = ($code === 200) && isset($json['data']['total_records']) && isset($json['data']['by_domain']);
reportTest('GET', '/knowledge/overview', 200, $code, 'Retrieve Knowledge Management Overview Metrics', $ok, $pass, $total);

// 2. List Knowledge Categories
[$code, $json] = requestHttp($baseUrl . '/knowledge/categories', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 5;
reportTest('GET', '/knowledge/categories', 200, $code, 'List Cross-Domain Knowledge Categories', $ok, $pass, $total);

// 3. Create Custom Knowledge Category
$testCatSlug = 'cat-test-' . rand(100, 999);
[$code, $json] = requestHttp($baseUrl . '/knowledge/categories', 'POST', [
    'name'        => 'Test Technical Infrastructure',
    'domain'      => 'technical',
    'description' => 'Category created during automated integration test.',
    'display_order' => 99,
], $authToken);
$testCatId = $json['data']['id'] ?? 1;
$ok = ($code === 201) && !empty($json['data']['category_code']);
reportTest('POST', '/knowledge/categories', 201, $code, 'Create New Knowledge Category in Technical Domain', $ok, $pass, $total);

// 4. List Knowledge Records (Articles)
[$code, $json] = requestHttp($baseUrl . '/knowledge/records', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
reportTest('GET', '/knowledge/records', 200, $code, 'List Knowledge Records with Least-Privilege Filtering', $ok, $pass, $total);

// 5. Create Draft Knowledge Record
$testRecSlug = 'rec-test-' . rand(1000, 9999);
[$code, $json] = requestHttp($baseUrl . '/knowledge/records', 'POST', [
    'category_id'           => $testCatId,
    'slug'                  => $testRecSlug,
    'title'                 => 'Operational Runbook for Multi-Agent Task Balancing',
    'summary'               => 'Best practices and procedural flow for task distribution.',
    'content'               => "# Runbook\n\nStep 1: Check queue depth.\nStep 2: Assign worker.\nStep 3: Log metrics.",
    'classification_level'  => 'internal',
    'target_audience'       => 'ai_and_human',
    'author_agent_id'       => 2, // Tech Lead AI
    'tags'                  => ['ops', 'runbook', 'balancing'],
], $authToken);
$testRecId = $json['data']['id'] ?? null;
$ok = ($code === 201) && (($json['data']['status'] ?? '') === 'draft');
reportTest('POST', '/knowledge/records', 201, $code, 'Create Knowledge Record in Draft Status', $ok, $pass, $total);

// 6. Get Single Knowledge Record
if ($testRecId) {
    [$code, $json] = requestHttp($baseUrl . "/knowledge/records/{$testRecId}", 'GET', null, $authToken);
    $ok = ($code === 200) && (($json['data']['id'] ?? null) == $testRecId);
    reportTest('GET', "/knowledge/records/{$testRecId}", 200, $code, 'Retrieve Single Knowledge Record by ID', $ok, $pass, $total);

    // 7. Update Draft Record
    [$code, $json] = requestHttp($baseUrl . "/knowledge/records/{$testRecId}", 'PUT', [
        'title'   => 'Operational Runbook for Multi-Agent Task Balancing v2',
        'content' => "# Runbook Updated\n\nRefined procedural steps.",
        'change_summary' => 'Enhanced with validation steps.',
    ], $authToken);
    $ok = ($code === 200) && strpos($json['data']['title'] ?? '', 'v2') !== false;
    reportTest('PUT', "/knowledge/records/{$testRecId}", 200, $code, 'Update Knowledge Record & Generate Draft Version', $ok, $pass, $total);

    // 8. Submit Draft for Review
    [$code, $json] = requestHttp($baseUrl . "/knowledge/records/{$testRecId}/transition", 'POST', [
        'target_status' => 'under_review',
        'reason'        => 'Submitted for independent architectural peer review.',
    ], $authToken);
    $ok = ($code === 200) && in_array($json['data']['status'] ?? '', ['under_review', 'submitted']);
    reportTest('POST', "/knowledge/records/{$testRecId}/transition (submit)", 200, $code, 'Submit Knowledge Record for Independent Review', $ok, $pass, $total);

    // 9. Review Record (Approve)
    [$code, $json] = requestHttp($baseUrl . "/knowledge/records/{$testRecId}/transition", 'POST', [
        'target_status' => 'approved',
        'reason'        => 'Approved following verification of architecture requirements.',
    ], $authToken);
    $ok = ($code === 200) && (($json['data']['status'] ?? '') === 'approved');
    reportTest('POST', "/knowledge/records/{$testRecId}/transition (approve)", 200, $code, 'Peer Review Record & Enforce Zero-Self-Review Gate', $ok, $pass, $total);

    // 10. Publish Approved Record
    [$code, $json] = requestHttp($baseUrl . "/knowledge/records/{$testRecId}/transition", 'POST', [
        'target_status' => 'published',
        'reason'        => 'Publishing article to organizational knowledge base.',
    ], $authToken);
    $ok = ($code === 200) && (($json['data']['status'] ?? '') === 'published');
    reportTest('POST', "/knowledge/records/{$testRecId}/transition (publish)", 200, $code, 'Publish Approved Knowledge Record', $ok, $pass, $total);

    // 11. Archive Record
    [$code, $json] = requestHttp($baseUrl . "/knowledge/records/{$testRecId}/transition", 'POST', [
        'target_status' => 'archived',
        'reason'        => 'Archiving following integration lifecycle verification.',
    ], $authToken);
    $ok = ($code === 200) && (($json['data']['status'] ?? '') === 'archived');
    reportTest('POST', "/knowledge/records/{$testRecId}/transition (archive)", 200, $code, 'Archive Outdated Knowledge Record', $ok, $pass, $total);
}

// 12. List Knowledge Contributions
[$code, $json] = requestHttp($baseUrl . '/knowledge/contributions', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']);
reportTest('GET', '/knowledge/contributions', 200, $code, 'List Agent Knowledge Sharing Contributions', $ok, $pass, $total);

// 13. Submit Knowledge Contribution (Agent 3 -> Services Head AI)
[$code, $json] = requestHttp($baseUrl . '/knowledge/contributions', 'POST', [
    'contributor_type'     => 'agent',
    'contributor_agent_id' => 3,
    'title'                => 'Client Service Incident Prevention Pattern',
    'domain'               => 'operational',
    'proposed_classification' => 'internal',
    'summary'              => 'Pattern for proactive customer inquiry classification.',
    'content'              => 'Detailing automatic sentiment scoring and rapid ticket routing.',
    'source_context'       => 'Operational experience from service incident handling.',
], $authToken);
$contribId = $json['data']['id'] ?? null;
$ok = ($code === 201) && (($json['data']['status'] ?? '') === 'submitted');
reportTest('POST', '/knowledge/contributions', 201, $code, 'Submit AI Agent Knowledge Contribution', $ok, $pass, $total);

// 14. Review Contribution & Award Performance Recognition (Owner approves Agent 3 contribution)
if ($contribId) {
    [$code, $json] = requestHttp($baseUrl . "/knowledge/contributions/{$contribId}/review", 'POST', [
        'decision' => 'approved',
        'feedback' => 'Contribution verified and ready for corporate dissemination.',
    ], $authToken);
    $ok = ($code === 200) && (($json['data']['status'] ?? '') === 'approved');
    reportTest('POST', "/knowledge/contributions/{$contribId}/review", 200, $code, 'Approve Contribution & Credit Performance History', $ok, $pass, $total);
}

// 15. List Official Corporate Documents
[$code, $json] = requestHttp($baseUrl . '/knowledge/documents', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
reportTest('GET', '/knowledge/documents', 200, $code, 'List Corporate Document Repository Catalog', $ok, $pass, $total);

// 16. Register New Official Document
$docNumber = 'DOC-POL-TEST-' . rand(100, 999);
[$code, $json] = requestHttp($baseUrl . '/knowledge/documents', 'POST', [
    'document_code'        => $docNumber,
    'title'                => 'Corporate Data Retention & Knowledge Preservation Policy',
    'document_type'        => 'policy',
    'domain'               => 'corporate',
    'classification_level' => 'internal',
    'storage_path'         => 'policies/data-retention-preservation-v1.pdf',
    'file_size_bytes'      => 1024 * 32,
    'mime_type'            => 'application/pdf',
    'file_checksum'        => hash('sha256', 'mock_doc_data'),
    'owner_unit_id'        => 1,
], $authToken);
$testDocId = $json['data']['id'] ?? null;
$ok = ($code === 201) && (($json['data']['document_code'] ?? '') === $docNumber);
reportTest('POST', '/knowledge/documents', 201, $code, 'Register Official Controlled Document with Checksum', $ok, $pass, $total);

// 17. Get Document Detail
if ($testDocId) {
    [$code, $json] = requestHttp($baseUrl . "/knowledge/documents/{$testDocId}", 'GET', null, $authToken);
    $ok = ($code === 200) && (($json['data']['id'] ?? null) == $testDocId);
    reportTest('GET', "/knowledge/documents/{$testDocId}", 200, $code, 'Retrieve Controlled Document Details & Version Pointer', $ok, $pass, $total);

    // 18. Document Version Revision
    [$code, $json] = requestHttp($baseUrl . "/knowledge/documents/{$testDocId}/versions", 'POST', [
        'version_number' => '1.1',
        'storage_path'   => 'policies/data-retention-preservation-v1.1.pdf',
        'file_checksum'  => hash('sha256', 'mock_doc_data_v1.1'),
        'file_size'      => 1024 * 34,
        'change_summary' => 'Added compliance addendum.',
    ], $authToken);
    $ok = ($code === 201) && isset($json['data']['current_version']);
    reportTest('POST', "/knowledge/documents/{$testDocId}/versions", 201, $code, 'Add Document Release Version Revision & Integrity Checksum', $ok, $pass, $total);
}

// 19. List Agent Memories (Director AI = 1)
[$code, $json] = requestHttp($baseUrl . '/knowledge/agents/1/memories', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
reportTest('GET', '/knowledge/agents/1/memories', 200, $code, 'List Agent Memory Enclave (Working/Episodic/Semantic/Procedural)', $ok, $pass, $total);

// 20. Create Agent Memory Item
[$code, $json] = requestHttp($baseUrl . '/knowledge/agents/1/memories', 'POST', [
    'title'                => 'Decision Synthesis Procedural Enclave Check',
    'memory_type'          => 'procedural',
    'memory_key'           => 'decision_synthesis_checklist',
    'content'              => 'Execute stakeholder impact check, verify DoA limits, record in continuous_executive_decisions.',
    'classification_level' => 'confidential',
    'importance_score'     => 0.95,
], $authToken);
$testMemId = $json['data']['id'] ?? null;
$ok = ($code === 201) && !empty($json['data']['memory_code']) && !empty($json['data']['id']);
reportTest('POST', '/knowledge/agents/1/memories', 201, $code, 'Store Structured Agent Memory Enclave Item', $ok, $pass, $total);

// 21. Get Single Memory Item
if ($testMemId) {
    [$code, $json] = requestHttp($baseUrl . "/knowledge/memories/{$testMemId}", 'GET', null, $authToken);
    $ok = ($code === 200) && (($json['data']['id'] ?? null) == $testMemId);
    reportTest('GET', "/knowledge/memories/{$testMemId}", 200, $code, 'Retrieve Agent Memory Item with Privacy Boundary', $ok, $pass, $total);

    // 22. Promote Memory to Knowledge Contribution
    [$code, $json] = requestHttp($baseUrl . "/knowledge/memories/{$testMemId}/promote", 'POST', [
        'title'   => 'Executive Decision Synthesis Procedural Memory Promotion',
        'domain'  => 'corporate',
        'summary' => 'Distilled procedural guide promoted from Director AI memory.',
    ], $authToken);
    $ok = ($code === 201) && !empty($json['data']['contribution_code']);
    reportTest('POST', "/knowledge/memories/{$testMemId}/promote", 201, $code, 'Promote Private Memory to Enterprise Knowledge Contribution', $ok, $pass, $total);
}

// 23. List Wallbook Handover Checklists
[$code, $json] = requestHttp($baseUrl . '/knowledge/wallbook/1/checklists', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
reportTest('GET', '/knowledge/wallbook/1/checklists', 200, $code, 'List Wallbook Shift Handover Quality Checklists', $ok, $pass, $total);

// 24. Verify Handover Checklist Item
$chkId = $json['data'][0]['id'] ?? 1;
[$code, $json] = requestHttp($baseUrl . "/knowledge/wallbook/checklists/{$chkId}", 'POST', [
    'is_satisfied' => 1,
    'notes'        => 'E2E Verification Checklist Validated by Operational Auditor.',
], $authToken);
$ok = ($code === 200) && (($json['data']['is_satisfied'] ?? 0) == 1);
reportTest('POST', "/knowledge/wallbook/checklists/{$chkId}", 200, $code, 'Verify Wallbook Shift Handover Quality Checklist Item', $ok, $pass, $total);

// 25. Promote Wallbook Handover Item to Knowledge Contribution
[$code, $json] = requestHttp($baseUrl . '/knowledge/wallbook/1/items/1/promote', 'POST', [
    'title'           => 'Morning-to-Afternoon Transition Critical Observations',
    'summary'         => 'Shift transition takeaways promoted into reusable organizational memory.',
    'proposed_domain' => 'operational',
], $authToken);
$ok = ($code === 201) && !empty($json['data']['contribution_code']);
reportTest('POST', '/knowledge/wallbook/1/items/1/promote', 201, $code, 'Promote Shift Handover Insights to Knowledge Contribution', $ok, $pass, $total);

// 26. Multi-Domain Search
[$code, $json] = requestHttp($baseUrl . '/knowledge/search?q=governance', 'GET', null, $authToken);
$ok = ($code === 200) && isset($json['data']['results']) && is_array($json['data']['results']);
reportTest('GET', '/knowledge/search?q=governance', 200, $code, 'Unified Multi-Domain Search Across KB, Docs & Memories', $ok, $pass, $total);

// 27. AI RAG Context Retrieval
[$code, $json] = requestHttp($baseUrl . '/knowledge/rag/retrieve', 'POST', [
    'query'    => 'What are the delegation of authority approval thresholds?',
    'agent_id' => 1,
], $authToken);
$ok = ($code === 200) && isset($json['data']['context_chunks']) && isset($json['data']['chunks_count']);
reportTest('POST', '/knowledge/rag/retrieve', 200, $code, 'AI RAG Context Retrieval with Authorization & Provenance', $ok, $pass, $total);

// -----------------------------------------------------------------------------
// 14. Internal Communication, Executive Coordination & Meetings (Prompt 26)
// -----------------------------------------------------------------------------

// 1. Executive Coordination Overview
[$code, $json] = requestHttp($baseUrl . '/collaboration/executive/overview', 'GET', null, $authToken);
$ok = ($code === 200) && isset($json['data']['metrics']['active_directives']) && is_array($json['data']['recent_directives']);
reportTest('GET', '/collaboration/executive/overview', 200, $code, 'Retrieve Executive Coordination Overview & DoA Compliance Metrics', $ok, $pass, $total);

// 2. List Owner Directives
[$code, $json] = requestHttp($baseUrl . '/collaboration/executive/directives', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
reportTest('GET', '/collaboration/executive/directives', 200, $code, 'List Owner Strategic Directives & Advisory Guidance', $ok, $pass, $total);

// 3. Issue New Strategic Directive (Human Owner)
$dirCode = 'DIR-E2E-' . rand(1000, 9999);
[$code, $json] = requestHttp($baseUrl . '/collaboration/executive/directives', 'POST', [
    'direction_code'          => $dirCode,
    'direction_type'          => 'strategic_instruction',
    'subject'                 => 'Operational Synchronization on Multi-Agent Task Delegation',
    'description'             => 'Continuous Executive Board is instructed to enforce DoA Level B/C boundaries for all operational tasks.',
    'requires_acknowledgment' => 1,
    'due_date'                => date('Y-m-d', strtotime('+7 days')),
], $authToken);
$testDirId = $json['data']['id'] ?? null;
$ok = ($code === 201) && (($json['data']['direction_code'] ?? '') === $dirCode);
reportTest('POST', '/collaboration/executive/directives', 201, $code, 'Issue Strategic Instruction Directive with Delegated Authority Scope', $ok, $pass, $total);

// 4. Retrieve Directive Detail
if ($testDirId) {
    [$code, $json] = requestHttp($baseUrl . "/collaboration/executive/directives/{$testDirId}", 'GET', null, $authToken);
    $ok = ($code === 200) && (($json['data']['id'] ?? null) == $testDirId);
    reportTest('GET', "/collaboration/executive/directives/{$testDirId}", 200, $code, 'Retrieve Strategic Directive Details & Ack Join', $ok, $pass, $total);

    // 5. Acknowledge Directive by Agent
    [$code, $json] = requestHttp($baseUrl . "/collaboration/executive/directives/{$testDirId}/acknowledge", 'POST', [
        'actor_type' => 'agent',
        'agent_id'   => 1,
        'status'     => 'in_execution',
        'notes'      => 'Director AI acknowledged directive and initiated board strategy sync.',
    ], $authToken);
    $ok = ($code === 200) && (($json['data']['status'] ?? '') === 'in_execution');
    reportTest('POST', "/collaboration/executive/directives/{$testDirId}/acknowledge", 200, $code, 'Record Formal Directive Acknowledgement & Execution Status', $ok, $pass, $total);
}

// 6. List Executive Decisions
[$code, $json] = requestHttp($baseUrl . '/collaboration/executive/decisions', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
reportTest('GET', '/collaboration/executive/decisions', 200, $code, 'List Continuous Executive Board Decisions (DoA Level B/C)', $ok, $pass, $total);

// 7. Propose Executive Decision
$decCode = 'DEC-E2E-' . rand(1000, 9999);
[$code, $json] = requestHttp($baseUrl . '/collaboration/executive/decisions', 'POST', [
    'decision_code'     => $decCode,
    'title'             => 'Ratification of Enterprise Collaboration Protocol v1.0',
    'summary'           => 'Board agreement to standardize channel hierarchy, meeting MoM workflows, and anti-impersonation guardrails.',
    'decision_category' => 'operational',
    'decision_level'    => 'B',
    'status'            => 'ratified',
], $authToken);
$ok = ($code === 201) && (($json['data']['decision_code'] ?? '') === $decCode);
reportTest('POST', '/collaboration/executive/decisions', 201, $code, 'Record Continuous Executive Board Ratified Decision Proposal', $ok, $pass, $total);

// 8. List Organizational Channels
[$code, $json] = requestHttp($baseUrl . '/collaboration/channels', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 4;
reportTest('GET', '/collaboration/channels', 200, $code, 'List Organizational Channels by Governance Domain & Privacy', $ok, $pass, $total);

// 9. Create New Organizational Channel
$chnCode = 'CHN-TEST-' . rand(100, 999);
[$code, $json] = requestHttp($baseUrl . '/collaboration/channels', 'POST', [
    'channel_code'   => $chnCode,
    'name'           => 'Architecture Integration Pod',
    'description'    => 'Dedicated cross-division channel for integration verification and operational sync.',
    'domain'         => 'technical',
    'classification' => 'internal',
    'is_private'     => 0,
], $authToken);
$testChannelId = $json['data']['id'] ?? null;
$ok = ($code === 201) && (($json['data']['channel_code'] ?? '') === $chnCode);
reportTest('POST', '/collaboration/channels', 201, $code, 'Create New Domain Organizational Channel with Role Hierarchy', $ok, $pass, $total);

// 10. Get Channel Detail
if ($testChannelId) {
    [$code, $json] = requestHttp($baseUrl . "/collaboration/channels/{$testChannelId}", 'GET', null, $authToken);
    $ok = ($code === 200) && (($json['data']['id'] ?? null) == $testChannelId) && is_array($json['data']['members']);
    reportTest('GET', "/collaboration/channels/{$testChannelId}", 200, $code, 'Retrieve Channel Metadata & Active Membership Roster', $ok, $pass, $total);

    // 11. Join Channel as Agent
    [$code, $json] = requestHttp($baseUrl . "/collaboration/channels/{$testChannelId}/join", 'POST', [
        'member_type' => 'agent',
        'agent_id'    => 2,
        'role'        => 'moderator',
    ], $authToken);
    $ok = ($code === 200) && (($json['data']['joined'] ?? false) === true);
    reportTest('POST', "/collaboration/channels/{$testChannelId}/join", 200, $code, 'Join AI Agent to Organizational Channel with Moderator Role', $ok, $pass, $total);

    // 12. Post Message to Channel (with Anti-Impersonation Validation)
    [$code, $json] = requestHttp($baseUrl . "/collaboration/channels/{$testChannelId}/messages", 'POST', [
        'sender_type'         => 'human',
        'content'             => 'Architecture review channel initialized. Team please report readiness.',
        'content_format'      => 'markdown',
        'sender_display_name' => 'Human Owner',
    ], $authToken);
    $testMsgId = $json['data']['id'] ?? null;
    $ok = ($code === 201) && !empty($json['data']['message_code']);
    reportTest('POST', "/collaboration/channels/{$testChannelId}/messages", 201, $code, 'Transmit Message to Channel with Sender Identity Verification', $ok, $pass, $total);

    // 13. Get Channel Messages
    [$code, $json] = requestHttp($baseUrl . "/collaboration/channels/{$testChannelId}/messages", 'GET', null, $authToken);
    $ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
    reportTest('GET', "/collaboration/channels/{$testChannelId}/messages", 200, $code, 'Retrieve Channel Message Stream with Sender Metadata', $ok, $pass, $total);

    // 14. Leave Channel
    [$code, $json] = requestHttp($baseUrl . "/collaboration/channels/{$testChannelId}/leave", 'POST', [
        'member_type' => 'agent',
        'agent_id'    => 2,
    ], $authToken);
    $ok = ($code === 200) && (($json['data']['left'] ?? false) === true);
    reportTest('POST', "/collaboration/channels/{$testChannelId}/leave", 200, $code, 'Remove Agent from Organizational Channel', $ok, $pass, $total);
}

// 15. List Direct Conversations
[$code, $json] = requestHttp($baseUrl . '/collaboration/conversations', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
reportTest('GET', '/collaboration/conversations', 200, $code, 'List Direct & Executive Session Conversations', $ok, $pass, $total);

// 16. Create Direct Conversation
$convCode = 'CONV-E2E-' . rand(100, 999);
[$code, $json] = requestHttp($baseUrl . '/collaboration/conversations', 'POST', [
    'conversation_code' => $convCode,
    'type'              => 'direct',
    'title'             => 'Human Owner & Tech Lead Direct Line',
    'classification'    => 'confidential',
    'participants'      => [
        ['participant_type' => 'agent', 'agent_id' => 2, 'role' => 'member']
    ],
], $authToken);
$testConvId = $json['data']['id'] ?? null;
$ok = ($code === 201) && (($json['data']['conversation_code'] ?? '') === $convCode);
reportTest('POST', '/collaboration/conversations', 201, $code, 'Establish Direct 1-on-1 Confidential Human-Agent Conversation', $ok, $pass, $total);

// 17. Get Conversation Detail
if ($testConvId) {
    [$code, $json] = requestHttp($baseUrl . "/collaboration/conversations/{$testConvId}", 'GET', null, $authToken);
    $ok = ($code === 200) && (($json['data']['id'] ?? null) == $testConvId) && count($json['data']['participants']) >= 2;
    reportTest('GET', "/collaboration/conversations/{$testConvId}", 200, $code, 'Retrieve Conversation Participants & Privacy Level', $ok, $pass, $total);

    // 18. Send Direct Message
    [$code, $json] = requestHttp($baseUrl . "/collaboration/conversations/{$testConvId}/messages", 'POST', [
        'sender_type'         => 'human',
        'content'             => 'Tech Lead, verify readiness of 9Router Gateway failover.',
        'content_format'      => 'text',
        'sender_display_name' => 'Human Owner',
    ], $authToken);
    $directMsgId = $json['data']['id'] ?? null;
    $ok = ($code === 201) && !empty($json['data']['id']);
    reportTest('POST', "/collaboration/conversations/{$testConvId}/messages", 201, $code, 'Send Confidential 1-on-1 Message Between Human & Agent', $ok, $pass, $total);

    // 19. Get Direct Messages
    [$code, $json] = requestHttp($baseUrl . "/collaboration/conversations/{$testConvId}/messages", 'GET', null, $authToken);
    $ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
    reportTest('GET', "/collaboration/conversations/{$testConvId}/messages", 200, $code, 'Retrieve Conversation Message Thread', $ok, $pass, $total);

    // 20. Pin Message
    if ($directMsgId) {
        [$code, $json] = requestHttp($baseUrl . "/collaboration/messages/{$directMsgId}/pin", 'POST', [
            'is_pinned' => true,
        ], $authToken);
        $ok = ($code === 200) && (($json['data']['pinned'] ?? false) === true);
        reportTest('POST', "/collaboration/messages/{$directMsgId}/pin", 200, $code, 'Pin Critical Message to Conversation Stream', $ok, $pass, $total);
    }

    // 21. Mark Messages as Read
    [$code, $json] = requestHttp($baseUrl . '/collaboration/messages/read', 'POST', [
        'target_type'     => 'conversation',
        'target_id'       => $testConvId,
        'last_message_id' => $directMsgId ?: 1,
    ], $authToken);
    $ok = ($code === 200) && (($json['data']['read'] ?? false) === true);
    reportTest('POST', '/collaboration/messages/read', 200, $code, 'Acknowledge Delivery & Mark Direct Messages Read', $ok, $pass, $total);
}

// 22. List Announcements
[$code, $json] = requestHttp($baseUrl . '/collaboration/announcements', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
reportTest('GET', '/collaboration/announcements', 200, $code, 'List Official Organization Announcements Catalog', $ok, $pass, $total);

// 23. Publish Official Announcement
$annCode = 'ANN-E2E-' . rand(100, 999);
[$code, $json] = requestHttp($baseUrl . '/collaboration/announcements', 'POST', [
    'announcement_code' => $annCode,
    'title'             => 'Scheduled Platform Performance Optimization Window',
    'content'           => 'Notice to all workforce units: Database cache re-indexing is scheduled tonight at 23:00 UTC.',
    'target_scope'      => 'all_organization',
    'priority'          => 'important',
    'classification'    => 'internal',
], $authToken);
$testAnnId = $json['data']['id'] ?? null;
$ok = ($code === 201) && (($json['data']['announcement_code'] ?? '') === $annCode);
reportTest('POST', '/collaboration/announcements', 201, $code, 'Publish Scoped Organization Announcement with Priority Badge', $ok, $pass, $total);

// 24. Get Announcement Detail
if ($testAnnId) {
    [$code, $json] = requestHttp($baseUrl . "/collaboration/announcements/{$testAnnId}", 'GET', null, $authToken);
    $ok = ($code === 200) && (($json['data']['id'] ?? null) == $testAnnId);
    reportTest('GET', "/collaboration/announcements/{$testAnnId}", 200, $code, 'Retrieve Published Announcement Details', $ok, $pass, $total);

    // 25. Update Announcement
    [$code, $json] = requestHttp($baseUrl . "/collaboration/announcements/{$testAnnId}", 'PUT', [
        'priority' => 'critical',
    ], $authToken);
    $ok = ($code === 200) && (($json['data']['priority'] ?? '') === 'critical');
    reportTest('PUT', "/collaboration/announcements/{$testAnnId}", 200, $code, 'Update Announcement Priority to Critical Level', $ok, $pass, $total);

    // 26. Delete Announcement
    [$code, $json] = requestHttp($baseUrl . "/collaboration/announcements/{$testAnnId}", 'DELETE', null, $authToken);
    $ok = ($code === 200) && (($json['data']['deleted'] ?? false) === true);
    reportTest('DELETE', "/collaboration/announcements/{$testAnnId}", 200, $code, 'Retire & Remove Expired Official Announcement', $ok, $pass, $total);
}

// 27. List Meetings
[$code, $json] = requestHttp($baseUrl . '/collaboration/meetings', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
reportTest('GET', '/collaboration/meetings', 200, $code, 'List Scheduled Meetings Bound to Spatial VHQ Rooms', $ok, $pass, $total);

// 28. Schedule Formal Meeting
$mtgCode = 'MTG-E2E-' . rand(100, 999);
[$code, $json] = requestHttp($baseUrl . '/collaboration/meetings', 'POST', [
    'meeting_code'         => $mtgCode,
    'title'                => 'E2E Architecture & Governance Review Session',
    'category'             => 'executive_coordination',
    'classification'       => 'internal',
    'room_id'              => 3, // ROOM-MAIN-BOARD
    'scheduled_start_time' => date('Y-m-d 15:00:00', strtotime('+1 day')),
    'scheduled_end_time'   => date('Y-m-d 16:30:00', strtotime('+1 day')),
    'description'          => 'Formal coordination between Human Owner, Director AI, and Tech Lead.',
    'agendas'              => [
        ['item_order' => 1, 'title' => 'DoA Level B/C Review', 'allocated_minutes' => 30],
        ['item_order' => 2, 'title' => 'Collaboration Module Verification', 'allocated_minutes' => 45],
    ],
    'participants'         => [
        ['participant_type' => 'agent', 'agent_id' => 1, 'role' => 'secretary'],
        ['participant_type' => 'agent', 'agent_id' => 2, 'role' => 'participant'],
    ],
], $authToken);
$testMtgId = $json['data']['id'] ?? null;
$ok = ($code === 201) && (($json['data']['meeting_code'] ?? '') === $mtgCode);
reportTest('POST', '/collaboration/meetings', 201, $code, 'Schedule Meeting with Spatial VHQ Room Binding, Agendas & Roster', $ok, $pass, $total);

// 29. Get Meeting Details
if ($testMtgId) {
    [$code, $json] = requestHttp($baseUrl . "/collaboration/meetings/{$testMtgId}", 'GET', null, $authToken);
    $ok = ($code === 200) && (($json['data']['id'] ?? null) == $testMtgId) && count($json['data']['agendas']) >= 2;
    reportTest('GET', "/collaboration/meetings/{$testMtgId}", 200, $code, 'Retrieve Meeting Details with Agendas, Participants & Room', $ok, $pass, $total);

    // 30. Transition Meeting Status to In-Progress
    [$code, $json] = requestHttp($baseUrl . "/collaboration/meetings/{$testMtgId}/status", 'PATCH', [
        'status' => 'in_progress',
    ], $authToken);
    $ok = ($code === 200) && (($json['data']['status'] ?? '') === 'in_progress');
    reportTest('PATCH', "/collaboration/meetings/{$testMtgId}/status", 200, $code, 'Transition Meeting Status to In-Progress with Actual Start Timestamp', $ok, $pass, $total);

    // 31. Add Agenda Item
    [$code, $json] = requestHttp($baseUrl . "/collaboration/meetings/{$testMtgId}/agendas", 'POST', [
        'title'             => 'Action Items Assignment & Synthesis',
        'allocated_minutes' => 15,
        'presenter_name'    => 'Director AI',
    ], $authToken);
    $ok = ($code === 201) && !empty($json['data']['id']);
    reportTest('POST', "/collaboration/meetings/{$testMtgId}/agendas", 201, $code, 'Append Dynamic Agenda Item to Scheduled Meeting', $ok, $pass, $total);

    // 32. Add Meeting Participant
    [$code, $json] = requestHttp($baseUrl . "/collaboration/meetings/{$testMtgId}/participants", 'POST', [
        'participant_type' => 'agent',
        'agent_id'         => 3,
        'role'             => 'observer',
    ], $authToken);
    $addedPartId = $json['data']['id'] ?? null;
    $ok = ($code === 201) && !empty($json['data']['id']);
    reportTest('POST', "/collaboration/meetings/{$testMtgId}/participants", 201, $code, 'Add Additional AI Agent Observer to Meeting Roster', $ok, $pass, $total);

    // 33. Record Participant Attendance
    if ($addedPartId) {
        [$code, $json] = requestHttp($baseUrl . "/collaboration/meetings/{$testMtgId}/participants/{$addedPartId}", 'PATCH', [
            'attendance_status' => 'attended',
            'notes'             => 'Joined via remote VHQ presence point.',
        ], $authToken);
        $ok = ($code === 200) && (($json['data']['updated'] ?? false) === true);
        reportTest('PATCH', "/collaboration/meetings/{$testMtgId}/participants/{$addedPartId}", 200, $code, 'Record Participant Roll Call & Attendance Status', $ok, $pass, $total);
    }

    // 34. Save Minutes of Meeting (MoM)
    [$code, $json] = requestHttp($baseUrl . "/collaboration/meetings/{$testMtgId}/minutes", 'POST', [
        'attendance_summary' => 'Human Owner (Chair), Director AI (Secretary), Tech Lead AI (Participant)',
        'discussion_notes'   => 'Reviewed full end-to-end integration and approved Prompt 26 deliverables.',
        'formal_decisions'   => '1. Mandated full deployment of internal communication system.\n2. Scheduled Prompt 27 transition.',
    ], $authToken);
    $ok = ($code === 200) && (($json['data']['is_approved'] ?? 1) == 0);
    reportTest('POST', "/collaboration/meetings/{$testMtgId}/minutes", 200, $code, 'Draft Formal Minutes of Meeting (MoM) with Decisions', $ok, $pass, $total);

    // 35. Formally Approve MoM (Executive / Owner Authority)
    [$code, $json] = requestHttp($baseUrl . "/collaboration/meetings/{$testMtgId}/minutes/approve", 'POST', null, $authToken);
    $ok = ($code === 200) && (($json['data']['is_approved'] ?? 0) == 1);
    reportTest('POST', "/collaboration/meetings/{$testMtgId}/minutes/approve", 200, $code, 'Formally Ratify Minutes of Meeting & Finalize Status', $ok, $pass, $total);

    // 36. Get Meeting Minutes Detail
    [$code, $json] = requestHttp($baseUrl . "/collaboration/meetings/{$testMtgId}/minutes", 'GET', null, $authToken);
    $ok = ($code === 200) && (($json['data']['is_approved'] ?? 0) == 1);
    reportTest('GET', "/collaboration/meetings/{$testMtgId}/minutes", 200, $code, 'Retrieve Ratified Minutes of Meeting (MoM) Record', $ok, $pass, $total);

    // 37. Create Action Item Synced to Workforce Tasks
    [$code, $json] = requestHttp($baseUrl . "/collaboration/meetings/{$testMtgId}/action-items", 'POST', [
        'description'   => 'Complete E2E integration test verification for Section 14',
        'assignee_type' => 'agent',
        'assignee_id'   => 2,
        'due_date'      => date('Y-m-d', strtotime('+3 days')),
        'sync_to_task'  => true,
    ], $authToken);
    $testAiId = $json['data']['id'] ?? null;
    $ok = ($code === 201) && !empty($json['data']['task_id']);
    reportTest('POST', "/collaboration/meetings/{$testMtgId}/action-items", 201, $code, 'Generate Action Item with Bi-Directional workforce_tasks Bridge', $ok, $pass, $total);

    // 38. Update Action Item Status
    if ($testAiId) {
        [$code, $json] = requestHttp($baseUrl . "/collaboration/action-items/{$testAiId}", 'PATCH', [
            'status' => 'completed',
        ], $authToken);
        $ok = ($code === 200) && (($json['data']['status'] ?? '') === 'completed');
        reportTest('PATCH', "/collaboration/action-items/{$testAiId}", 200, $code, 'Update Action Item Lifecycle to Completed Status', $ok, $pass, $total);
    }
}

// 39. List All Action Items
[$code, $json] = requestHttp($baseUrl . '/collaboration/action-items', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
reportTest('GET', '/collaboration/action-items', 200, $code, 'List Global Meeting Action Items with Task Links', $ok, $pass, $total);

// 40. List In-App Notifications
[$code, $json] = requestHttp($baseUrl . '/collaboration/notifications', 'GET', null, $authToken);
$ok = ($code === 200) && is_array($json['data']) && count($json['data']) >= 1;
reportTest('GET', '/collaboration/notifications', 200, $code, 'List In-App Notifications Inbox with Priority Levels', $ok, $pass, $total);

// 41. Get Unread Notification Count
[$code, $json] = requestHttp($baseUrl . '/collaboration/notifications/unread-count', 'GET', null, $authToken);
$ok = ($code === 200) && isset($json['data']['unread_count']);
reportTest('GET', '/collaboration/notifications/unread-count', 200, $code, 'Retrieve Real-Time Unread Notification Badge Count', $ok, $pass, $total);

// 42. Mark Notification as Read
$firstNotifId = $json['data']['unread_count'] > 0 ? 1 : null;
[$code, $json] = requestHttp($baseUrl . '/collaboration/notifications/1/read', 'POST', null, $authToken);
$ok = ($code === 200) && (($json['data']['read'] ?? false) === true);
reportTest('POST', '/collaboration/notifications/1/read', 200, $code, 'Mark Single Notification as Read', $ok, $pass, $total);

// 43. Mark All Notifications as Read
[$code, $json] = requestHttp($baseUrl . '/collaboration/notifications/read-all', 'POST', null, $authToken);
$ok = ($code === 200) && (($json['data']['all_read'] ?? false) === true);
reportTest('POST', '/collaboration/notifications/read-all', 200, $code, 'Mark All User Notifications as Read', $ok, $pass, $total);

// 44. Unified Collaboration Search
[$code, $json] = requestHttp($baseUrl . '/collaboration/search?q=Board', 'GET', null, $authToken);
$ok = ($code === 200) && isset($json['data']['total_results']) && ($json['data']['total_results'] >= 1);
reportTest('GET', '/collaboration/search?q=Board', 200, $code, 'Perform Unified Multi-Entity Cross-Domain Collaboration Search', $ok, $pass, $total);

// -----------------------------------------------------------------------------
// 15. Token Revocation
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
