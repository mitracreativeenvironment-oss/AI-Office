# MCE AI Office — Enterprise AI Company Operating System & 3D Virtual Headquarters

[![Version](https://img.shields.io/badge/version-0.1.0-blue.svg)](https://github.com/mitracreativeenvironment-oss/AI-Office)
[![PHP](https://img.shields.io/badge/PHP-8.2.12-777BB4.svg)](https://www.php.net/)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.7.6-EF4423.svg)](https://codeigniter.com/)
[![React](https://img.shields.io/badge/React-19.3.0-61DAFB.svg)](https://react.dev/)
[![Three.js](https://img.shields.io/badge/Three.js-r170-000000.svg)](https://threejs.org/)
[![Vite](https://img.shields.io/badge/Vite-8.3.8-646CFF.svg)](https://vitejs.dev/)

**Mitra Creative Environment (MCE)** operates an enterprise AI operating system integrating an **Administrative Dashboard** with an immersive **3D Virtual Headquarters (VHQ)**.

---

## 1. System Architecture Overview

The system operates across three tightly bounded layers:

1. **Frontend Viewport (React 19 + TypeScript + Vite 8)**:
   - **Administrative Dashboard (`/#dashboard`)**: Operational governance, AI workforce metrics, financial approval gates, and system diagnostics.
   - **Virtual Headquarters (`/#vhq`)**: Native Three.js (r170) isometric campus reflecting real-time physical agent states, work shifts, and collaboration districts.
2. **Authoritative Backend Engine (CodeIgniter 4.7.6)**:
   - Modular monolith REST API (`/api/v1/*`) serving as the Single Source of Truth and Policy Enforcement Engine.
   - Dual-boundary security with Argon2id session handling and strict CORS policies.
3. **Database System of Record (MySQL / MariaDB)**:
   - Relational database on port 3306 (`mce_db`) with UUID v4 primary keys and InnoDB row-level locking.
4. **AI Runtime Boundaries (Planned)**:
   - **Hermes Runtime**: Ephemeral Docker sandbox executing subtasks up to a strict maximum depth of 3 levels.
   - **9Router Gateway**: Centralized AI model router (Claude 3.5 Sonnet, GPT-4o, Gemini 1.5 Pro) with token tracking.

---

## 2. Repository Structure

```text
AI-Office/
├── backend/                  # CodeIgniter 4.7.6 REST API
│   ├── app/
│   │   ├── Config/           # Application, Database, CORS, Routes configuration
│   │   ├── Controllers/Api/  # Thin REST API controllers (Health, Company, Agents, etc.)
│   │   ├── Database/         # Migrations and Seeders (MceInitialSeeder)
│   │   └── Filters/          # CorsFilter and security middleware
│   ├── tests/                # PHPUnit test suite (HealthTest, etc.)
│   └── composer.json         # PHP dependency manifest
│
├── frontend/                 # React 19 + TypeScript + Vite 8 SPA
│   ├── src/
│   │   ├── components/       # DashboardShell, NavigationHeader, VhqView
│   │   ├── services/         # apiClient.ts (centralized API client)
│   │   ├── types/            # api.ts, mce.ts
│   │   ├── gaya/             # 17 visual style kits for 3D office
│   │   ├── OfficeScene.tsx   # Native Three.js isometric office scene
│   │   ├── App.tsx           # Application root & dual-viewport router
│   │   └── styles.css        # Enterprise styling tokens & HUD
│   ├── package.json          # Node dependency manifest
│   └── vite.config.ts        # Vite configuration with /api proxy
│
├── docs/                     # Technical architecture and development guides
│   ├── architecture/         # FOUNDATION.md, master specs
│   ├── development/          # ENVIRONMENT.md, LOCAL_SETUP.md
│   └── implementation/       # IMPLEMENTATION-STATUS.md
│
├── scripts/                  # Automated verification and testing runners
│   ├── test_backend.php      # 10/10 REST API integration test runner
│   ├── test.mjs              # Frontend logic & 3D style test suite
│   └── rekam.mjs             # Video frame recorder for 3D timelapse
│
├── .env.example              # Root environment guidance
├── package.json              # Monorepo command runner
├── CHANGELOG.md              # Version and milestone release log
└── README.md                 # Project documentation
```

---

## 3. Development Prerequisites

- **Windows 11** (or modern Linux/macOS)
- **PHP CLI >= 8.2** (`intl`, `mbstring`, `mysqli`, `curl`, `openssl` enabled)
- **Composer >= 2.8**
- **Node.js >= 20.x** (verified on v22.21.0) and **npm >= 10.x**
- **MySQL >= 8.0** or **MariaDB >= 10.11** on port 3306

Detailed environment specifications are recorded in [docs/development/ENVIRONMENT.md](docs/development/ENVIRONMENT.md).

---

## 4. Quick Start & Setup

### 1. Configure Environment Files
```bash
copy backend\.env.example backend\.env
copy frontend\.env.example frontend\.env
```

### 2. Initialize Database
Ensure MySQL is running on port 3306, then execute:
```bash
cd backend
php spark migrate
php spark db:seed MceInitialSeeder
cd ..
```

### 3. Launch Development Servers

**Backend (Port 8080):**
```bash
cd backend
php spark serve --port 8080
```

**Frontend (Port 5173):**
```bash
cd frontend
npm run dev
```

Open `http://localhost:5173` in your browser. Use the top navigation bar to toggle between the **Administrative Dashboard** and **Virtual Headquarters (3D)**.

---

## 5. Verification Test Commands

Run the full verification suite from the project root:

```bash
# Test backend REST endpoints (10/10 passing)
npm run test:backend

# Test frontend logic & Three.js diorama rules (6/6 passing)
npm test

# Verify frontend production build
npm run build
```

---

## 6. Troubleshooting

- **`PHP Warning: Module "openssl" is already loaded`**:
  A harmless warning on Windows when OpenSSL is compiled statically and declared in `php.ini`. See [docs/development/ENVIRONMENT.md](docs/development/ENVIRONMENT.md#3-analysis-of-php-warning-module-openssl-is-already-loaded) for safe resolution.
- **Database Connection Failed**:
  Confirm MySQL service is active on `127.0.0.1:3306` and credentials match `backend/.env`. Check `http://localhost:8080/api/v1/health/database`.
- **CORS Issues**:
  The Vite dev server proxies `/api` requests directly to `http://localhost:8080/api`, bypassing browser CORS in local development.

---

## 7. CI/CD & Deploy Key Configuration

The repository includes GitHub Actions automation (`.github/workflows/auto-release-tag.yml`) for automated tagging and releases. Local development uses an isolated SSH deploy key configured in `.deploy_key/`. See project docs for details.
