<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKnowledgeManagementAndMemorySchema extends Migration
{
    public function up()
    {
        // 1. KNOWLEDGE CATEGORIES
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'category_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'domain' => [
                'type'       => 'ENUM',
                'constraint' => ['corporate', 'operational', 'project', 'technical', 'agent_knowledge'],
                'default'    => 'operational',
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'parent_category_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'default_classification' => [
                'type'       => 'ENUM',
                'constraint' => ['public', 'internal', 'confidential', 'restricted', 'highly_restricted'],
                'default'    => 'internal',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('domain');
        $this->forge->addKey('parent_category_id');
        $this->forge->createTable('knowledge_categories', true);

        // 2. KNOWLEDGE RECORDS (Authoritative Articles & SOPs)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'record_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'category_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'summary' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'content' => [
                'type' => 'MEDIUMTEXT',
            ],
            'content_format' => [
                'type'       => 'ENUM',
                'constraint' => ['markdown', 'plain_text', 'html', 'json'],
                'default'    => 'markdown',
            ],
            'classification' => [
                'type'       => 'ENUM',
                'constraint' => ['public', 'internal', 'confidential', 'restricted', 'highly_restricted'],
                'default'    => 'internal',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'draft', 'submitted', 'under_review', 'approved', 'published',
                    'needs_revision', 'rejected', 'superseded', 'expired', 'archived', 'withdrawn'
                ],
                'default'    => 'draft',
            ],
            'version' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => '1.0',
            ],
            'owner_type' => [
                'type'       => 'ENUM',
                'constraint' => ['unit', 'human_user', 'agent'],
                'default'    => 'unit',
            ],
            'owner_unit_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'author_type' => [
                'type'       => 'ENUM',
                'constraint' => ['human', 'agent'],
                'default'    => 'human',
            ],
            'author_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'author_agent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'provenance_type' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'human_authored', 'agent_generated', 'wallbook_promoted',
                    'executive_directive', 'task_deliverable_distilled'
                ],
                'default'    => 'human_authored',
            ],
            'provenance_source_ref' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'related_task_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'tags_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'effective_from' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'review_due_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'is_latest' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('category_id');
        $this->forge->addKey('classification');
        $this->forge->addKey('status');
        $this->forge->addKey('owner_unit_id');
        $this->forge->addKey('author_agent_id');
        $this->forge->addKey('related_task_id');
        $this->forge->addKey('provenance_source_ref');
        $this->forge->createTable('knowledge_records', true);

        // 3. KNOWLEDGE VERSIONS (Historical Trackability)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'record_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'version_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'summary' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'content' => [
                'type' => 'MEDIUMTEXT',
            ],
            'change_summary' => [
                'type' => 'TEXT',
            ],
            'author_type' => [
                'type'       => 'ENUM',
                'constraint' => ['human', 'agent'],
                'default'    => 'human',
            ],
            'author_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'author_agent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'approved_by_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'approved_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['record_id', 'version_number']);
        $this->forge->createTable('knowledge_versions', true);

        // 4. KNOWLEDGE ACCESS POLICIES (Least Privilege Granular Access)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'record_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'category_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'policy_type' => [
                'type'       => 'ENUM',
                'constraint' => ['allow', 'deny'],
                'default'    => 'allow',
            ],
            'grantee_type' => [
                'type'       => 'ENUM',
                'constraint' => ['role', 'unit', 'agent_classification', 'specific_agent', 'specific_user'],
            ],
            'grantee_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'can_read' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'can_contribute' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'can_approve' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('record_id');
        $this->forge->addKey('category_id');
        $this->forge->addKey(['grantee_type', 'grantee_id']);
        $this->forge->createTable('knowledge_access_policies', true);

        // 5. KNOWLEDGE CONTRIBUTIONS & PROPOSALS (Peer Review Workflow)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'contribution_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'contributor_type' => [
                'type'       => 'ENUM',
                'constraint' => ['human', 'agent'],
                'default'    => 'agent',
            ],
            'contributor_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'contributor_agent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'target_record_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'proposed_category_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'proposed_classification' => [
                'type'       => 'ENUM',
                'constraint' => ['public', 'internal', 'confidential', 'restricted', 'highly_restricted'],
                'default'    => 'internal',
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'summary' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'content' => [
                'type' => 'MEDIUMTEXT',
            ],
            'evidence_references' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'source_context' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['submitted', 'under_review', 'approved', 'rejected', 'revision_requested'],
                'default'    => 'submitted',
            ],
            'reviewer_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'reviewer_agent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'review_feedback' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'reviewed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'published_record_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'performance_history_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('contributor_agent_id');
        $this->forge->addKey('status');
        $this->forge->addKey('performance_history_id');
        $this->forge->createTable('knowledge_contributions', true);

        // 6. CORPORATE & TECHNICAL DOCUMENTS (Metadata & Files)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'document_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'classification' => [
                'type'       => 'ENUM',
                'constraint' => ['public', 'internal', 'confidential', 'restricted', 'highly_restricted'],
                'default'    => 'internal',
            ],
            'category' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'corporate_policy', 'standard_procedure', 'architecture_spec',
                    'project_deliverable', 'handover_evidence', 'technical_manual'
                ],
                'default'    => 'standard_procedure',
            ],
            'owner_unit_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'current_version' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => '1.0',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'active', 'under_review', 'superseded', 'archived'],
                'default'    => 'active',
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'file_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'file_size_bytes' => [
                'type'       => 'BIGINT',
                'unsigned'   => true,
            ],
            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'sha256_checksum' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'uploaded_by_type' => [
                'type'       => 'ENUM',
                'constraint' => ['human', 'agent'],
                'default'    => 'human',
            ],
            'uploaded_by_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'uploaded_by_agent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'is_latest' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('classification');
        $this->forge->addKey('category');
        $this->forge->addKey('owner_unit_id');
        $this->forge->createTable('documents', true);

        // 7. DOCUMENT VERSIONS (Version Control)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'document_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'version_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'file_size_bytes' => [
                'type'       => 'BIGINT',
                'unsigned'   => true,
            ],
            'sha256_checksum' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'change_summary' => [
                'type' => 'TEXT',
            ],
            'created_by_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_by_agent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['document_id', 'version_number']);
        $this->forge->createTable('document_versions', true);

        // 8. INDIVIDUAL AGENT MEMORIES (Working, Episodic, Semantic, Procedural)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'memory_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'agent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'memory_type' => [
                'type'       => 'ENUM',
                'constraint' => ['working_memory', 'episodic_memory', 'semantic_memory', 'procedural_memory'],
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'content' => [
                'type' => 'MEDIUMTEXT',
            ],
            'context_tags_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'source_type' => [
                'type'       => 'ENUM',
                'constraint' => ['task_execution', 'interaction_session', 'wallbook_brief', 'peer_collaboration', 'model_inference'],
                'default'    => 'task_execution',
            ],
            'source_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'classification' => [
                'type'       => 'ENUM',
                'constraint' => ['internal', 'confidential', 'restricted'],
                'default'    => 'internal',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'archived', 'expired', 'discarded'],
                'default'    => 'active',
            ],
            'retention_policy' => [
                'type'       => 'ENUM',
                'constraint' => ['session_ephemeral', 'task_duration', '30_days', '90_days', 'permanent'],
                'default'    => 'permanent',
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'importance_score' => [
                'type'       => 'DECIMAL',
                'constraint' => '3,2',
                'default'    => 0.50,
            ],
            'access_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'last_accessed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('agent_id');
        $this->forge->addKey('memory_type');
        $this->forge->addKey('status');
        $this->forge->addKey('retention_policy');
        $this->forge->createTable('agent_memories', true);

        // 9. AGENT MEMORY VERSIONS (Revision History)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'memory_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'version_number' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'content' => [
                'type' => 'MEDIUMTEXT',
            ],
            'change_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['memory_id', 'version_number']);
        $this->forge->createTable('agent_memory_versions', true);

        // 10. WALLBOOK HANDOVER CHECKLISTS (Handover Verification Extension)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'handover_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'checklist_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'item_label' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'is_mandatory' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'is_satisfied' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'satisfied_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'evidence_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('handover_id');
        $this->forge->addKey('checklist_code');
        $this->forge->createTable('wallbook_handover_checklists', true);

        // 11. KNOWLEDGE RETRIEVAL LOGS (Security, Audit & Provenance)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'query_text' => [
                'type' => 'TEXT',
            ],
            'requester_type' => [
                'type'       => 'ENUM',
                'constraint' => ['human', 'agent'],
                'default'    => 'agent',
            ],
            'requester_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'requester_agent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'task_context_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'records_retrieved_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'classification_filter' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'internal',
            ],
            'retrieval_latency_ms' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'retrieval_method' => [
                'type'       => 'ENUM',
                'constraint' => ['fulltext_search', 'faceted_keyword', 'semantic_hybrid', 'rag_context'],
                'default'    => 'fulltext_search',
            ],
            'security_decision' => [
                'type'       => 'ENUM',
                'constraint' => ['allowed', 'filtered', 'denied'],
                'default'    => 'allowed',
            ],
            'provenance_sources_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('requester_agent_id');
        $this->forge->addKey('task_context_id');
        $this->forge->addKey('security_decision');
        $this->forge->createTable('knowledge_retrieval_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('knowledge_retrieval_logs', true);
        $this->forge->dropTable('wallbook_handover_checklists', true);
        $this->forge->dropTable('agent_memory_versions', true);
        $this->forge->dropTable('agent_memories', true);
        $this->forge->dropTable('document_versions', true);
        $this->forge->dropTable('documents', true);
        $this->forge->dropTable('knowledge_contributions', true);
        $this->forge->dropTable('knowledge_access_policies', true);
        $this->forge->dropTable('knowledge_versions', true);
        $this->forge->dropTable('knowledge_records', true);
        $this->forge->dropTable('knowledge_categories', true);
    }
}
