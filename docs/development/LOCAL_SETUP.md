# LOCAL DEVELOPMENT SETUP GUIDE
## MCE AI Office — Getting Started
**Document Code:** `MCE-DEV-SETUP-2026-V1`  
**Audience:** Full-Stack Engineers & Technical Contributors

---

## 1. Prerequisites

Ensure your workstation has the following tools installed:
- **Windows 11** (or compatible Linux/macOS)
- **PHP CLI >= 8.2** with `intl`, `mbstring`, `mysqli`, `curl`, `openssl` enabled
- **Composer >= 2.8**
- **Node.js >= 20.x** (verified on v22.21.0) and **npm >= 10.x**
- **MySQL >= 8.0** or **MariaDB >= 10.11** running on port 3306
- **Git >= 2.40**

---

## 2. Initial Setup

### Step 1: Clone and Inspect
```bash
git clone git@github.com:mitracreativeenvironment-oss/AI-Office.git
cd AI-Office
```

### Step 2: Configure Environment Files
Duplicate the provided example templates:
```bash
# Root environment guidance
copy .env.example .env

# Backend environment
copy backend\.env.example backend\.env

# Frontend environment
copy frontend\.env.example frontend\.env
```

### Step 3: Configure Database
1. Ensure your local MySQL/MariaDB service is running on port `3306`.
2. Create the development database:
   ```sql
   CREATE DATABASE IF NOT EXISTS mce_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Verify that `backend/.env` has matching credentials:
   ```ini
   database.default.hostname = 127.0.0.1
   database.default.database = mce_db
   database.default.username = root
   database.default.password = 
   database.default.port = 3306
   ```

### Step 4: Run Database Migrations and Seeders
In the `backend/` directory:
```bash
cd backend
php spark migrate
php spark db:seed MceInitialSeeder
cd ..
```

---

## 3. Running the Application Locally

You need two terminal windows running concurrently during local development:

### Terminal 1: Backend REST API Server (Port 8080)
```bash
cd backend
php spark serve --port 8080
```
> The API will be available at `http://localhost:8080/api/v1`

### Terminal 2: Frontend Application (Port 5173)
```bash
cd frontend
npm run dev
```
> The React application will be available at `http://localhost:5173`

---

## 4. Verifying Application Health

1. Open your browser and navigate to:
   - **Administrative Dashboard**: `http://localhost:5173/#dashboard`
   - **Virtual Headquarters (3D)**: `http://localhost:5173/#vhq`
2. Check the top navigation bar:
   - The status badge should display **`API ONLINE (<latency>ms)`** in green.
3. Test backend endpoints directly:
   - `http://localhost:8080/api/v1/health`
   - `http://localhost:8080/api/v1/health/database`

---

## 5. Automated Verification Suite

Run all verification tests from the workspace root:

```bash
# Run backend REST integration tests (10/10 endpoints)
npm run test:backend

# Run frontend business logic and 3D diorama tests (6/6 tests)
npm test

# Run production build compilation
npm run build
```
