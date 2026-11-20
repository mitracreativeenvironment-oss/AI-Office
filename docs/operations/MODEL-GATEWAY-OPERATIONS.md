# 9Router Model Gateway Operations Guide

## 1. Overview & Operational Responsibilities

**9Router** multiplexes, load-balances, and monitors model completion traffic for MCE AI Office. As the sole gateway, operations teams must monitor:
- Upstream provider latency and health.
- Token consumption rate and budget quotas.
- Model failover and fallback frequency.
- Rate-limiting and error spikes.

---

## 2. Standard Service Configuration

- **Default Endpoint**: `http://127.0.0.1:9090/v1`
- **Health Check Probe**: `GET http://127.0.0.1:9090/health`
- **Completions Route**: `POST http://127.0.0.1:9090/v1/chat/completions`

```bash
# Verify 9Router Gateway daemon
curl -i http://127.0.0.1:9090/health
```

---

## 3. Cost & Quota Monitoring

Operations engineers can query the authoritative usage metrics via CI4:
```bash
curl -H "Authorization: Bearer <TOKEN>" http://127.0.0.1:8080/api/v1/model-gateway/usage
```

Example JSON Response:
```json
{
  "status": "success",
  "data": {
    "total_requests": 42,
    "total_tokens": 128450,
    "prompt_tokens": 38200,
    "completion_tokens": 90250,
    "total_cost_idr": 1926.75,
    "successful_requests": 42,
    "failed_requests": 0,
    "by_route": {
      "ROUTE-FAST-REASONING": { "requests": 28, "tokens": 74500, "cost_idr": 745.00 },
      "ROUTE-CODE-GEN": { "requests": 14, "tokens": 53950, "cost_idr": 1181.75 }
    }
  }
}
```

---

## 4. Disaster Recovery & Fallback Activation

When an upstream model provider degrades:
1. 9Router automatically diverts requests to the profile's configured `fallback_model`.
2. Incidents are logged in `runtime_health_snapshots`.
3. If all upstream providers are unreachable, `NineRouterGatewayAdapter` shifts to simulated multiplexer fallback to prevent complete system lockup in development environments.
