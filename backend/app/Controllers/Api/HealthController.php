<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;

class HealthController extends BaseApiController
{
    /**
     * General Application Health & Environment Info
     * GET /api/v1/health
     */
    public function index()
    {
        $db = \Config\Database::connect();
        $dbConnected = false;
        $dbVersion = null;

        try {
            $dbConnected = (bool)$db->connect();
            if ($dbConnected) {
                $dbVersion = $db->getVersion();
            }
        } catch (\Throwable $e) {
            $dbConnected = false;
        }

        return $this->respondSuccess([
            'system'        => 'MCE AI Company Operating System',
            'service'       => 'mce-ai-office-api',
            'status'        => 'operational',
            'environment'   => ENVIRONMENT,
            'php_version'   => PHP_VERSION,
            'ci_version'    => \CodeIgniter\CodeIgniter::CI_VERSION,
            'database'      => [
                'connected' => $dbConnected,
                'driver'    => $db->DBDriver,
                'version'   => $dbVersion,
            ],
            'server_time'   => date('Y-m-d H:i:s T'),
        ], 'MCE Backend is healthy', ResponseInterface::HTTP_OK, [
            'uptime_check' => true,
        ]);
    }

    /**
     * Dedicated Database Health & Connectivity Diagnostic
     * GET /api/v1/health/database
     */
    public function database()
    {
        $db = \Config\Database::connect();
        $connected = false;
        $version = null;
        $driver = $db->DBDriver ?? 'MySQLi';
        $databaseName = null;

        try {
            $connected = (bool)$db->connect();
            if ($connected) {
                $version = $db->getVersion();
                $databaseName = $db->getDatabase();
            }
        } catch (\Throwable $e) {
            $connected = false;
        }

        if (!$connected) {
            return $this->respondError(
                'Database connection unavailable',
                ResponseInterface::HTTP_SERVICE_UNAVAILABLE,
                [
                    'connected' => false,
                    'driver'    => $driver,
                    'hint'      => 'Verify MySQL service on port 3306 and database credentials in .env',
                ],
                ['checked_at' => date('c')]
            );
        }

        return $this->respondSuccess([
            'connected' => true,
            'driver'    => $driver,
            'version'   => $version,
            'database'  => $databaseName,
        ], 'Database is operational and connected', ResponseInterface::HTTP_OK, [
            'checked_at' => date('c'),
        ]);
    }
}

