<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\AuthService;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Allow preflight OPTIONS
        if (strtolower($request->getMethod()) === 'options') {
            return;
        }

        $user = $request->authenticatedUser ?? null;
        if (!$user) {
            $response = service('response');
            return $response->setJSON([
                'status'    => 'error',
                'code'      => ResponseInterface::HTTP_UNAUTHORIZED,
                'message'   => 'Unauthorized: Authentication required before checking permissions.',
                'errors'    => null,
                'timestamp' => date('c'),
            ])->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        // If no specific permission argument required, pass
        if (empty($arguments)) {
            return;
        }

        $requiredPermission = $arguments[0];
        $userPermissions = $user->permissions ?? [];
        $userRoles = $user->roles ?? [];

        // Owner role has implicit total access
        if (in_array('owner', $userRoles, true)) {
            return;
        }

        if (!in_array($requiredPermission, $userPermissions, true)) {
            $authService = new AuthService();
            $authService->logSecurityEvent(
                'access_denied',
                $user->id,
                $user->username,
                $request->getIPAddress(),
                $request->getUserAgent()->getAgentString(),
                'failure',
                ['required_permission' => $requiredPermission]
            );

            $response = service('response');
            return $response->setJSON([
                'status'    => 'error',
                'code'      => ResponseInterface::HTTP_FORBIDDEN,
                'message'   => "Forbidden: Access denied. Missing required permission '{$requiredPermission}'.",
                'errors'    => null,
                'timestamp' => date('c'),
            ])->setStatusCode(ResponseInterface::HTTP_FORBIDDEN);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No-op
    }
}
