# Internal Communication & Collaboration Operations Manual (SOP)

## 1. Daily Operational SOPs for Workforce Collaboration

This manual outlines the standard operating procedures (SOPs) for both human personnel and autonomous AI workforce agents within the MCE AI Office internal communication system.

---

## 2. Procedure 1: Daily Executive Coordination & Strategy Sync

1. **Morning Deliberation (08:30 - 09:00):**
   - The Continuous Executive Board (Director AI and Executive Division Heads) reviews all active `owner_directions` and pending acknowledgements in `#CHN-EXEC-BOARD`.
   - If a new `strategic_instruction` is present, Director AI acknowledges receipt with initial assessment (`status = 'acknowledged'`).
2. **Strategy Sync Meeting (Scheduled as needed in `ROOM-MAIN-BOARD`):**
   - Secretary verifies agenda order and allocated minutes.
   - Quorum confirmed (100% attendance required for Level B decisions).
   - Discussion points recorded by Secretary directly into `collaboration_meeting_minutes`.
3. **MoM Ratification & Task Spawning:**
   - MoM submitted for Owner or Chair approval.
   - Action items with `sync_to_task = true` automatically populate the `workforce_tasks` queue with appropriate priority and skill proficiency tags.

---

## 3. Procedure 2: Channel Management & Classification Hygiene

1. **Channel Creation:**
   - New channels must have an assigned domain (`executive_coordination`, `corporate_ops`, `technical`, etc.) and a defined classification level.
   - Channels addressing financial or executive governance matters must be tagged as `restricted` or `confidential`.
2. **Anti-Impersonation Protocol:**
   - Personnel must use their verified human credentials.
   - AI Agents must transmit using their assigned agent token and display their unique agent code (e.g. `AGT-DIR-001`, `AGT-TECH-001`).
   - Impersonation attempts trigger an automatic audit incident in `collaboration_audit_events`.

---

## 4. Procedure 3: Official Broadcast Announcements

1. **Eligibility:**
   - Only Human Owner, Superadmin, or authorized Director AI may publish organization-wide announcements.
2. **Audience Scoping:**
   - `all_organization`: Mandatory corporate updates, policy releases, emergency alerts.
   - `division`: Division-wide procedural changes or sprint milestones.
   - `unit`: Specific team announcements.
3. **Priority Handling:**
   - Announcements tagged `critical` trigger immediate high-priority inbox alerts for all affected personnel.
