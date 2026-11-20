<?php

namespace App\Services;

use Config\Database;

class AuthService
{
    protected $db;
    protected int $tokenTtlHours = 24;
    protected int $maxFailedAttempts = 5;
    protected int $lockoutMinutes = 15;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Authenticate user with username or email + password.
     */
    public function authenticate(string $login, string $password, ?string $ip = null, ?string $userAgent = null): array
    {
        $now = date('Y-m-d H:i:s');

        // Find user by username or email
        $user = $this->db->table('users')
            ->where('username', $login)
            ->orWhere('email', $login)
            ->get()
            ->getRow();

        if (!$user) {
            $this->logSecurityEvent('login_failure', null, $login, $ip, $userAgent, 'failure', ['reason' => 'user_not_found']);
            throw new \RuntimeException('Invalid credentials provided.', 401);
        }

        // Check if account is temporarily locked
        if (!empty($user->locked_until) && strtotime($user->locked_until) > time()) {
            $remaining = ceil((strtotime($user->locked_until) - time()) / 60);
            $this->logSecurityEvent('login_blocked_locked', $user->id, $login, $ip, $userAgent, 'failure', ['locked_until' => $user->locked_until]);
            throw new \RuntimeException("Account is temporarily locked. Please try again in {$remaining} minutes.", 423);
        }

        // Check account status
        if ($user->status === 'disabled' || $user->is_active == 0) {
            $this->logSecurityEvent('login_blocked_disabled', $user->id, $login, $ip, $userAgent, 'failure');
            throw new \RuntimeException('Account is disabled. Please contact the administrator.', 403);
        }

        if ($user->status === 'suspended') {
            $this->logSecurityEvent('login_blocked_suspended', $user->id, $login, $ip, $userAgent, 'failure');
            throw new \RuntimeException('Account is suspended under governance policy.', 403);
        }

        // Verify password
        if (!password_verify($password, $user->password_hash)) {
            $failedAttempts = ($user->failed_login_attempts ?? 0) + 1;
            $lockedUntil = null;

            if ($failedAttempts >= $this->maxFailedAttempts) {
                $lockedUntil = date('Y-m-d H:i:s', time() + ($this->lockoutMinutes * 60));
                $this->db->table('users')->where('id', $user->id)->update([
                    'failed_login_attempts' => $failedAttempts,
                    'locked_until'          => $lockedUntil,
                    'status'                => 'locked',
                ]);
                $this->logSecurityEvent('account_lockout', $user->id, $login, $ip, $userAgent, 'failure', [
                    'failed_attempts' => $failedAttempts,
                    'locked_until'    => $lockedUntil,
                ]);
                throw new \RuntimeException("Too many failed attempts. Account locked for {$this->lockoutMinutes} minutes.", 423);
            }

            $this->db->table('users')->where('id', $user->id)->update([
                'failed_login_attempts' => $failedAttempts,
            ]);

            $this->logSecurityEvent('login_failure', $user->id, $login, $ip, $userAgent, 'failure', [
                'failed_attempts' => $failedAttempts,
            ]);

            throw new \RuntimeException('Invalid credentials provided.', 401);
        }

        // Rehash password if newer algorithm is available (e.g. from bcrypt to Argon2id)
        $targetAlgo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        if (password_needs_rehash($user->password_hash, $targetAlgo)) {
            $newHash = password_hash($password, $targetAlgo);
            $this->db->table('users')->where('id', $user->id)->update(['password_hash' => $newHash]);
        }

        // Reset failed login attempts & update last_login_at
        $this->db->table('users')->where('id', $user->id)->update([
            'failed_login_attempts' => 0,
            'locked_until'          => null,
            'status'                => 'active',
            'last_login_at'         => $now,
        ]);

        // Generate cryptographically secure opaque token (64 hex characters = 256 bits entropy)
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', time() + ($this->tokenTtlHours * 3600));

        $this->db->table('user_tokens')->insert([
            'user_id'      => $user->id,
            'token_hash'   => $tokenHash,
            'name'         => 'web_bearer_session',
            'ip_address'   => $ip,
            'user_agent'   => $userAgent,
            'expires_at'   => $expiresAt,
            'last_used_at' => $now,
            'is_revoked'   => 0,
            'created_at'   => $now,
        ]);

        $roles = $this->getUserRoles($user->id);
        $permissions = $this->getUserPermissions($user->id);

        $this->logSecurityEvent('login_success', $user->id, $user->username, $ip, $userAgent, 'success');

        return [
            'token'      => $rawToken,
            'expires_at' => $expiresAt,
            'user'       => [
                'id'           => (int)$user->id,
                'username'     => $user->username,
                'email'        => $user->email,
                'display_name' => $user->full_name,
                'status'       => 'active',
                'roles'        => $roles,
                'permissions'  => $permissions,
                'last_login_at'=> $now,
            ],
        ];
    }

