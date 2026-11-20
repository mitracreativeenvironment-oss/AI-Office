# CHANGELOG
All notable changes to the **MCE AI Office** project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [0.12.0] - 2026-11-21 — Project Management, Client Engagement, Project Lifecycle & Delivery Operations Milestone (Prompt 27)

### Added
- **Database Schema Batch 12 (`CreateProjectManagementAndDeliverySchema.php`)**:
  - Implemented 15 relational tables: `project_clients`, `project_client_contacts`, `projects`, `project_team_members`, `project_briefs`, `project_scopes`, `project_milestones`, `project_deliverables`, `project_risks`, `project_issues`, `project_change_requests`, `project_resource_allocations`, `project_status_history`, `project_templates`, `project_audit_events`.
  - Added seamless `workforce_tasks` extension with `project_id` and `milestone_id` foreign keys (zero duplicate task engine).
  - Short foreign key constraint names applied across all tables to guarantee MySQL/MariaDB 64-char identifier limit compliance.
- **Database Seeder (`ProjectManagementSeeder.php`)**:
  - Seeded 2 Clients (`CLI-CORP-001` PT Digital Nusantara Mandiri and `CLI-GOV-001` Diskominfo Prov).
  - Seeded 3 Client Key Stakeholder Contacts (CTO, VP of Engineering, PPK).
  - Seeded 2 Customer Projects (`PRJ-CUST-2026-001` Knowledge Portal and `PRJ-CUST-2026-002` Smart City).
  - Seeded 2 Internal Initiatives (`PRJ-INT-2026-001` VHQ Phase 2 and `PRJ-INT-2026-002` Workforce Skills Benchmark).
  - Seeded multi-disciplinary workforce team allocations (Human Owner sponsor, Lead Architects, Senior Developers, QA Engineers).
  - Seeded briefs, versioned scope baselines, milestones with critical path indicators, deliverables with review/acceptance statuses, risks, issues, change requests, resource allocations, and lifecycle status history.
- **Backend Domain Services**:
  - `ProjectAccessService.php`: 4-tier security classification enforcement (`public`, `internal`, `confidential`, `restricted`), client boundary isolation, and granular role permissions (`view`, `edit`, `approve_stage`, `manage_budget`, `review_deliverable`, etc.).
  - `ProjectService.php`: Master project management, portfolio metrics aggregation, progress recalculation algorithm combining milestones (60%) and linked tasks (40%), bi-directional task linking.
  - `ProjectLifecycleService.php`: State machine validating 9 customer project stages (`lead` to `warranty_support`) and 7 internal initiative stages (`idea_proposal` to `completed_archived`) with automated transition history audit.
  - `ClientManagementService.php`: Customer profile directories and stakeholder contact directories.
  - `ScopeDeliverableService.php`: Project briefs, versioned scope baselines, milestone sequencing, deliverable technical review and external client acceptance sign-off.
  - `ProjectRiskIssueService.php`: Risk register with probability/impact matrix, blocker tracking and resolution plans, and Change Request governance under DoA.
  - `ProjectResourceBudgetService.php`: Workforce team assignments, compute/infrastructure resource pools, and financial budget control.
- **REST API Controller & Routes**:
  - `ProjectController.php`: 32 RESTful endpoints under `/api/v1/projects/*` with standardized envelopes and auth filters.
- **Frontend Integration**:
  - `project.ts`: Complete TypeScript types and interfaces.
  - `projectService.ts`: API client service for all 32 endpoints.
  - `ProjectManagementView.tsx`: Interactive project workspace with Portfolio Overview, Table/Kanban views, 6-tab Workspace drill-down, Client Directory, and modals.
  - Mounted in `NavigationHeader.tsx` and `App.tsx`.
- **Testing & Quality Assurance**:
  - `ProjectManagementTest.php`: 12 PHPUnit tests, 95 assertions passing 100% (111 total backend tests, 790 assertions passing 100%).
  - `scripts/test_backend.php`: Section 15 added with 40 integration endpoints (241/241 REST endpoints passing 100%).
  - `npm --prefix frontend run build`: Clean production compilation with Vite (0 errors).
- **Documentation**:
  - `docs/architecture/PROJECT-MANAGEMENT-ARCHITECTURE.md`
  - `docs/architecture/PROJECT-LIFECYCLE-STATE-MACHINE.md`
  - `docs/architecture/CLIENT-ENGAGEMENT-MODEL.md`
  - `docs/architecture/PROJECT-TASK-INTEGRATION.md`
  - `docs/architecture/PROJECT-GOVERNANCE-FINANCIAL-CONTROL.md`
  - `docs/operations/PROJECT-MANAGEMENT-OPERATIONS.md`
  - `docs/api/PROJECT-MANAGEMENT-API.md`
  - `docs/implementation/PROJECT-MANAGEMENT-IMPLEMENTATION.md`
  - `docs/implementation/PROJECT-MANAGEMENT-READINESS.md`

## [0.11.0] - 2026-11-20 — Internal Communication, Executive Coordination, Meetings & Organizational Collaboration Milestone (Prompt 26)

### Added
- **Database Schema Batch 11 (`CreateInternalCollaborationAndMeetingsSchema.php`)**:
  - Implemented 15 relational tables: `collaboration_conversations`, `collaboration_conversation_participants`, `collaboration_channels`, `collaboration_channel_members`, `collaboration_messages`, `collaboration_message_references`, `collaboration_announcements`, `collaboration_meetings`, `collaboration_meeting_participants`, `collaboration_meeting_agendas`, `collaboration_meeting_minutes`, `collaboration_meeting_action_items`, `collaboration_directive_acknowledgements`, `collaboration_notifications`, `collaboration_audit_events`.
  - Added message threading (`parent_message_id`), pinning (`is_pinned`), read tracking, and polymorphic cross-linking to tasks, documents, knowledge records, and meetings.
  - Added spatial room binding to Virtual Headquarters (`hq_rooms.id`).
  - Added formal Minutes of Meeting (MoM) approval workflow and automated bi-directional action item synchronization with `workforce_tasks`.
- **Database Seeder (`CollaborationSeeder.php`)**:
  - Seeded 5 operational and executive channels (`#CHN-EXEC-BOARD`, `#CHN-CORP-OPS`, `#CHN-DIV-TECH`, `#CHN-ANNOUNCEMENTS`, `#CHN-ALERTS`).
  - Seeded channel memberships, 2 conversations, initial threaded messages, 2 broadcast announcements, and 2 formal meetings.
  - Seeded initial MoM with Human Owner ratification, action items linked to tasks, and in-app notifications.
