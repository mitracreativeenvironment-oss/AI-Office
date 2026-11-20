<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use App\Services\AuthService;
use Config\Database;

class AdminController extends BaseApiController
{
    protected $db;
    protected AuthService $authService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->authService = new AuthService();
    }

    /**
     * List all human application users
     * GET /api/v1/admin/users
     */
    public function listUsers()
    {
        $users = $this->db->table('users')
            ->select('id, username, email, full_name, role, status, is_active, last_login_at, created_at')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResult();

        foreach ($users as &$u) {
            $u->id = (int)$u->id;
            $u->roles = $this->authService->getUserRoles($u->id);
        }

        return $this->respondSuccess($users, 'User accounts retrieved.');
    }

    /**
     * Get User details
     * GET /api/v1/admin/users/{id}
     */
    public function getUser($id = null)
    {
        $user = $this->db->table('users')
            ->select('id, username, email, full_name, role, status, is_active, last_login_at, created_at, updated_at')
            ->where('id', (int)$id)
            ->get()
            ->getRow();

        if (!$user) {
            return $this->respondNotFound('User account not found.');
        }

        $user->id = (int)$user->id;
        $user->roles = $this->authService->getUserRoles($user->id);
        $user->permissions = $this->authService->getUserPermissions($user->id);

        return $this->respondSuccess($user, 'User details retrieved.');
    }

    /**
     * Create a new user account
     * POST /api/v1/admin/users
     */
    public function createUser()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $username = trim($json['username'] ?? '');
        $email = trim($json['email'] ?? '');
        $fullName = trim($json['full_name'] ?? '');
        $password = (string)($json['password'] ?? '');
        $roleSlug = trim($json['role'] ?? 'operator');

        if (empty($username) || empty($email) || empty($password)) {
            return $this->respondError('Username, email, and password are required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check unique username and email
        $existing = $this->db->table('users')
            ->where('username', $username)
            ->orWhere('email', $email)
            ->countAllResults();

        if ($existing > 0) {
            return $this->respondError('Username or email already exists.', ResponseInterface::HTTP_CONFLICT);
        }

        // Privilege escalation check
        $currentUser = $this->request->authenticatedUser ?? null;
        if ($roleSlug === 'owner' && !in_array('owner', $currentUser->roles ?? [], true)) {
            return $this->respondError('Only an Owner may create or assign the Owner role.', ResponseInterface::HTTP_FORBIDDEN);
        }

        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        $now = date('Y-m-d H:i:s');

        $userId = $this->db->table('users')->insert([
            'username'      => $username,
            'email'         => $email,
            'full_name'     => $fullName ?: $username,
            'password_hash' => password_hash($password, $algo),
            'role'          => $roleSlug,
            'status'        => 'active',
            'is_active'     => 1,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        // Assign role
        $role = $this->db->table('roles')->where('slug', $roleSlug)->get()->getRow();
        if ($role) {
            $this->db->table('user_roles')->insert([
                'user_id'    => $userId,
                'role_id'    => $role->id,
                'created_at' => $now,
            ]);
        }

        $this->authService->logSecurityEvent(
            'user_created',
            $currentUser ? $currentUser->id : null,
            $currentUser ? $currentUser->username : 'system',
            null,
            null,
            'success',
            ['created_user_id' => $userId, 'username' => $username, 'role' => $roleSlug]
        );

        return $this->respondSuccess([
            'id'       => $userId,
            'username' => $username,
            'email'    => $email,
            'role'     => $roleSlug,
        ], 'User account created successfully.', ResponseInterface::HTTP_CREATED);
    }

    /**
     * Disable a user account
     * POST /api/v1/admin/users/{id}/disable
     */
    public function disableUser($id = null)
    {
        $userId = (int)$id;
        $currentUser = $this->request->authenticatedUser ?? null;

        if ($currentUser && $currentUser->id == $userId) {
            return $this->respondError('You cannot disable your own active account.', ResponseInterface::HTTP_BAD_REQUEST);
        }

        $user = $this->db->table('users')->where('id', $userId)->get()->getRow();
        if (!$user) {
            return $this->respondNotFound('User not found.');
        }

        // Privilege check: cannot disable owner unless you are an owner
        $targetRoles = $this->authService->getUserRoles($userId);
        if (in_array('owner', $targetRoles, true) && !in_array('owner', $currentUser->roles ?? [], true)) {
            return $this->respondError('Cannot disable an Owner account.', ResponseInterface::HTTP_FORBIDDEN);
        }

        $this->db->table('users')->where('id', $userId)->update([
            'status'     => 'disabled',
            'is_active'  => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Revoke all active tokens immediately
        $this->db->table('user_tokens')->where('user_id', $userId)->update(['is_revoked' => 1]);

        $this->authService->logSecurityEvent(
            'user_disabled',
            $currentUser ? $currentUser->id : null,
            $currentUser ? $currentUser->username : 'system',
            null,
            null,
            'success',
            ['disabled_user_id' => $userId]
        );

        return $this->respondSuccess(['id' => $userId, 'status' => 'disabled'], 'User account disabled and active sessions revoked.');
    }

    /**
     * Enable a user account
     * POST /api/v1/admin/users/{id}/enable
     */
    public function enableUser($id = null)
    {
        $userId = (int)$id;
        $user = $this->db->table('users')->where('id', $userId)->get()->getRow();
        if (!$user) {
            return $this->respondNotFound('User not found.');
        }

        $this->db->table('users')->where('id', $userId)->update([
            'status'                => 'active',
            'is_active'             => 1,
            'failed_login_attempts' => 0,
            'locked_until'          => null,
            'updated_at'            => date('Y-m-d H:i:s'),
        ]);

        return $this->respondSuccess(['id' => $userId, 'status' => 'active'], 'User account enabled successfully.');
    }

    /**
     * List all roles and assigned permissions
     * GET /api/v1/admin/roles
     */
    public function listRoles()
    {
        $roles = $this->db->table('roles')
            ->select('id, name, slug, description, is_system')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResult();

        foreach ($roles as &$r) {
            $r->id = (int)$r->id;
            $builder = $this->db->table('role_permissions rp');
            $builder->select('p.slug, p.name, p.module');
            $builder->join('permissions p', 'p.id = rp.permission_id');
            $builder->where('rp.role_id', $r->id);
            $r->permissions = $builder->get()->getResult();
        }

        return $this->respondSuccess($roles, 'Roles catalogue retrieved.');
    }

    /**
     * List all available permissions
     * GET /api/v1/admin/permissions
     */
    public function listPermissions()
    {
        $permissions = $this->db->table('permissions')
            ->select('id, name, slug, module, description')
            ->orderBy('module', 'ASC')
            ->orderBy('slug', 'ASC')
            ->get()
            ->getResult();

        return $this->respondSuccess($permissions, 'Permissions catalogue retrieved.');
    }

    /**
     * Assign role to user
     * POST /api/v1/admin/users/{id}/roles
     */
    public function assignUserRole($id = null)
    {
        $userId = (int)$id;
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $roleSlug = trim($json['role_slug'] ?? $json['role'] ?? '');

        if (empty($roleSlug)) {
            return $this->respondError('Role slug is required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $this->db->table('users')->where('id', $userId)->get()->getRow();
        if (!$user) {
            return $this->respondNotFound('User not found.');
        }

        $role = $this->db->table('roles')->where('slug', $roleSlug)->get()->getRow();
        if (!$role) {
            return $this->respondNotFound("Role '{$roleSlug}' not found.");
        }

        // Privilege escalation guard
        $currentUser = $this->request->authenticatedUser ?? null;
        if ($roleSlug === 'owner' && !in_array('owner', $currentUser->roles ?? [], true)) {
            return $this->respondError('Only an existing Owner can grant the Owner role.', ResponseInterface::HTTP_FORBIDDEN);
        }

        $exists = $this->db->table('user_roles')
            ->where('user_id', $userId)
            ->where('role_id', $role->id)
            ->countAllResults();

        if ($exists === 0) {
            $this->db->table('user_roles')->insert([
                'user_id'    => $userId,
                'role_id'    => $role->id,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $roles = $this->authService->getUserRoles($userId);

        $this->authService->logSecurityEvent(
            'role_assigned',
            $currentUser ? $currentUser->id : null,
            $currentUser ? $currentUser->username : 'system',
            null,
            null,
            'success',
            ['target_user_id' => $userId, 'assigned_role' => $roleSlug]
        );

        return $this->respondSuccess(['user_id' => $userId, 'roles' => $roles], "Role '{$roleSlug}' assigned successfully.");
    }

    /**
     * List recent security events & audit logs
     * GET /api/v1/admin/audit-logs
     */
    public function listAuditLogs()
    {
        $logs = $this->db->table('security_events')
            ->orderBy('id', 'DESC')
            ->limit(50)
            ->get()
            ->getResult();

        foreach ($logs as &$log) {
            if (!empty($log->metadata)) {
                $log->metadata = json_decode($log->metadata, true);
            }
        }

        return $this->respondSuccess($logs, 'Recent security events retrieved.');
    }
}
