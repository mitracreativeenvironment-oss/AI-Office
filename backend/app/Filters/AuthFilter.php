<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\AuthService;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Allow preflight OPTIONS
        if (strtolower($request->getMethod()) === 'options') {
            return;
        }

        $header = $request->getHeaderLine('Authorization');
        if (empty($header) || !preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
            $response = service('response');
            return $response->setJSON([
                'status'    => 'error',
                'code'      => ResponseInterface::HTTP_UNAUTHORIZED,
                'message'   => 'Unauthorized: Bearer token is required.',
                'errors'    => null,
                'timestamp' => date('c'),
            ])->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $rawToken = $matches[1];
        $authService = new AuthService();
        $user = $authService->verifyToken($rawToken);

        if (!$user) {
            $response = service('response');
            return $response->setJSON([
                'status'    => 'error',
                'code'      => ResponseInterface::HTTP_UNAUTHORIZED,
                'message'   => 'Unauthorized: Token is invalid, expired, or revoked.',
                'errors'    => null,
                'timestamp' => date('c'),
            ])->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        // Expose user and token to current request instance and Service container
        $request->authenticatedUser = $user;
        $request->rawAuthToken = $rawToken;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No-op
    }
}