- **Backend Domain Services**:
  - `CollaborationAccessService.php`: 5-tier classification matrix enforcement and strict anti-impersonation sender identity validation.
  - `MessagingService.php`: Channel management, conversation streams, threaded replies, message references, and read receipts.
  - `AnnouncementService.php`: Broadcast announcements, audience scoping, priority filtering.
  - `ExecutiveCoordinationService.php`: Distinction between advisory guidance and binding directives, acknowledgement tracking, board decision proposals.
  - `MeetingManagementService.php`: 8-state meeting lifecycle, VHQ room bindings, agendas, attendance, MoM approval workflow, action items to task bridge.
  - `CollaborationNotificationService.php`: Real-time notification center, unread counters, delivery tracking.
  - `CollaborationSearchService.php`: Unified cross-domain multi-entity search.
- **REST API Controller & Routes**:
  - `CollaborationController.php`: 28 REST API endpoints under `/api/v1/collaboration/*` with standardized envelopes and auth filters.
- **Frontend Integration**:
  - `collaboration.ts`: Complete TypeScript types and interfaces.
  - `collaborationService.ts`: API client service for all 28 endpoints.
  - `CollaborationView.tsx`: Enterprise collaboration view with 8 operational tabs (Executive Board Deliberation, Organizational Channels, Direct Messages, Official Announcements, Meetings & MoM, Strategic Directives, Action Items, Search & Notifications).
  - Mounted in `NavigationHeader.tsx`, `App.tsx`, and `DashboardShell.tsx`.
- **Testing & Quality Assurance**:
  - `CollaborationTest.php`: 12 PHPUnit tests, 87 assertions passing 100% (99 total backend tests, 686 assertions passing 100%).
  - `scripts/test_backend.php`: Section 14 added, 201/201 REST integration endpoints passing 100%.
  - `npm --prefix frontend run build`: Clean production compilation with Vite (0 errors).
- **Documentation**:
  - Created `docs/architecture/INTERNAL-COMMUNICATION-ARCHITECTURE.md`.
  - Created `docs/architecture/MESSAGING-DATA-MODEL.md`.
  - Created `docs/architecture/EXECUTIVE-COORDINATION-WORKFLOW.md`.
  - Created `docs/architecture/MEETING-MANAGEMENT-MoM.md`.
  - Created `docs/architecture/NOTIFICATION-SYSTEM-ARCHITECTURE.md`.
  - Created `docs/operations/COLLABORATION-OPERATIONS.md`.
  - Created `docs/api/COLLABORATION-API.md`.
  - Created `docs/implementation/COLLABORATION-IMPLEMENTATION.md`.

## [0.10.0] - 2026-11-20 — Knowledge Management, Organizational Memory, Wallbook & Agent Knowledge Sharing Milestone (Prompt 25)

### Added
- **Database Schema Batch 10 (`CreateKnowledgeManagementAndMemorySchema.php`)**:
  - Implemented 11 relational tables: `knowledge_categories`, `knowledge_records`, `knowledge_versions`, `knowledge_access_policies`, `knowledge_contributions`, `documents`, `document_versions`, `agent_memories`, `agent_memory_versions`, `wallbook_handover_checklists`, `knowledge_retrieval_logs`.
  - Added 5-domain categorization: `corporate`, `operational`, `project`, `technical`, `agent_knowledge`.
  - Added 5-tier information classification: `public`, `internal`, `confidential`, `restricted`, `highly_restricted`.
  - Added immutable knowledge and document versioning with cryptographic SHA-256 checksums and MIME type tracking.
  - Added 4 cognitive agent memory types: `working_memory`, `episodic_memory`, `semantic_memory`, `procedural_memory`.
  - Added shift handover quality checklists (`wallbook_handover_checklists`) extending the existing Wallbook module without duplication.
- **Database Seeder (`KnowledgeManagementSeeder.php`)**:
  - Seeded 7 categories, 5 baseline authoritative knowledge articles with version 1.0 entries.
  - Seeded 3 controlled documents (Company Constitution, Security Policy, Architecture Spec).
  - Seeded 4 baseline agent memories for Director AI and Tech Lead AI.
  - Seeded 4-point handover quality checklists for existing shift handovers.
- **Backend Domain Services**:
  - `KnowledgeAccessService.php`: Least-privilege clearance matrix and audience filtering.
  - `KnowledgeBaseService.php`: Article lifecycle management, version history, status state machine, overview aggregation.
  - `KnowledgeContributionService.php`: Candidate submission, Zero-Self-Review peer reviews, automated recognition crediting in `ai_agent_performance_history`.
  - `DocumentManagementService.php`: Controlled document repository, cryptographic validation, revisioning.
  - `AgentMemoryService.php`: Agent memory enclaves with privacy boundaries and memory-to-knowledge promotion.
  - `WallbookKnowledgeBridgeService.php`: 4-point handover quality checklists and shift observation knowledge promoter.
  - `KnowledgeSearchService.php`: Multi-domain fulltext search, RAG context retrieval for Hermes runtime, prompt injection safety, forensic query logging.
- **REST API Controller & Routes**:
  - `KnowledgeController.php`: 26 REST API endpoints under `/api/v1/knowledge/*` with standardized envelopes and auth filters.
- **Frontend Integration**:
  - `knowledge.ts`: Complete TypeScript types and interfaces.
  - `knowledgeService.ts`: API client service for all 26 endpoints.
  - `KnowledgeManagementView.tsx`: Enterprise knowledge view with 8 operational tabs (Overview, Knowledge Base, Documents, Agent Memory, Wallbook Bridge, Contributions, Search, AI RAG Retrieval).
  - Mounted in `NavigationHeader.tsx`, `App.tsx`, and `DashboardShell.tsx`.
- **Testing & Quality Assurance**:
  - `KnowledgeManagementTest.php`: 12 PHPUnit tests, 83 assertions passing 100% (87 total backend tests, 597 assertions passing 100%).
  - `scripts/test_backend.php`: Section 13 added, 157/157 REST integration endpoints passing 100%.
  - `npm --prefix frontend run build`: Clean production compilation with Vite (0 errors).
- **Documentation**:
  - Created `docs/architecture/KNOWLEDGE-MANAGEMENT-ARCHITECTURE.md`.
  - Created `docs/architecture/ORGANIZATIONAL-MEMORY-MODEL.md`.
  - Created `docs/architecture/AGENT-MEMORY-ARCHITECTURE.md`.
  - Created `docs/architecture/WALLBOOK-KNOWLEDGE-INTEGRATION.md`.
  - Created `docs/operations/KNOWLEDGE-OPERATIONS.md`.
  - Created `docs/api/KNOWLEDGE-API.md`.
  - Created `docs/implementation/KNOWLEDGE-IMPLEMENTATION.md`.

---

## [0.9.0] - 2026-11-20 — Agent Welfare Facilities, Welfare Tickets & Agent Welfare Operations Milestone (Prompt 24)

