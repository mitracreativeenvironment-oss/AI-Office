# AUTHENTICATION SPECIFICATION & SESSION LIFECYCLE
## MCE AI Office — Token Strategy & Password Management
**Document Code:** `MCE-ARC-AUTH-2026-V1`  
**Status:** Implemented (Prompt 17)

---

## 1. Authentication Mechanism

Authentication in MCE AI Office follows a secure, server-side persisted Bearer token model:

1. **Credential Validation**:
   - The user submits login credentials (`username` or `email` + `password`) to `POST /api/v1/auth/login`.
   - The backend checks for temporary lockouts in `users.locked_until`.
   - `password_verify()` evaluates the hash against Argon2id / bcrypt.
2. **Token Issuance**:
   - On success, the backend generates a 256-bit entropy random token: `bin2hex(random_bytes(32))`.
   - The token is hashed with `hash('sha256', $rawToken)` and stored in `user_tokens`.
   - The raw token is returned to the client and transmitted in subsequent HTTP requests via the standard header:
     ```http
     Authorization: Bearer <raw_token>
     ```
3. **Token Verification (`AuthFilter`)**:
   - The filter hashes the incoming bearer token with SHA-256 and queries `user_tokens`.
   - Verifies that `is_revoked = 0` and `expires_at > NOW()`.
   - Loads user profile and effective permissions onto `$request->authenticatedUser`.

---

## 2. Token Storage & Hash-At-Rest Security

```text
CLIENT (Browser)                  CI4 BACKEND                          DATABASE (user_tokens)
   │                                   │                                         │
   │  POST /api/v1/auth/login          │                                         │
   ├──────────────────────────────────►│                                         │
   │                                   │  rawToken = random_bytes(32)            │
   │                                   │  hash = sha256(rawToken)                │
   │                                   │  INSERT token_hash, expires_at          │
   │                                   ├────────────────────────────────────────►│
   │  200 OK (rawToken)                │                                         │
   │◄──────────────────────────────────┤                                         │
   │                                   │                                         │
   │  GET /api/v1/auth/me              │                                         │
   │  Header: Bearer rawToken          │                                         │
   ├──────────────────────────────────►│                                         │
   │                                   │  hash = sha256(rawToken)                │
   │                                   │  SELECT WHERE token_hash = hash         │
   │                                   ├────────────────────────────────────────►│
```

### Why Hash-at-Rest?
If the MySQL database is breached or exfiltrated, attackers only obtain SHA-256 hashes of the tokens. Because SHA-256 is an irreversible cryptographic one-way hash, attackers CANNOT reconstruct the valid Bearer tokens or hijack active sessions.

---

## 3. Session Lifecycle & Invalidation Rules

- **Default TTL**: 24 hours (`expires_at = NOW() + 24 HOURS`).
- **Logout Invalidation**: Calling `POST /api/v1/auth/logout` immediately sets `is_revoked = 1`.
- **Password Change Invalidation**: Changing passwords via `POST /api/v1/auth/change-password` revokes ALL existing tokens for that user ID, preventing concurrent sessions from persisting.
- **Account Suspension / Deactivation**: Disabling or suspending a user account immediately invalidates all active sessions during `AuthFilter` evaluation.