    /**
     * Verify an opaque bearer token and return user identity + permissions.
     */
    public function verifyToken(string $rawToken): ?object
    {
        $tokenHash = hash('sha256', $rawToken);
        $now = date('Y-m-d H:i:s');

        $tokenRecord = $this->db->table('user_tokens')
            ->where('token_hash', $tokenHash)
            ->where('is_revoked', 0)
            ->where('expires_at >', $now)
            ->get()
            ->getRow();

        if (!$tokenRecord) {
            return null;
        }

        $user = $this->db->table('users')
            ->where('id', $tokenRecord->user_id)
            ->where('is_active', 1)
            ->where('status', 'active')
            ->get()
            ->getRow();

        if (!$user) {
            return null;
        }

        // Update token last_used_at asynchronously
        $this->db->table('user_tokens')
            ->where('id', $tokenRecord->id)
            ->update(['last_used_at' => $now]);

        $user->roles = $this->getUserRoles($user->id);
        $user->permissions = $this->getUserPermissions($user->id);
        $user->token_id = $tokenRecord->id;

        return $user;
    }

    /**
     * Revoke a specific raw token on logout.
     */
    public function revokeToken(string $rawToken, ?int $userId = null): bool
    {
        $tokenHash = hash('sha256', $rawToken);
        $affected = $this->db->table('user_tokens')
            ->where('token_hash', $tokenHash)
            ->update(['is_revoked' => 1]);

        if ($userId) {
            $this->logSecurityEvent('logout', $userId, null, null, null, 'success');
        }

        return $affected > 0;
    }

    /**
     * Change user password securely and revoke all other sessions.
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $user = $this->db->table('users')->where('id', $userId)->get()->getRow();
        if (!$user) {
            throw new \RuntimeException('User not found.', 404);
        }

        if (!password_verify($currentPassword, $user->password_hash)) {
            $this->logSecurityEvent('password_change_failure', $userId, $user->username, null, null, 'failure', [
                'reason' => 'invalid_current_password',
            ]);
            throw new \RuntimeException('Current password verification failed.', 401);
        }

        // Validate new password strength: minimum 8 characters, letters and numbers
        if (strlen($newPassword) < 8 || !preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            throw new \InvalidArgumentException('Password must be at least 8 characters long and contain both letters and numbers.', 422);
        }

        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        $newHash = password_hash($newPassword, $algo);

        $this->db->table('users')->where('id', $userId)->update([
            'password_hash' => $newHash,
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        // Revoke all existing tokens to enforce re-authentication
        $this->db->table('user_tokens')->where('user_id', $userId)->update(['is_revoked' => 1]);

        // Generate fresh token for the current session
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', time() + ($this->tokenTtlHours * 3600));

        $this->db->table('user_tokens')->insert([
            'user_id'      => $userId,
            'token_hash'   => $tokenHash,
            'name'         => 'web_bearer_session_after_password_change',
            'expires_at'   => $expiresAt,
            'last_used_at' => date('Y-m-d H:i:s'),
            'is_revoked'   => 0,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->logSecurityEvent('password_change_success', $userId, $user->username, null, null, 'success');

        return [
            'token'      => $rawToken,
            'expires_at' => $expiresAt,
            'message'    => 'Password changed successfully. All previous sessions have been revoked.',
        ];
    }

    /**
     * Retrieve array of role slugs assigned to user.
     */
    public function getUserRoles(int $userId): array
    {
        $builder = $this->db->table('user_roles ur');
        $builder->select('r.slug');
        $builder->join('roles r', 'r.id = ur.role_id');
        $builder->where('ur.user_id', $userId);
        $rows = $builder->get()->getResult();

        return array_map(fn($row) => $row->slug, $rows);
    }

    /**
     * Retrieve aggregated array of effective permission slugs assigned to user through all their roles.
     */
    public function getUserPermissions(int $userId): array
    {
        $builder = $this->db->table('user_roles ur');
        $builder->select('p.slug');
        $builder->distinct();
        $builder->join('role_permissions rp', 'rp.role_id = ur.role_id');
        $builder->join('permissions p', 'p.id = rp.permission_id');
        $builder->where('ur.user_id', $userId);
        $rows = $builder->get()->getResult();

        return array_values(array_unique(array_map(fn($row) => $row->slug, $rows)));
    }

    /**
     * Record an auditable security event in security_events table.
     */
    public function logSecurityEvent(
        string $eventType,
        ?int $userId = null,
        ?string $actorIdentifier = null,
        ?string $ip = null,
        ?string $userAgent = null,
        string $status = 'success',
        array $metadata = []
    ): void {
        try {
            $this->db->table('security_events')->insert([
                'user_id'          => $userId,
                'event_type'       => $eventType,
                'actor_identifier' => $actorIdentifier,
                'ip_address'       => $ip ?? service('request')->getIPAddress(),
                'user_agent'       => substr($userAgent ?? service('request')->getUserAgent()->getAgentString(), 0, 255),
                'status'           => $status,
                'metadata'         => !empty($metadata) ? json_encode($metadata) : null,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to log security event: ' . $e->getMessage());
        }
    }
}
