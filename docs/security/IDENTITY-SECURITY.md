# IDENTITY & ACCESS CONTROL SECURITY BASELINE
## MCE AI Office — Threat Model & Security Controls
**Document Code:** `MCE-SEC-IDENT-2026-V1`  
**Status:** Operational (Prompt 17)

---

## 1. Threat Mitigation Matrix

| Threat Category | Potential Attack Vector | Applied Countermeasure | Verification Status |
| :--- | :--- | :--- | :---: |
| **Credential Guessing** | Brute-force dictionary attack on login endpoint | 5-attempt limit with automatic 15-minute account lockout (`locked_until`). | Verified via unit & integration tests |
| **Account Enumeration** | Timing or error difference between wrong user vs wrong password | Generic error message (`Invalid credentials provided.`) with uniform response time. | Verified |
| **Database Exfiltration** | SQL dump exposing user access tokens | Tokens are hashed using SHA-256 before insertion into `user_tokens`. Raw tokens cannot be retrieved from disk. | Verified |
| **Session Hijacking** | Stolen token after logout or password change | Server-side invalidation (`is_revoked = 1`) on logout and mass revocation on password change. | Verified |
| **Privilege Escalation** | Admin attempting to self-grant or grant `owner` role | Hard constraint in `AdminController`: only existing `owner` users can grant `owner` role. | Verified |
| **Tampered Frontend** | User modifying client React state or URLs | Backend `AuthFilter` and `PermissionFilter` validate every single request independently. | Verified |

---

## 2. Audit Trail Requirements

Every identity-significant operation logs a permanent record in `security_events`:
- `login_success`
- `login_failure`
- `account_lockout`
- `logout`
- `password_change_success`
- `password_change_failure`
- `access_denied`
- `user_created`
- `user_disabled`
- `role_assigned`

Records include timestamp, actor identifier, client IP address, and user-agent string. Plaintext passwords or raw session tokens are strictly prohibited from logs.
