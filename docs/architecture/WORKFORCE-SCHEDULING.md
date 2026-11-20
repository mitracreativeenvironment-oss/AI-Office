# WORKFORCE SCHEDULING ARCHITECTURE & 5/2 POLICY
## MCE AI Office — Staggered Rosters, Shift Management & Agent Availability Model
**Document Code:** `MCE-ARCH-SCH-2026-V1`  
**Classification:** Controlled Architecture Specification  
**Implementation Milestone:** Prompt 20 — Agent Scheduling, Shift Coverage, Replacement & Wallbook Handover  

---

## 1. Executive Summary & Policy Foundation

The **Workforce Scheduling Architecture** of **PT Mandiri Cyber Ekosistem (MCE)** translates corporate labor and operational policies into a deterministic, auditable workforce management system. 

An AI Agent in MCE is treated as an active organizational workforce member requiring:
- A predictable work and rest roster.
- A distinction between scheduled duty, actual duty completion, designated rest, planned leave, and operational suspension.
- Protection against duty assignment conflicts and exhaustion.
- Historical retention of all published schedules.

---

## 2. Standard 5/2 Work and Rest Policy

MCE establishes a default corporate policy of:
- **Five (5) Working Days**.
- **Two (2) Rest Days** per 7-day scheduling cycle.

### Staggered Roster Mechanism
To prevent operational blackout across weekends and critical shifts, the system enforces **Staggered Rest Days**. Different agents hold different designated rest days within the cycle:

```
+------------------+-----+-----+-----+-----+-----+---------------+---------------+
| AGENT            | MON | TUE | WED | THU | FRI | SAT (WEEKEND) | SUN (WEEKEND) |
+------------------+-----+-----+-----+-----+-----+---------------+---------------+
| Director AI      | WRK | WRK | WRK | WRK | WRK |      RST      |      RST      |
| Tech Head AI     | WRK | WRK | WRK | WRK | WRK |      RST      |      RST      |
| Dev AI           | RST | RST | WRK | WRK | WRK |      WRK      |      WRK      |
| Services Head AI | WRK | WRK | WRK | WRK | WRK |      RST      |      RST      |
| Academy Head AI  | RST | WRK | WRK | WRK | WRK |      WRK      |      RST      |
| Hardware Head AI | WRK | WRK | WRK | WRK | WRK |      RST      |      RST      |
+------------------+-----+-----+-----+-----+-----+---------------+---------------+
Legend: WRK = 8-Hour Scheduled Duty | RST = Designated 5/2 Mandatory Rest Day
```

**Key Operational Benefit:**  
In the Technology Division (`DIV-TECH`), when `Tech Head AI` takes rest on Saturday and Sunday, `Dev AI` is on active duty. Conversely, when `Dev AI` rests on Monday and Tuesday, `Tech Head AI` is on active duty. Thus, technical leadership and operational coverage is maintained **7 days a week (100% coverage)** without violating the 5/2 rule for either agent.

---

## 3. Shift Management

Shifts represent bounded time windows configured with timezone awareness (`Asia/Jakarta` / UTC+7 default):

| Shift Code | Shift Name | Hours (WIB) | Duration | Category | Midnight Crossing |
| :--- | :--- | :---: | :---: | :--- | :---: |
| `SFT-MORN` | Morning Shift | 08:00 – 16:00 | 8.00h | `business_hours` | No |
| `SFT-AFTN` | Afternoon & Evening | 16:00 – 00:00 | 8.00h | `extended_hours` | No |
| `SFT-NGHT` | Night Shift | 00:00 – 08:00 | 8.00h | `continuous_24_7`| No |
| `SFT-STD-DAY` | Standard Corporate Day | 09:00 – 17:00 | 8.00h | `business_hours` | No |

### Conflict Validation Rules:
1. **Overlap Prevention:** An agent cannot be scheduled for two concurrent shifts on the same date.
2. **Rest Interval Protection:** Configurable minimum rest interval (`min_rest_interval_hours = 12`) prevents assigning back-to-back night and morning shifts.
3. **Immutability of Past Records:** Changes to future schedules are recorded via amenders or replacement proposals without rewriting historical duty logs.

---

## 4. Agent Availability Model

Agent availability is strictly decoupled from the Agent Lifecycle State (`active`, `draft`, `retired`). An agent may be `active` in the registry while temporarily unavailable for duty.

### Supported Availability Conditions:
- **`available`**: Ready for operational task assignment.
- **`scheduled_duty`**: Currently executing an approved scheduled shift.
- **`scheduled_rest`**: Fulfilling mandatory 5/2 designated rest period.
- **`planned_leave`**: Approved absence (subject to replacement and Wallbook handover).
- **`temporary_unavailability`**: Short-term offline state (e.g. maintenance, model re-indexing).
- **`under_handover`**: Engaged in preparing or acknowledging a Wallbook handover.
- **`replacement_pending`**: Off-duty request logged, awaiting replacement acceptance.
- **`duty_completed`**: Completed scheduled shift, awaiting next cycle.