### Added
- **Database Schema Batch 9 (`CreateAgentWelfareAndTicketsSchema.php`)**:
  - Implemented 10 relational tables: `welfare_policies`, `welfare_compensation_periods`, `welfare_period_calculations`, `welfare_task_bonus_evaluations`, `welfare_special_recognitions`, `welfare_ticket_transactions`, `welfare_agent_balances`, `welfare_facilities`, `welfare_facility_usage_logs`, `welfare_audit_trail`.
  - Added double-entry authoritative immutable ledger with SHA-256 idempotency hash and reversal tracing.
  - Added materialized balance cache with automated ledger reconciliation proof (`is_reconciled`).
  - Added cross-divisional VHQ facility mappings to `BLD-WELFARE`, `ROOM-WLF-REST`, and `ROOM-WLF-LEARN`.
- **Database Seeder (`AgentWelfareSeeder.php`)**:
  - Seeded baseline active policy `POL-WLF-2026-V1` (10 WT per 5/2 duty cycle, 500 WT batch limit).
  - Seeded VHQ welfare facilities `FAC-WLF-REST-01` and `FAC-WLF-LEARN-01`.
  - Initialized balances for all 6 verified enterprise AI workforce agents.
  - Seeded completed historical compensation cycle `WCP-2026-W46` with 6 distributed calculations.
  - Seeded sample verified task bonus (`AGT-DEV-001`) and special recognition (`AGT-TECH-001`).
- **Backend Domain Services**:
  - `WelfarePolicyService.php`: Policy versioning, proposal, and activation.
  - `WelfareCompensationService.php`: 5/2 duty fulfillment engine, period approval, and ledger distribution.
  - `WelfareBonusService.php`: Verified task bonus evaluation, Zero-Self-Review gate, and performance history linkage.
  - `WelfareRecognitionService.php`: Special recognition nomination, DoA level enforcement, and ledger crediting.
  - `WelfareLedgerService.php`: Immutable ledger queries, balance retrieval, mathematical reconciliation, adjustments, and reversals.
  - `WelfareFacilityService.php`: VHQ facilities listing with live occupancy, check-in, check-out, and session logging.
  - `WelfareOverviewService.php`: Campus-wide welfare metrics aggregation.
- **REST API Controller & Routes**:
  - `WelfareController.php`: 24 endpoints under `/api/v1/welfare/*` with standardized envelopes and auth filters.
- **Frontend Integration**:
  - `welfare.ts`: Complete TypeScript types and interfaces.
  - `welfareService.ts`: API client service for all 24 welfare endpoints.
  - `WelfareManagementView.tsx`: Enterprise welfare management view with 7 operational tabs (Overview, Policies, Base Compensation, Task Bonuses, Special Recognitions, Immutable Ledger, Facilities).
  - Updated `NavigationHeader.tsx`, `App.tsx`, and `DashboardShell.tsx` to support Agent Welfare navigation.
- **Testing & Quality Assurance**:
  - `WelfareTest.php`: 11 PHPUnit tests, 85 assertions passing 100% (75 total backend tests, 500 assertions passing 100%).
  - `scripts/test_backend.php`: 130/130 REST integration endpoints passing 100%.
  - `npm --prefix frontend run build`: Clean production compilation with Vite (0 errors).
- **Documentation**:
  - Created `docs/architecture/WELFARE-TICKETS-SPECIFICATION.md`.
  - Created `docs/architecture/WELFARE-FACILITIES-VHQ.md`.
  - Created `docs/operations/WELFARE-OPERATIONS.md`.
  - Created `docs/api/WELFARE-API.md`.
  - Created `docs/implementation/WELFARE-IMPLEMENTATION.md`.

---

## [0.8.0] - 2026-11-20 — Virtual Headquarters (VHQ) 3D Environment & Real-Time Presence Milestone (Prompt 23)

### Added
- **Database Schema Batch 8 (`CreateVirtualHeadquartersSpatialSchema.php`)**:
  - Implemented 5 spatial relational tables: `hq_buildings`, `hq_floors`, `hq_rooms`, `hq_workstations`, `hq_camera_bookmarks`.
  - Added building metadata with campus zones, types (`tower`, `pavilion`, `hall`, `facility`, `welfare_center`), 3D Cartesian coordinates, dimensions, and theme/accent colors.
  - Added floor tracking with elevations and dimensions.
  - Added functional rooms categorizing zones (`reception`, `executive`, `workspace`, `monitoring`, `welfare_rest`, `welfare_learning`).
  - Added workstation physical coordinates, rotation, equipment type, and single-desk agent assignment foreign keys.
  - Added camera navigation bookmarks with preset positions and look-at targets.
- **Database Seeder (`VirtualHeadquartersSeeder.php`)**:
  - Seeded 6 campus buildings: Main Executive Tower (`BLD-MAIN`), Technology Pavilion (`BLD-DIV-TECH`), Services Pavilion (`BLD-DIV-SRV`), Academy Center (`BLD-DIV-ACAD`), Hardware Labs (`BLD-DIV-HW`), and the mandatory **AI Agent Welfare Commons** (`BLD-WELFARE`).
  - Seeded 7 floors and 10 operational rooms across all campus buildings.
  - Seeded 8 workstations mapped to verified enterprise AI agents (`AGT-DIR-001`, `AGT-TECH-001`, `AGT-SRV-001`, `AGT-ACD-001`, `AGT-DEV-001`, `AGT-HDW-001`) plus 2 welfare recovery pods.
  - Seeded 6 camera navigation bookmarks (`BM-CAMPUS`, `BM-LOBBY`, `BM-EXEC`, `BM-TECH`, `BM-WELFARE`, `BM-HW`).
- **Backend Domain Services & REST Endpoints**:
  - `VirtualHeadquartersService.php`: Implemented `getCampusOverview()`, `getBuildingDetail()`, `listRooms()`, `listWorkstations()`, `getAgentPresence()`, `listAllAgentPresences()`, `getCameraBookmarks()`, and `assignWorkstation()`.
  - High-fidelity presence calculation linking `workforce_agent_availability` (`on_leave`), `workforce_duty_assignments` (`scheduled_off`), `workforce_tasks` (`working`), and `ai_agent_runtime_mappings` (`runtime_disconnected`).
  - `VhqController.php`: REST controller exposing `/api/v1/vhq/*` endpoints with standardized JSON envelopes.
- **Three.js 3D Viewport & Rendering Architecture**:
  - `CampusRenderer.ts`: Procedural generation of campus terrain, pathways, courtyards, building enclosures, glass partitions, and workstation furniture.
  - `AgentAvatarRenderer.ts`: Stylized 3D agent character with status indicator rings matching true operational status (`available`, `working`, `on_leave`, `scheduled_off`, `suspended`, `runtime_disconnected`), overhead nameplate sprites, and idle bobbing animation.
  - `VhqCanvas.tsx`: Three.js viewport controller with smooth cubic ease-out camera tweening, orbit azimuth/polar controls, wheel/pinch zoom, raycaster selection, and deterministic GPU memory disposal.
