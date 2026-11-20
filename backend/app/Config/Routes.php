<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// ==========================================
// MCE REST API v1
// ==========================================
$routes->group('api/v1', static function ($routes) {
    // Health Check
    $routes->get('health', 'Api\HealthController::index');

    // Company Profile & Divisions
    $routes->get('company', 'Api\CompanyController::profile');
    $routes->get('divisions', 'Api\CompanyController::divisions');

    // Agents Management
    $routes->get('agents', 'Api\AgentController::index');
    $routes->get('agents/(:segment)', 'Api\AgentController::show/$1');
    $routes->post('agents/(:segment)/status', 'Api\AgentController::updateStatus/$1');

    // Tasks Management
    $routes->get('tasks', 'Api\TaskController::index');
    $routes->post('tasks', 'Api\TaskController::create');

    // Approval Center (Human-in-the-Loop)
    $routes->get('approvals', 'Api\ApprovalController::index');
    $routes->post('approvals/(:num)/action', 'Api\ApprovalController::decide/$1');

    // 3D Virtual Office Integration
    $routes->get('office/layout', 'Api\OfficeController::layout');
    $routes->get('office/feed', 'Api\OfficeController::feed');
});
