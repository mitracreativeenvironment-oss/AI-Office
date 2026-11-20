<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use App\Services\AuthService;

class AuthController extends BaseApiController
{
    protected AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * Authenticate Human User
     * POST /api/v1/auth/login
     */
    public function login()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $login = trim($json['login'] ?? $json['username'] ?? '');
        $password = (string)($json['password'] ?? '');

        if (empty($login) || empty($password)) {
            return $this->respondError(
                'Username/Email and password are required.',
                ResponseInterface::HTTP_UNPROCESSABLE_ENTITY,
                ['fields' => ['login' => 'Required', 'password' => 'Required']]
            );
        }

        try {
            $ip = $this->request->getIPAddress();
            $userAgent = $this->request->getUserAgent()->getAgentString();

            $authResult = $this->authService->authenticate($login, $password, $ip, $userAgent);

            return $this->respondSuccess(
                $authResult,
                'Authentication successful. Session token granted.',
                ResponseInterface::HTTP_OK,
                ['auth_type' => 'bearer']
            );
        } catch (\RuntimeException $e) {
            $code = $e->getCode();
            if ($code < 400 || $code > 500) {
                $code = ResponseInterface::HTTP_UNAUTHORIZED;
            }
            return $this->respondError($e->getMessage(), $code);
        } catch (\Throwable $e) {
            log_message('error', 'Login error: ' . $e->getMessage());
            return $this->respondError('An unexpected authentication error occurred.', ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get Current Authenticated Identity Profile & Permissions
     * GET /api/v1/auth/me
     */
    public function me()
    {
        $user = $this->request->authenticatedUser ?? null;
        if (!$user) {
            return $this->respondError('Unauthorized access.', ResponseInterface::HTTP_UNAUTHORIZED);
        }

        return $this->respondSuccess([
            'id'           => (int)$user->id,
            'username'     => $user->username,
            'email'        => $user->email,
            'display_name' => $user->full_name,
            'status'       => $user->status ?? 'active',
            'roles'        => $user->roles ?? [],
            'permissions'  => $user->permissions ?? [],
            'last_login_at'=> $user->last_login_at ?? null,
            'created_at'   => $user->created_at ?? null,
        ], 'Identity profile retrieved successfully.');
    }

    /**
     * Revoke Current Authentication Token
     * POST /api/v1/auth/logout
     */
    public function logout()
    {
        $rawToken = $this->request->rawAuthToken ?? null;
        $user = $this->request->authenticatedUser ?? null;

        if ($rawToken) {
            $this->authService->revokeToken($rawToken, $user ? (int)$user->id : null);
        }

        return $this->respondSuccess(
            ['revoked' => true],
            'Authentication token revoked successfully. Session terminated.'
        );
    }

    /**
     * Change Password Securely
     * POST /api/v1/auth/change-password
     */
    public function changePassword()
    {
        $user = $this->request->authenticatedUser ?? null;
        if (!$user) {
            return $this->respondError('Unauthorized access.', ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $currentPassword = (string)($json['current_password'] ?? '');
        $newPassword = (string)($json['new_password'] ?? '');

        if (empty($currentPassword) || empty($newPassword)) {
            return $this->respondError(
                'Current password and new password are required.',
                ResponseInterface::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $result = $this->authService->changePassword((int)$user->id, $currentPassword, $newPassword);
            return $this->respondSuccess($result, $result['message']);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\RuntimeException $e) {
            $code = $e->getCode();
            if ($code < 400 || $code > 500) $code = 400;
            return $this->respondError($e->getMessage(), $code);
        }
    }
}