- **HUD Overlay, Navigation & 2D Fallback**:
  - `VhqOverlay.tsx`: Interactive HUD with campus metrics, real-time Search & Locate agent, Camera Bookmarks bar, mini-map / room selector, and Agent Detail slide-out drawer.
  - `VhqFallback2D.tsx`: High-fidelity 2D interactive campus directory and floor map for WebGL failure fallback or low-power devices.
  - `VhqView.tsx`: Integrated container mounting canvas, HUD, and fallback with 8-second presence refresh.
- **Documentation**:
  - Created `docs/architecture/VHQ-SPATIAL-ARCHITECTURE.md`.
  - Created `docs/architecture/VHQ-THREEJS-IMPLEMENTATION.md`.
  - Created `docs/architecture/AGENT-WELFARE-BUILDING.md`.
  - Created `docs/operations/VHQ-OPERATIONS.md`.
  - Created `docs/api/VHQ-API.md`.
  - Created `docs/implementation/VHQ-IMPLEMENTATION.md`.

---

## [0.7.0] - 2026-11-20 — Hermes AI Agent Runtime & 9Router Model Gateway Integration Milestone (Prompt 22)

### Added
- **Database Schema Batch 7 (`CreateHermesRuntimeAnd9RouterSchema.php`)**:
  - Implemented 9 relational tables: `runtime_environments`, `model_routing_profiles`, `runtime_profiles`, `runtime_tools_registry`, `execution_sessions`, `runtime_execution_events`, `runtime_tool_invocations`, `model_requests_usage`, `runtime_health_snapshots`.
  - Added Hermes runtime node environment tracking with endpoints, latency, and status (`connected`, `simulated`, `disconnected`).
  - Added 9Router model routing profiles with primary and fallback models, context window limits, and token cost rates in IDR.
  - Added Hermes sandbox runtime profiles with execution duration limits, tool invocation quotas, and filesystem/network isolation policies.
  - Added Controlled Tool Registry with risk levels (`low`, `medium`, `high`, `critical`) and explicit human approval requirements.
  - Added execution sessions tracking with cryptographic SHA-256 idempotency deduplication (`task:agent:nonce`).
  - Added chronological execution events audit logging.
  - Added authoritative 9Router token consumption and IDR cost accounting per session, task, and route.
- **Database Seeder (`RuntimeIntegrationSeeder.php`)**:
  - Seeded 2 environments (`ENV-HERMES-LOCAL`, `ENV-HERMES-PROD`).
  - Seeded 5 model routes (`ROUTE-FAST-REASONING`, `ROUTE-DEEP-RESEARCH`, `ROUTE-CODE-GEN`, `ROUTE-HARDWARE-AUDIT`, `ROUTE-GOVERNANCE`).
  - Seeded 6 workforce runtime profiles mapped to verified workforce positions.
  - Seeded 6 controlled tools (`TOOL-CODE-DIFF`, `TOOL-DOC-AUDIT`, `TOOL-SCHEMA-VALIDATE`, `TOOL-METRICS-QUERY`, `TOOL-GPU-HEALTH`, `TOOL-DB-MUTATE`).
  - Seeded runtime mappings for all 6 verified workforce agents (`AGT-DIR-001`, `AGT-TECH-001`, `AGT-SRV-001`, `AGT-ACAD-001`, `AGT-HW-001`, `AGT-DEV-001`).
  - Seeded completed baseline execution session with events and tool invocations.
- **Backend Domain Services**:
  - `HermesRuntimeAdapter.php`: REST adapter with live daemon dispatch and high-fidelity controlled simulation fallback.
  - `NineRouterGatewayAdapter.php`: Sole model gateway enforcement, routing profiles, token accounting, and fallback.
  - `ExecutionAuthorizationService.php`: 15 execution preconditions gate before dispatch.
  - `ExecutionDispatchService.php`: Scoped context assembly, idempotency deduplication, session management, and deliverable submission.
  - `ToolAuthorizationService.php`: Controlled tool registry, input sanitization against prompt injection, and human approval enforcement for critical tools.
  - `AgentProvisioningService.php`: 12-step readiness validation and runtime lifecycle management.
  - `RuntimeHealthService.php`: Aggregate health diagnostics across runtime and model gateway.
- **REST API Endpoints**:
  - `/api/v1/runtime/health` & `/api/v1/runtime/status`: Aggregate runtime and 9Router health diagnostics.
  - `/api/v1/runtime/environments`: Configured Hermes execution environments.
  - `/api/v1/runtime/profiles`: Workforce agent runtime profiles.
  - `/api/v1/runtime/tools`: Controlled sandbox tool catalog.
  - `/api/v1/runtime/tools/invoke`: Authorize and invoke tool within sandbox.
  - `/api/v1/runtime/executions`: List execution sessions and dispatch authorized tasks to Hermes.
  - `/api/v1/runtime/executions/{id}`: Detailed session audit with events and tool invocations.
  - `/api/v1/runtime/executions/{id}/cancel`: Session cancellation.
  - `/api/v1/runtime/agents/{id}/*`: Agent runtime mapping, provisioning, suspension, and 12-step readiness validation.
  - `/api/v1/model-gateway/*`: 9Router status, routes, token & cost usage accounting, and completions dispatch.
- **Frontend Types, Services & UI Integration**:
  - Created `types/runtime.ts` and `services/runtimeService.ts`.
  - Created `components/RuntimeManagementView.tsx` with 6 operational tabs:
    1. 🌐 Infrastructure & Environments
    2. 🚀 Execution Sessions Monitor with expandable event/tool audit drawer
    3. 🎭 Agent Runtime Profiles
    4. 🛡️ 9Router Model Gateway & Interactive Test Console
    5. 🔧 Controlled Tool Registry with new tool registration modal
    6. 🎯 Task Execution Dispatcher with 12-step pre-dispatch validation
  - Added `⚡ AI Runtime & 9Router` tab in `DashboardShell.tsx`.
- **Comprehensive Verification**:
  - 54/54 backend PHPUnit unit tests passing with 296 assertions across all 8 test suites.
  - 97/97 E2E backend integration endpoints passing (100% pass rate).
  - Frontend TypeScript & Vite production bundle built in 7.07s with zero errors.
  - Root logic tests passing with 100% pass rate.

---

## [0.6.0] - 2026-11-20 — AI Agent Task Management, Delegation, Execution Workflow & Multi-Agent Collaboration Milestone (Prompt 21)

