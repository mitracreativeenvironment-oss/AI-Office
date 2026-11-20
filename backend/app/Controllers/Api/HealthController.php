<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;

class HealthController extends BaseApiController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $dbConnected = false;
        $dbVersion = null;

        try {
            $dbConnected = $db->connect();
            $dbVersion = $db->getVersion();
        } catch (\Throwable $e) {
            $dbConnected = false;
        }

        return $this->respondSuccess([
            'system'        => 'MCE AI Company Operating System',
            'environment'   => ENVIRONMENT,
            'php_version'   => PHP_VERSION,
            'ci_version'    => \CodeIgniter\CodeIgniter::CI_VERSION,
            'database'      => [
                'connected' => (bool)$dbConnected,
                'driver'    => $db->DBDriver,
                'version'   => $dbVersion,
            ],
            'server_time'   => date('Y-m-d H:i:s T'),
        ], 'MCE Backend is healthy');
    }
}
