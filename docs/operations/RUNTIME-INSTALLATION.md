# Hermes Runtime Installation & Deployment Operations

## 1. System Requirements

- **Operating System**: Linux (Ubuntu 22.04+ / Debian 12+) or Windows Server / Windows 11 with WSL2.
- **Node.js**: v20.x or v22.x LTS.
- **Python**: 3.10+ (for Hermes worker orchestration).
- **RAM**: Minimum 8 GB (16 GB+ recommended for local LLM inference).
- **Ports**: Port `8686` (Hermes REST API), Port `9090` (9Router Gateway).

---

## 2. Production Installation Steps

### Step 1: Install Hermes Agent Runtime
```bash
# Clone Hermes Runtime repository
git clone https://github.com/hermes-agent-runtime/hermes.git /opt/mce/hermes
cd /opt/mce/hermes

# Install dependencies and build daemon
npm ci
npm run build

# Configure environment
cp .env.example .env
# Set HERMES_PORT=8686
# Set HERMES_ROUTER_URL=http://127.0.0.1:9090/v1
```

### Step 2: Configure Systemd Daemon (Linux)
```ini
[Unit]
Description=Hermes AI Agent Runtime Daemon for MCE AI Office
After=network.target

[Service]
Type=simple
User=mce
WorkingDirectory=/opt/mce/hermes
ExecStart=/usr/bin/npm start
Restart=always
RestartSec=5
Environment=NODE_ENV=production
Environment=PORT=8686

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now hermes
```

### Step 3: Verify Runtime Connectivity from CI4
```bash
cd /opt/mce/backend
php spark db:seed RuntimeIntegrationSeeder
curl -X GET http://127.0.0.1:8080/api/v1/runtime/health
```

---

## 3. Simulation Mode for Local Development

When live Hermes daemons are absent, `HermesRuntimeAdapter` automatically engages simulation fallback:
- All execution endpoints return valid responses.
- Chronological execution events are recorded faithfully.
- Sandbox tool executions simulate real execution latencies (25–95 ms).
- Token usage and cost metrics are tracked with high accuracy.
- Zero fake connections: `mode: 'simulated'` and `is_live: false` are reported transparently.