### Added
- **Database Schema Batch 6 (`CreateTaskManagementAndCollaborationSchema.php`)**:
  - Implemented 11 relational tables: `workforce_tasks`, `task_assignments`, `task_delegations`, `task_collaboration_groups`, `task_collaboration_members`, `task_dependencies`, `task_execution_attempts`, `task_deliverables`, `task_reviews`, `task_blockers`, `task_events_audit`.
  - Added 17-state lifecycle state machine (`draft` -> `proposed` -> `awaiting_approval` -> `approved` -> `ready` -> `assigned` -> `awaiting_acceptance` -> `accepted` -> `in_progress` -> `blocked` -> `under_review` -> `revision_required` -> `verified` -> `completed` -> `suspended` -> `cancelled` -> `reopened`).
  - Added parent/subtask work packaging (`parent_task_id`), binding directive origin flag (`is_binding_instruction`), and DoA approval levels.
  - Added formal task delegation engine with circular delegation loop prevention ($A \rightarrow B \rightarrow A$ rejection).
  - Added multi-agent collaboration groups with designated lead agents and specialist roles.
  - Added task dependency engine with cycle detection (`hasDirectedPath`) and Finish-to-Start satisfaction tracking.
  - Added deliverable versioning (`v1`, `v2`...) with artifact URI links.
  - Added independent peer review engine with strict Zero-Self-Review enforcement and direct linkage to `ai_agent_performance_history`.
  - Added operational blocker reporting (auto-transitions to `blocked`) and resolution (auto-resumes to `in_progress`).
  - Added Wallbook handover continuity via `getActiveTasksForAgent`.
- **Database Seeder (`TaskManagementSeeder.php`)**:
  - Seeded 6 initial realistic tasks across Executive, Tech, Services, Academy, and Hardware divisions (`TSK-2026-001` through `TSK-2026-006`).
  - Seeded parent-child hierarchy, Finish-to-Start dependency, multi-agent collaboration group `COL-2026-001`, formal delegation, deliverable, independent review, and verified performance history linkage.
- **Backend Domain Services**:
  - `TaskManagementService.php`: Task CRUD, 17-state transition matrix validation, aggregate metrics, active tasks for Wallbook handover continuity.
  - `TaskDelegationService.php`: Delegator authority checking, circular delegation cycle prevention, acceptance/rejection lifecycle.
  - `TaskCollaborationService.php`: Collaboration groups workspace, specialist role assignments.
  - `TaskDependencyService.php`: Directed graph traversal for cycle prevention, prerequisite satisfaction checking.
  - `TaskReviewService.php`: Deliverable versioning, zero-self-review enforcement, verified contribution performance history integration, blocker reporting/unblocking.
- **REST API Endpoints**:
  - `/api/v1/workforce/tasks/*`: Tasks registry, metrics, task inspect, creation, assignment, 17-state transitions, active tasks for agent.
  - `/api/v1/workforce/tasks/delegations/*`: Delegation register, creation, acceptance.
  - `/api/v1/workforce/tasks/collaborations/*`: Collaboration groups, creation, member assignments.
  - `/api/v1/workforce/tasks/{id}/dependencies`: Task dependency graph establish/list.
  - `/api/v1/workforce/tasks/{id}/deliverables`: Deliverables submission and list.
  - `/api/v1/workforce/tasks/{taskId}/deliverables/{delivId}/review`: Independent review & verified contribution integration.
  - `/api/v1/workforce/tasks/{id}/blockers`: Blocker reporting and resolution.
- **Frontend Types, Services & UI Integration**:
  - Created `types/tasks.ts` and `services/taskService.ts`.
  - Created `components/TaskManagementView.tsx` with 4 operational sub-views:
    1. 📋 Task Registry & Work Packages with filters, search, and hierarchy badges.
    2. 📊 Visual Kanban State Machine (Ready/Assigned, In Progress, Under Review, Blocked, Completed).
    3. 🤝 Delegation Register with authority limit badges and acceptance actions.
    4. 👥 Multi-Agent Collaboration Groups with specialist rosters.
    5. Detailed Task Inspector Drawer with 17-state visual stepper, subtasks, deliverables, independent review submission, and blocker resolution.
  - Added `📋 Task Control Plane` tab in `DashboardShell.tsx`.
- **Comprehensive Verification**:
  - 44/44 backend PHPUnit unit tests passing with 227 assertions (including `TaskOperationsTest.php`).
  - 85/85 API endpoints passing in `scripts/test_backend.php` (100% success rate).
  - Clean TypeScript & Vite build via `npm --prefix frontend run build` (0 errors, 769ms).
  - Clean Node.js logic tests via `npm test` (0 errors).
- **Architecture & Implementation Documentation**:
  - Created `docs/architecture/AI-TASK-MANAGEMENT.md`.
  - Created `docs/architecture/AGENT-DELEGATION.md`.
  - Created `docs/architecture/MULTI-AGENT-COLLABORATION.md`.
  - Created `docs/architecture/TASK-EXECUTION-WORKFLOW.md`.
  - Created `docs/architecture/TASK-WALLBOOK-INTEGRATION.md`.
  - Created `docs/api/TASK-MANAGEMENT-API.md`.
  - Created `docs/implementation/TASK-MANAGEMENT-IMPLEMENTATION.md`.

## [0.5.0] - 2026-11-20 — Agent Scheduling, Shift Coverage, Replacement & Wallbook Handover Milestone (Prompt 20)

### Added
- **Database Schema Batch 5 (`CreateWorkforceSchedulingAndWallbookSchema.php`)**:
  - Implemented 11 relational tables: `workforce_schedule_policies`, `workforce_shifts`, `workforce_rosters`, `workforce_duty_assignments`, `workforce_agent_availability`, `workforce_coverage_requirements`, `workforce_replacement_proposals`, `wallbook_handovers`, `wallbook_handover_items`, `wallbook_acknowledgements`, `workforce_scheduling_audit`.
  - Added support for standard 5-working-day / 2-rest-day policy (`POL-5-2-STD`) with staggered rest schedules.
  - Added timezone-aware shifts (`Asia/Jakarta`) with midnight crossing support.
  - Added 13 structured sections for operational Wallbook handovers.
  - Added atomic replacement activation with historical assignment preservation (`reassigned` status).
- **Scheduling & Wallbook Seeder (`WorkforceSchedulingSeeder.php`)**:
  - Seeded standard 5/2 policy and 24/7 technical continuity policy.
  - Seeded 4 standard shift definitions (Morning, Afternoon, Night, Standard Day).
  - Seeded 6 operational coverage requirements across executive, tech, services, hardware, and academy units.
  - Seeded Master Current Week Roster (`RST-2026-W47`) with **42 staggered 5/2 duty assignments** preserving continuous operational coverage.
  - Seeded sample accepted replacement proposal and Wallbook handover with 5 structured section items.
