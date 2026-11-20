# AUTHENTICATION & RBAC API SPECIFICATION
## MCE AI Office — API Contract v1
**Document Code:** `MCE-API-AUTH-2026-V1`  
**Base URL:** `/api/v1`

---

## 1. Authentication Endpoints

### 1.1 Login
- **Endpoint**: `POST /api/v1/auth/login`
- **Access**: Public
- **Request Body**:
  ```json
  {
    "login": "owner",
    "password": "your-password"
  }
  ```
- **Response (200 OK)**:
  ```json
  {
    "status": "success",
    "code": 200,
    "message": "Authentication successful. Session token granted.",
    "data": {
      "token": "4f9d2b... (64 hex characters)",
      "expires_at": "2026-11-21 17:00:00",
      "user": {
        "id": 1,
        "username": "owner",
        "email": "owner@mce.local",
        "display_name": "Human Owner & Executive Management",
        "status": "active",
        "roles": ["owner"],
        "permissions": ["users.view", "users.create", "..."]
      }
    },
    "meta": { "auth_type": "bearer" },
    "timestamp": "2026-11-20T17:00:00+08:00"
  }
  ```
- **Error Responses**:
  - `401 Unauthorized`: Invalid credentials.
  - `403 Forbidden`: Account suspended or disabled.
  - `423 Locked`: Account temporarily locked due to 5 consecutive failed attempts.

---

### 1.2 Get Current Identity
- **Endpoint**: `GET /api/v1/auth/me`
- **Access**: Authenticated (`Authorization: Bearer <token>`)
- **Response (200 OK)**:
  ```json
  {
    "status": "success",
    "code": 200,
    "message": "Identity profile retrieved successfully.",
    "data": {
      "id": 1,
      "username": "owner",
      "email": "owner@mce.local",
      "display_name": "Human Owner & Executive Management",
      "status": "active",
      "roles": ["owner"],
      "permissions": ["users.view", "users.create", "..."]
    },
    "timestamp": "2026-11-20T17:00:00+08:00"
  }
  ```

---

### 1.3 Logout
- **Endpoint**: `POST /api/v1/auth/logout`
- **Access**: Authenticated (`Authorization: Bearer <token>`)
- **Response (200 OK)**:
  ```json
  {
    "status": "success",
    "code": 200,
    "message": "Authentication token revoked successfully. Session terminated.",
    "data": { "revoked": true },
    "timestamp": "2026-11-20T17:00:00+08:00"
  }
  ```

---

### 1.4 Change Password
- **Endpoint**: `POST /api/v1/auth/change-password`
- **Access**: Authenticated (`Authorization: Bearer <token>`)
- **Request Body**:
  ```json
  {
    "current_password": "oldPassword123",
    "new_password": "newSecurePassword456!"
  }
  ```
- **Response (200 OK)**: Returns fresh bearer token and confirmation that prior sessions are revoked.

---

## 2. RBAC Administration Endpoints

| Endpoint | Method | Required Permission | Description |
| :--- | :---: | :--- | :--- |
| `/api/v1/admin/users` | `GET` | `users.view` | List all human users with assigned roles |
| `/api/v1/admin/users/{id}` | `GET` | `users.view` | Retrieve specific user details |
| `/api/v1/admin/users` | `POST` | `users.create` | Provision new user account |
| `/api/v1/admin/users/{id}/disable` | `POST` | `users.disable` | Disable user and immediately revoke all sessions |
| `/api/v1/admin/users/{id}/enable` | `POST` | `users.disable` | Re-enable user and clear lockout |
| `/api/v1/admin/roles` | `GET` | `roles.view` | List roles and assigned permission maps |
| `/api/v1/admin/permissions` | `GET` | `permissions.view` | List full permissions catalogue |
| `/api/v1/admin/users/{id}/roles` | `POST` | `roles.assign` | Assign role to user (escalation-guarded) |
| `/api/v1/admin/audit-logs` | `GET` | `audit.view` | List recent security events |
