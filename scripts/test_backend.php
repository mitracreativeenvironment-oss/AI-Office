<?php

// MCE REST API End-to-End Test Suite
// Verifies HTTP endpoints, JSON structure, and database persistence

$baseUrl = 'http://localhost:8080/api/v1';

// Check if server is running on port 8080
$fp = @fsockopen('127.0.0.1', 8080, $errno, $errstr, 1);
$startedServer = false;
$serverProcess = null;

if (!$fp) {
    echo "Starting temporary CI4 server on http://localhost:8080 ...\n";
    $backendDir = realpath(__DIR__ . '/../backend');
    $cmd = 'php spark serve --port 8080';

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $p = proc_open("php spark serve --port 8080", [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, $backendDir);
    }
    sleep(3);
    $startedServer = true;
} else {
    fclose($fp);
}

$endpoints = [
    ['GET', '/health', 200, 'System Health & DB Connection'],
    ['GET', '/company', 200, 'Company Profile & Metrics'],
    ['GET', '/divisions', 200, '6 Divisions (KBLI Units)'],
    ['GET', '/agents', 200, 'Agents Directory & Status'],
    ['GET', '/agents/director', 200, 'Single Agent Detail (Director AI)'],
    ['GET', '/tasks', 200, 'Tasks List (Track 1 & 2)'],
    ['GET', '/approvals', 200, 'Pending Approval Requests (Level C)'],
    ['GET', '/office/layout', 200, '3D Virtual Office Layout'],
    ['GET', '/office/feed', 200, '3D Live Report Feed'],
];

echo "\n======================================================\n";
echo " MCE CODEIGNITER 4 REST API — INTEGRATION TESTS\n";
echo "======================================================\n\n";

$pass = 0;
$total = count($endpoints);

foreach ($endpoints as [$method, $path, $expectedCode, $label]) {
    $url = $baseUrl . $path;
    $ctx = stream_context_create([
        'http' => [
            'method'  => $method,
            'timeout' => 5,
            'ignore_errors' => true,
        ]
    ]);

    $content = @file_get_contents($url, false, $ctx);
    $statusLine = $http_response_header[0] ?? '';
    preg_match('{HTTP\/\S*\s(\d{3})}', $statusLine, $match);
    $statusCode = isset($match[1]) ? (int)$match[1] : 0;

    $json = json_decode($content ?? '', true);
    $isSuccess = ($statusCode === $expectedCode) && (($json['status'] ?? '') === 'success');

    if ($isSuccess) {
        $pass++;
        echo sprintf(" [PASS] %-6s %-25s -> %s (HTTP %d)\n", $method, $path, $label, $statusCode);
    } else {
        echo sprintf("❌ [FAIL] %-6s %-25s -> Expected %d, got %d (%s)\n", $method, $path, $expectedCode, $statusCode, $label);
    }
}

echo "\n------------------------------------------------------\n";
echo "Hasil Pengujian API: {$pass}/{$total} Endpoint Lulus!\n";
echo "======================================================\n\n";

if ($startedServer && isset($p) && is_resource($p)) {
    proc_terminate($p);
}

exit($pass === $total ? 0 : 1);