- **Backend Domain Services**:
  - `WorkforceSchedulingService.php`: Policies, shift configuration, cycle rosters, duty assignment conflict checking, and off-duty transition enforcement (mandating replacement & Wallbook completion).
  - `OperationalCoverageService.php`: Real-time coverage evaluation algorithm comparing requirements against qualified/available agents; calculates `covered`, `coverage_at_risk`, `partially_covered`, `pending_replacement`, and `uncovered` states.
  - `AgentReplacementService.php`: Algorithmic candidate discovery (skills, unit compatibility, active lifecycle, zero schedule conflicts); proposal lifecycle and atomic transactional activation.
  - `WallbookHandoverService.php`: 13 structured sections management, mandatory section validation on submission, incoming agent acknowledgement/clarification workflow, and automated overdue escalation.
- **REST API Endpoints**:
  - `/api/v1/workforce/scheduling/*`: Policies, shifts, rosters, duty assignments, availability, coverage status & gaps.
  - `/api/v1/workforce/replacements/*`: Candidate discovery, proposal creation, acceptance, approval, activation.
  - `/api/v1/wallbook/*`: Handover inbox/outbox, structured items, submission, acknowledgement, clarification, acceptance, overdue escalation.
- **Frontend Types, Services & UI Integration**:
  - Created `types/scheduling.ts` and `services/schedulingService.ts`.
  - Created `components/SchedulingView.tsx` with 4 operational tabs:
    1. 📅 5/2 Staggered Roster Calendar with shift badges and duty completion toggle.
    2. 🛡️ Operational Coverage Monitor with real-time status pills and gap alerts.
    3. 🔄 Qualified Replacement Center with candidate matching and atomic activation.
    4. 📖 Wallbook Handover Center with 13 structured sections inspector, acknowledgement workflow, and overdue scanner.
  - Added `📅 Scheduling & Wallbook` navigation tab in `DashboardShell.tsx`.
- **Architecture & Implementation Documentation**:
  - Created `docs/architecture/WORKFORCE-SCHEDULING.md`.
  - Created `docs/architecture/OPERATIONAL-COVERAGE.md`.
  - Created `docs/architecture/AGENT-REPLACEMENT.md`.
  - Created `docs/architecture/WALLBOOK-HANDOVER.md`.
  - Created `docs/api/WORKFORCE-API.md`.
  - Created `docs/implementation/WORKFORCE-SCHEDULING-IMPLEMENTATION.md`.

### Changed
- Expanded PHPUnit unit test suite to 34 unit tests (166 assertions, 100% passing) including `SchedulingTest.php`.
- Expanded integration test suite `scripts/test_backend.php` to 68 endpoints covering scheduling, coverage evaluation, replacement candidate discovery, off-duty enforcement, and Wallbook handover (68/68 passing).
- Guaranteed that planned off-duty transitions cannot be completed while leaving duties unreplaced or Wallbook handovers unacknowledged.
- Preserved existing Fase 1/2 and Prompts 16–19 databases, tables, and UI views without regressions.

---

## [0.4.0] - 2026-11-20 — AI Agent Registry, Identity Mapping & Workforce Lifecycle Milestone (Prompt 19)

### Added
- **Database Schema Batch 4 (`CreateAgentRegistryAndWorkforceSchema.php`)**:
  - Implemented 9 relational tables: `ai_agent_registry`, `ai_agent_profiles`, `ai_agent_assignments`, `ai_agent_responsibilities`, `skill_catalogue`, `ai_agent_skills`, `ai_agent_runtime_mappings`, `ai_agent_performance_history`, `ai_agent_lifecycle_events`.
  - Added RFC 4122 v4 UUID generator and enterprise `agent_code` unique indexing.
  - Added policy compatibility columns: `roster_schedule_type` (`5_2_staggered`), `welfare_tier` (`tier_standard`), and `designated_replacement_id`.
  - Added Individual Agent Performance History schema with immutable superseding pattern (`superseded_by_id`) and evidence verification workflow.
- **AI Agent Registry Seeder (`AgentRegistrySeeder.php`)**:
  - Seeded 8 core enterprise competencies in `skill_catalogue` covering system architecture, full-stack, AI runtime, LLM-Ops, corporate governance, agile coordination, curriculum design, and IoT/embedded systems.
  - Registered and preserved the **6 verified workforce members** established in Phase 1/2 (`director`, `tech_head`, `services_head`, `academy_head`, `dev_ai`, `hardware_head`).
  - Seeded complete organizational profiles, initial active unit assignments, structured operational responsibilities, skill proficiency ratings, inaugural performance timeline records, and Hermes runtime mapping placeholders.
- **Backend Domain Services**:
  - `AgentRegistryService.php`: UUID v4 generation, paginated agent listing with multi-field search and filters, agent identity creation in `draft` state, profile management, and aggregate workforce metrics calculation.
  - `AgentWorkforceService.php`: 10-state validated lifecycle state machine, unit reassignment preserving historical placements, responsibility management, skill catalogue assignment and verification, and Individual Agent Performance History with evidence verification and the immutable Superseded Record Pattern.
- **REST API Endpoints**:
  - `GET /api/v1/workforce/metrics`: Real-time aggregate workforce numbers.
  - `GET /api/v1/workforce/agents`, `GET /api/v1/workforce/agents/(:num)`: Paginated listing and complete agent details.
  - `POST /api/v1/workforce/agents`, `PUT /api/v1/workforce/agents/(:num)`: Agent identity creation and updates.
  - `PUT /api/v1/workforce/agents/(:num)/profile`: Extended profile and communication preferences updates.
  - `GET /api/v1/workforce/agents/(:num)/lifecycle`, `POST /api/v1/workforce/agents/(:num)/transition`: Validated lifecycle transitions with audit logging.
  - `GET /api/v1/workforce/agents/(:num)/assignments`, `POST /api/v1/workforce/agents/(:num)/assignments`, `POST /api/v1/workforce/assignments/(:num)/end`: Historical assignment tracking.
  - `GET /api/v1/workforce/skills`, `POST /api/v1/workforce/skills`: Enterprise skill catalogue.
  - `GET /api/v1/workforce/agents/(:num)/skills`, `POST /api/v1/workforce/agents/(:num)/skills`, `POST /api/v1/workforce/skills/verify`: Skill proficiencies.
  - `GET /api/v1/workforce/agents/(:num)/responsibilities`, `POST /api/v1/workforce/agents/(:num)/responsibilities`: Structured duties.
  - `GET /api/v1/workforce/agents/(:num)/performance-history`, `POST /api/v1/workforce/agents/(:num)/performance-history`, `POST /api/v1/workforce/performance-history/(:num)/verify`, `POST /api/v1/workforce/performance-history/(:num)/amend`: Individual Agent Performance History.
  - `GET /api/v1/workforce/agents/(:num)/runtime-mappings`, `POST /api/v1/workforce/agents/(:num)/runtime-mappings`: Hermes/9Router technical identity mappings.
