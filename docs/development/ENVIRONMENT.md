# ENVIRONMENT CONFIGURATION & SYSTEM PREREQUISITES
## MCE AI Office — Development Baseline
**Document Code:** `MCE-DEV-ENV-2026-V1`  
**Last Verified:** 2026-11-20  
**Environment Host:** Windows 11 Enterprise / Pro x64

---

## 1. Verified Toolchain & Runtime Versions

The following table records the actual toolchain versions verified in the local workspace (`f:\AI-Office`):

| Tool / Runtime | Verified Version | Executable / System Path | Status |
| :--- | :--- | :--- | :---: |
| **Operating System** | Windows 11 (NT 10.0) | `C:\WINDOWS` | Active & Verified |
| **PHP CLI** | 8.2.12 (ZTS Visual C++ 2019 x64) | `E:\project\php\php.exe` | Active & Verified |
| **Composer** | 2.8.12 (2025-09-19) | System PATH (`composer`) | Active & Verified |
| **Node.js** | v22.21.0 | System PATH (`node`) | Active & Verified |
| **npm** | 10.9.4 | System PATH (`npm`) | Active & Verified |
| **Git** | 2.52.0.windows.1 | System PATH (`git`) | Active & Verified |
| **CodeIgniter** | 4.7.6 | `f:\AI-Office\backend` | Active & Verified |
| **Database Server** | MySQL / MariaDB (Port 3306) | `127.0.0.1:3306` (`mce_db`) | Active & Verified |
| **Web / Dev Server** | Vite 8.3.8 / PHP Development Server | Ports 5173 / 8080 | Active & Verified |

---

## 2. PHP Extension & Configuration Audit

CodeIgniter 4 requires specific PHP extensions to operate securely and efficiently. All required extensions were audited via `php -m`:

| Extension | Purpose | Status in Local PHP |
| :--- | :--- | :---: |
| `intl` | Internationalization & locale formatting | Enabled |
| `mbstring` | Multibyte string processing | Enabled |
| `json` | JSON serialization and parsing | Enabled |
| `mysqli` | MySQL database driver | Enabled |
| `pdo_mysql` | PDO MySQL abstraction | Enabled |
| `curl` | HTTP client engine for external integrations | Enabled |
| `openssl` | Cryptographic functions & TLS security | Enabled |
| `xml` / `dom` | XML parsing & schema validations | Enabled |
| `zip` | Archive handling & backup exports | Enabled |

---

## 3. Analysis of PHP Warning: `Module "openssl" is already loaded`

### Observation
Executing PHP commands outputs:
```
PHP Warning: Module "openssl" is already loaded in Unknown on line 0
```

### Root Cause
In PHP 8.2 for Windows (ZTS builds), OpenSSL is either:
1. Statically compiled into the PHP binary core or registered internally, while `php.ini` still contains an explicit directive `extension=openssl`.
2. Loaded in the primary `php.ini` and redundantly in an additional conf file scanned by PHP.

### Impact Assessment
- **Severity**: Low (Harmless Warning).
- The warning does not affect runtime execution, cryptographic operations, or API functionality. OpenSSL functions (`openssl_encrypt`, `openssl_random_pseudo_bytes`) operate normally.

### Safe Correction Procedure
1. Open the active `php.ini` file located at `E:\project\php\php.ini` (or the path reported by `php --ini`).
2. Search for `extension=openssl`.
3. Check if duplicate lines exist, or comment out one instance (`#extension=openssl` or `;extension=openssl`).
4. Re-run `php -v` to confirm the warning is suppressed.
> *Note: If `php.ini` is managed centrally by Laragon or a global PHP installer, modifications should be made within the Laragon PHP configuration menu.*

---

## 4. Port Allocation & Local Network Boundaries

| Port | Protocol | Service | Bound Interface | Access Policy |
| :--- | :---: | :--- | :--- | :--- |
| **5173** | HTTP | Vite Frontend Dev Server | `127.0.0.1` / `0.0.0.0` | Local developer browser |
| **8080** | HTTP | CodeIgniter 4 REST API | `127.0.0.1:8080` | Localhost & Vite Proxy |
| **3306** | TCP | MySQL / MariaDB Server | `127.0.0.1:3306` | Localhost only (CI4 access) |

---

## 5. Security & Isolation Boundaries

- **No Public Secret Exposure**: All development credentials reside strictly in uncommitted `.env` files (`backend/.env`, `frontend/.env`).
- **Standardized Proxying**: The frontend connects to the backend through the Vite proxy (`/api` -> `http://localhost:8080/api`), preventing cross-origin security issues in local development.
- **CORS Restrictiveness**: In production or direct API calls, backend CORS only permits requests from the explicitly designated frontend origin (`http://localhost:5173`). Wildcard `*` origins are disabled.