- **Frontend Types, Services & UI Integration**:
  - Created `types/workforce.ts` and `services/workforceService.ts`.
  - Created `components/WorkforceView.tsx` with metrics cards, filterable agent list, search, agent registration modal, and detailed inspector drawer (overview, profile, lifecycle history, unit assignments, skills & competencies, responsibilities, performance timeline with evidence verification).
  - Added `🤖 AI Workforce Registry` navigation tab to `DashboardShell.tsx`.
- **Architecture & Implementation Documentation**:
  - Created `docs/architecture/AI-AGENT-IDENTITY.md`.
  - Created `docs/architecture/AI-WORKFORCE-DOMAIN.md`.
  - Created `docs/architecture/AGENT-LIFECYCLE.md`.
  - Created `docs/architecture/AGENT-PERFORMANCE-HISTORY.md`.
  - Created `docs/api/AI-WORKFORCE-API.md`.
  - Created `docs/implementation/AGENT-REGISTRY-IMPLEMENTATION.md`.

### Changed
- Expanded PHPUnit unit test suite to 26 unit tests (89 assertions, 100% passing) including `WorkforceTest.php`.
- Expanded integration test suite `scripts/test_backend.php` to 51 endpoints covering all workforce lifecycle transitions, skills verification, historical assignments, and performance history (51/51 passing).
- Maintained clean separation of 3 Identity Domains (Human Users vs AI Agents vs Runtime Executions).
- Preserved existing Fase 1/2 and Prompt 16–18 database tables, models, and UI views without regressions.

---

## [0.3.0] - 2026-11-20 — Organization Structure, Corporate Governance & Delegated Authority Milestone (Prompt 18)

### Added
- **Database Schema Batch 3 (`CreateOrganizationAndGovernanceSchema.php`)**:
  - Implemented 12 relational governance tables: `organization_units`, `organization_positions`, `organization_appointments`, `reporting_relationships`, `governance_bodies`, `governance_memberships`, `authority_profiles`, `delegation_records`, `owner_directions`, `executive_decisions`, `core_values`, `governance_audit_logs`.
  - Added self-referencing hierarchy support (`parent_id`) and unit categorization (`root`, `executive_body`, `division`, `department`, `unit`, etc.).
  - Added support for dual appointee types (`human` and `agent`) with complete historical timeline retention.
- **Organization & Governance Seeder (`OrganizationGovernanceSeeder.php`)**:
  - Seeded 7 immutable Core Values (`integrity`, `innovation`, `collaboration`, `excellence`, `adaptability`, `customer_centric`, `sustainability`).
  - Seeded 4 DoA Authority Profiles (Level A, Level B, Level C, Routine Operational).
  - Seeded 3 Governance Bodies (`GOV-OWNER`, `GOV-BOARD`, `GOV-DIR-AI`).
  - Seeded enterprise Root `ORG-MCE` and 6 operational divisions (`technology`, `operations`, `services`, `hardware`, `commerce`, `academy`).
  - Seeded key leadership positions (`POS-OWNER`, `POS-DIR-AI`, `POS-HEAD-TECH`, `POS-HEAD-SRV`, `POS-HEAD-ACD`) and initial appointments.
  - Seeded inaugural Owner Direction (`DIR-OWNER-2026-001`) and Executive Board Decision (`DEC-BOARD-2026-001`).
- **Backend Organization & Governance Domain Services**:
  - Implemented `OrganizationService.php` with nested tree assembly (`getTree`), cycle detection (`isCircularParent`), position management, and appointee transitions.
  - Implemented `GovernanceService.php` with DoA authority evaluation logic (`checkAuthority`), delegation lifecycle management, Owner Direction acknowledgment, and Executive Decision ratification.
  - Implemented audit logging in `governance_audit_logs` tracking structural mutations.
- **REST API Endpoints**:
  - `GET /api/v1/organization/tree`: Recursive hierarchical organization tree.
  - `GET /api/v1/organization/units`, `GET /api/v1/organization/units/(:num)`: Units flat list and detail.
  - `POST /api/v1/organization/units`, `PUT /api/v1/organization/units/(:num)`: Unit creation and update with cycle prevention.
  - `GET /api/v1/organization/positions`, `POST /api/v1/organization/positions`: Position management.
  - `GET /api/v1/organization/appointments`, `POST /api/v1/organization/appointments`, `POST /api/v1/organization/appointments/(:num)/end`: Appointment lifecycle.
  - `GET /api/v1/organization/reporting`: Reporting relationships graph.
  - `GET /api/v1/governance/core-values`: The 7 Core Values reference data.
  - `GET /api/v1/governance/bodies`, `GET /api/v1/governance/bodies/(:num)`: Governance bodies and memberships.
  - `GET /api/v1/governance/authority-profiles`: DoA profiles catalogue.
  - `POST /api/v1/governance/authority-check`: Real-time authority limit check (Level B <= 5M autonomous vs > 5M requiring Owner).
  - `GET /api/v1/governance/delegations`, `POST /api/v1/governance/delegations`, `POST /api/v1/governance/delegations/(:num)/revoke`: Delegation records.
  - `GET /api/v1/governance/directions`, `POST /api/v1/governance/directions`, `POST /api/v1/governance/directions/(:num)/acknowledge`: Owner directions (advisory vs binding).
  - `GET /api/v1/governance/decisions`, `POST /api/v1/governance/decisions`, `POST /api/v1/governance/decisions/(:num)/ratify`: Executive decisions.
  - `GET /api/v1/governance/audit-logs`: Immutable audit trails.
- **Frontend Types, Services & UI Integration**:
  - Created `types/organization.ts` and `services/organizationService.ts`.
  - Added "🏛️ Organization Hierarchy" tab in `DashboardShell.tsx` featuring an interactive multi-tier tree viewer and positions directory.
  - Added "📜 Corporate Governance & DoA" tab in `DashboardShell.tsx` displaying the 7 Core Values cards, DoA spending matrix, governance bodies, Owner directions table (advisory vs binding), and Executive decisions.
- **Architecture & Implementation Documentation**:
  - Created `docs/architecture/ORGANIZATION-DOMAIN.md`.
  - Created `docs/architecture/GOVERNANCE-DOMAIN.md`.
  - Created `docs/architecture/DELEGATION-OF-AUTHORITY.md`.
  - Created `docs/api/ORGANIZATION-GOVERNANCE-API.md`.
  - Created `docs/implementation/ORGANIZATION-IMPLEMENTATION.md`.

### Changed
- Expanded PHPUnit test suite to 18 unit tests (52 assertions, 100% passing) including `OrganizationTest.php` and `GovernanceTest.php`.
- Expanded integration test suite `scripts/test_backend.php` to 35 endpoints covering organization tree, positions, appointments, DoA limit enforcement, dynamic unit creation, advisory directions, and token revocation (35/35 passing).

---

## [0.2.0] - 2026-11-20 — Authentication, Identity Foundation & Access Control Milestone (Prompt 17)

### Added
- **Database Schema Batch 2 (`CreateIdentityAndRbacSchema.php`)**:
  - Extended `users` table with security lifecycle fields: `status`, `failed_login_attempts`, `locked_until`, `last_login_at`.
  - Created relational RBAC tables: `roles`, `permissions`, `user_roles`, `role_permissions`.
  - Created `user_tokens` table for server-side opaque bearer token persistence with SHA-256 hash at rest.
  - Created `security_events` table for structured security audit logging.
- **RBAC Seeder (`RbacSeeder.php`)**:
  - Seeded 7 core system roles (`owner`, `executive`, `director_ai`, `division_head`, `admin`, `operator`, `auditor`).
  - Seeded 20 granular permissions across `users`, `roles`, `audit`, `governance`, `company`, `agents`, `tasks`, `approvals`, `system`.
  - Mapped role-to-permission relationships and seeded initial bootstrap administrator (`admin`).
- **Backend Authentication & IAM Engine (`AuthService.php`)**:
  - Implemented Argon2id password verification with automatic rehash from legacy algorithms.
  - Implemented brute-force protection: 5-attempt limit with 15-minute account lockout (`locked_until`).
  - Implemented secure 256-bit random opaque Bearer token generation with SHA-256 hash storage and 24-hour TTL.
  - Implemented token revocation on logout and mass-revocation on password change.
  - Implemented `security_events` logging for login success/failure, lockout, logout, and access denial.
- **Authorization Middleware & Security Filters**:
  - Implemented `AuthFilter.php` enforcing Bearer token authentication and loading authenticated user context.
  - Implemented `PermissionFilter.php` validating effective user permissions with implicit Owner bypass and 403 Forbidden enforcement.
- **Authentication & RBAC REST APIs**:
  - `POST /api/v1/auth/login`: Authenticate and issue Bearer token.
  - `GET /api/v1/auth/me`: Retrieve current identity profile, roles, and effective permissions.
  - `POST /api/v1/auth/logout`: Server-side token revocation.
  - `POST /api/v1/auth/change-password`: Authenticated password change with session invalidation.
  - `GET /api/v1/admin/users`, `GET /api/v1/admin/roles`, `GET /api/v1/admin/permissions`, `GET /api/v1/admin/audit-logs`.
- **Frontend Authentication UI & Centralized State**:
  - Created `authService.ts` with token persistence in `localStorage` and dynamic token injection into `apiClient`.
  - Created `LoginPage.tsx` with credential inputs, password toggle, error alert banner, and dev quick-fill accounts.
  - Updated `NavigationHeader.tsx` with authenticated user avatar, display name, role pill, and Sign In / Sign Out actions.
  - Extended `DashboardShell.tsx` with User Profile card, effective permissions badges, and interactive tabs for User Accounts, Roles & Permissions, and Security Audit Logs.
- **Architecture Documentation**:
  - Created `docs/architecture/IDENTITY-ARCHITECTURE.md` (Human vs Agent vs Runtime boundaries).
  - Created `docs/architecture/AUTHENTICATION.md` (Token lifecycle and hash-at-rest design).
  - Created `docs/architecture/AUTHORIZATION-RBAC.md` (Roles, permissions, and evaluation engine).
  - Created `docs/api/AUTHENTICATION-API.md` (Authentication and RBAC API contract).
  - Created `docs/security/IDENTITY-SECURITY.md` (Threat model and mitigation controls).

### Changed
- Configured PHPUnit tests database group in `Database.php` to use MySQLi driver with port 3306.
- Updated `scripts/test_backend.php` to test 20/20 endpoints, including login, authenticated `/auth/me`, RBAC admin endpoints, and post-logout token rejection (all 20 passing).

---

## [0.1.0] - 2026-11-20 — Development Foundation & Environment Setup Milestone (Prompt 16)

### Added
- **Unified Dual-Viewport Routing**:
  - Implemented application routing in React supporting `#dashboard` (Administrative Dashboard Shell) and `#vhq` (3D Virtual Headquarters).
  - Integrated `NavigationHeader` component with real-time API health status badge and ping latency monitor.
- **Administrative Dashboard Shell (`DashboardShell.tsx`)**:
  - Built foundational enterprise dashboard shell displaying live backend engine metrics, database connectivity status, and AI runtime boundary parameters.
  - Implemented interactive API communication console for executing real-time `GET /api/v1/health` and `GET /api/v1/health/database` requests.
- **Frontend API Client Abstraction (`apiClient.ts`)**:
  - Created centralized `ApiClient` service featuring `AbortController` timeout management (8000ms), typed JSON envelopes, standardized error normalization (`ApiError`), and future Bearer token provider hooks.
  - Configured Vite development proxy in `vite.config.ts` mapping `/api` to `http://localhost:8080` for transparent local development.
- **Backend Diagnostic Endpoints (CodeIgniter 4)**:
  - Added `GET /api/v1/health/database` in `HealthController.php` for isolated, safe database connectivity diagnostics without credential leakage.
  - Standardized JSON responses across `BaseApiController.php` using `{ status, code, message, data, meta, timestamp }`.
  - Added unit test coverage in `HealthTest.php` verifying controller instantiation and application paths.
- **CORS & Origin Hardening**:
  - Hardened `CorsFilter.php` to restrict `Access-Control-Allow-Origin` to explicitly authorized frontend URLs (`mce.frontendURL`), deprecating wildcard `*`.
- **Environment & Setup Documentation**:
  - Created `docs/development/ENVIRONMENT.md` with complete toolchain version audit and PHP extension analysis.
  - Created `docs/development/LOCAL_SETUP.md` with step-by-step developer onboarding instructions.
  - Created `docs/architecture/FOUNDATION.md` detailing architectural boundaries and system topology.
  - Created `docs/implementation/IMPLEMENTATION-STATUS.md` tracking verified baseline vs deferred scope.
  - Created `backend/.env.example` and `frontend/.env.example` templates.

### Changed
- Refactored `App.tsx` into a modular orchestrator, extracting the 3D canvas and HUD into `VhqView.tsx` while preserving 100% of the existing Three.js scene, agent styles, and diorama assets.
- Updated `scripts/test_backend.php` to test 10/10 endpoints including `/health/database` (all 10 passing).

### Preserved
- 100% of existing Three.js Virtual Headquarters code (`OfficeScene.tsx`, 17 style variations in `gaya/`, desk schedules in `office.ts`, report data in `laporan.json`).
- Existing MySQL schema and initial seed data (`MceInitialSeeder`) in `backend/app/Database/`.
