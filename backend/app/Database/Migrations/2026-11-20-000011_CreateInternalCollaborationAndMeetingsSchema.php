<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInternalCollaborationAndMeetingsSchema extends Migration
{
    public function up()
    {
        // 1. Conversations (Direct & Group chats)
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'conversation_code'   => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'type'                => ['type' => 'ENUM', 'constraint' => ['direct', 'group', 'executive_session'], 'default' => 'direct'],
            'title'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by_user_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_by_agent_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'classification'      => ['type' => 'ENUM', 'constraint' => ['public', 'internal', 'confidential', 'restricted', 'highly_restricted'], 'default' => 'internal'],
            'is_active'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'last_message_at'     => ['type' => 'DATETIME', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('type');
        $this->forge->addKey('classification');
        $this->forge->createTable('collaboration_conversations', true);

        // 2. Conversation Participants
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'conversation_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'participant_type'     => ['type' => 'ENUM', 'constraint' => ['human', 'agent'], 'default' => 'human'],
            'user_id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'agent_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'role'                 => ['type' => 'ENUM', 'constraint' => ['owner', 'admin', 'member'], 'default' => 'member'],
            'last_read_message_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'is_muted'             => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'joined_at'            => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('conversation_id', 'collaboration_conversations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('collaboration_conversation_participants', true);

        // 3. Organizational Channels
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'channel_code'       => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'               => ['type' => 'VARCHAR', 'constraint' => 150],
            'description'        => ['type' => 'TEXT', 'null' => true],
            'domain'             => [
                'type'       => 'ENUM',
                'constraint' => ['executive_coordination', 'corporate_ops', 'division_ops', 'project_collab', 'technical', 'knowledge_sharing', 'announcements', 'operational_alerts'],
                'default'    => 'corporate_ops'
            ],
            'scope_unit_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'classification'     => ['type' => 'ENUM', 'constraint' => ['public', 'internal', 'confidential', 'restricted', 'highly_restricted'], 'default' => 'internal'],
            'is_private'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_archived'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_by_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('scope_unit_id', 'organization_units', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('collaboration_channels', true);

        // 4. Channel Memberships
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'channel_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'member_type'           => ['type' => 'ENUM', 'constraint' => ['human', 'agent'], 'default' => 'human'],
            'user_id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'agent_id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'role'                  => ['type' => 'ENUM', 'constraint' => ['admin', 'moderator', 'member'], 'default' => 'member'],
            'notifications_enabled' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'last_read_message_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'joined_at'             => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('channel_id', 'collaboration_channels', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('collaboration_channel_members', true);

        // 5. Messages
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'message_code'        => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'channel_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'conversation_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'parent_message_id'   => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'sender_type'         => ['type' => 'ENUM', 'constraint' => ['human', 'agent', 'system'], 'default' => 'human'],
            'sender_user_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'sender_agent_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'sender_display_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'content'             => ['type' => 'TEXT'],
            'content_format'      => ['type' => 'ENUM', 'constraint' => ['text', 'markdown', 'system_event'], 'default' => 'markdown'],
            'classification'      => ['type' => 'ENUM', 'constraint' => ['public', 'internal', 'confidential', 'restricted', 'highly_restricted'], 'default' => 'internal'],
            'is_pinned'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_edited'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'edited_at'           => ['type' => 'DATETIME', 'null' => true],
            'is_deleted'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deleted_at'          => ['type' => 'DATETIME', 'null' => true],
            'created_at'          => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('channel_id');
        $this->forge->addKey('conversation_id');
        $this->forge->addKey('parent_message_id');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('channel_id', 'collaboration_channels', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('conversation_id', 'collaboration_conversations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('sender_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('sender_agent_id', 'ai_agent_registry', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('collaboration_messages', true);

        // 6. Message Cross References
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'message_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'reference_type'  => ['type' => 'ENUM', 'constraint' => ['task', 'document', 'knowledge_record', 'wallbook_handover', 'owner_direction', 'executive_decision', 'meeting']],
            'reference_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'reference_code'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'reference_title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'      => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['reference_type', 'reference_id']);
        $this->forge->addForeignKey('message_id', 'collaboration_messages', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('collaboration_message_references', true);

        // 7. Announcements
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'announcement_code'    => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'title'                => ['type' => 'VARCHAR', 'constraint' => 255],
            'content'              => ['type' => 'TEXT'],
            'target_scope'         => ['type' => 'ENUM', 'constraint' => ['all_organization', 'division', 'unit', 'project_team'], 'default' => 'all_organization'],
            'target_unit_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'priority'             => ['type' => 'ENUM', 'constraint' => ['routine', 'important', 'urgent', 'critical'], 'default' => 'routine'],
            'classification'       => ['type' => 'ENUM', 'constraint' => ['public', 'internal', 'confidential', 'restricted', 'highly_restricted'], 'default' => 'internal'],
            'published_by_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'published_by_name'    => ['type' => 'VARCHAR', 'constraint' => 150],
            'is_published'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'published_at'         => ['type' => 'DATETIME', 'null' => true],
            'expires_at'           => ['type' => 'DATETIME', 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('target_unit_id', 'organization_units', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('published_by_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('collaboration_announcements', true);

        // 8. Meetings
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'meeting_code'         => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'title'                => ['type' => 'VARCHAR', 'constraint' => 255],
            'category'             => [
                'type'       => 'ENUM',
                'constraint' => ['board_meeting', 'executive_coordination', 'division_sync', 'cross_division', 'project_meeting', 'technical_review', 'operational_planning'],
                'default'    => 'executive_coordination'
            ],
            'status'               => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'scheduled', 'confirmed', 'in_progress', 'completed', 'minutes_finalized', 'cancelled', 'rescheduled'],
                'default'    => 'scheduled'
            ],
            'classification'       => ['type' => 'ENUM', 'constraint' => ['public', 'internal', 'confidential', 'restricted', 'highly_restricted'], 'default' => 'internal'],
            'room_id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'organizer_user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'organizer_agent_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'scheduled_start_time' => ['type' => 'DATETIME'],
            'scheduled_end_time'   => ['type' => 'DATETIME'],
            'actual_start_time'    => ['type' => 'DATETIME', 'null' => true],
            'actual_end_time'      => ['type' => 'DATETIME', 'null' => true],
            'description'          => ['type' => 'TEXT', 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('room_id', 'hq_rooms', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('organizer_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('organizer_agent_id', 'ai_agent_registry', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('collaboration_meetings', true);

        // 9. Meeting Participants
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'meeting_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'participant_type'  => ['type' => 'ENUM', 'constraint' => ['human', 'agent'], 'default' => 'human'],
            'user_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'agent_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'role'              => ['type' => 'ENUM', 'constraint' => ['chair', 'secretary', 'participant', 'observer'], 'default' => 'participant'],
            'invitation_status' => ['type' => 'ENUM', 'constraint' => ['invited', 'confirmed', 'declined', 'tentative'], 'default' => 'invited'],
            'attendance_status' => ['type' => 'ENUM', 'constraint' => ['unrecorded', 'attended', 'absent', 'excused'], 'default' => 'unrecorded'],
            'notes'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('meeting_id', 'collaboration_meetings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('collaboration_meeting_participants', true);

        // 10. Meeting Agendas
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'meeting_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'item_order'        => ['type' => 'INT', 'default' => 1],
            'title'             => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'       => ['type' => 'TEXT', 'null' => true],
            'allocated_minutes' => ['type' => 'INT', 'default' => 15],
            'presenter_name'    => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'status'            => ['type' => 'ENUM', 'constraint' => ['pending', 'in_discussion', 'concluded', 'tabled'], 'default' => 'pending'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('meeting_id', 'collaboration_meetings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('collaboration_meeting_agendas', true);

        // 11. Minutes of Meeting (MoM)
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'meeting_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'unique' => true],
            'prepared_by_user_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'prepared_by_agent_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'approved_by_user_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'attendance_summary'   => ['type' => 'TEXT', 'null' => true],
            'discussion_notes'     => ['type' => 'TEXT'],
            'formal_decisions'     => ['type' => 'TEXT', 'null' => true],
            'is_approved'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'approved_at'          => ['type' => 'DATETIME', 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('meeting_id', 'collaboration_meetings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('prepared_by_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('prepared_by_agent_id', 'ai_agent_registry', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('approved_by_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('collaboration_meeting_minutes', true);

        // 12. Meeting Action Items
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'meeting_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'agenda_item_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'task_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'description'       => ['type' => 'TEXT'],
            'assignee_type'     => ['type' => 'ENUM', 'constraint' => ['human', 'agent'], 'default' => 'agent'],
            'assignee_user_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'assignee_agent_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'due_date'          => ['type' => 'DATE', 'null' => true],
            'status'            => ['type' => 'ENUM', 'constraint' => ['pending', 'assigned_to_task', 'in_progress', 'completed', 'cancelled'], 'default' => 'pending'],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('meeting_id', 'collaboration_meetings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('agenda_item_id', 'collaboration_meeting_agendas', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('assignee_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('assignee_agent_id', 'ai_agent_registry', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('collaboration_meeting_action_items', true);

        // 13. Directive Acknowledgements & Execution Tracking
        $this->forge->addField([
            'id'                     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'direction_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'acknowledging_agent_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'acknowledging_user_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'status'                 => [
                'type'       => 'ENUM',
                'constraint' => ['acknowledged', 'clarification_requested', 'in_execution', 'executed', 'impediment_reported'],
                'default'    => 'acknowledged'
            ],
            'notes'                  => ['type' => 'TEXT', 'null' => true],
            'acknowledged_at'        => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('direction_id', 'owner_directions', 'id', 'CASCADE', 'CASCADE', 'fk_cda_direction');
        $this->forge->addForeignKey('acknowledging_agent_id', 'ai_agent_registry', 'id', 'SET NULL', 'CASCADE', 'fk_cda_agent');
        $this->forge->addForeignKey('acknowledging_user_id', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_cda_user');
        $this->forge->createTable('collaboration_directive_acknowledgements', true);

        // 14. Notifications Center
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'recipient_type' => ['type' => 'ENUM', 'constraint' => ['human', 'agent'], 'default' => 'human'],
            'user_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'agent_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'title'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'message'        => ['type' => 'TEXT'],
            'type'           => [
                'type'       => 'ENUM',
                'constraint' => ['message', 'mention', 'meeting_invite', 'meeting_reminder', 'decision_published', 'directive_issued', 'approval_requested', 'action_item_assigned', 'announcement', 'alert'],
                'default'    => 'message'
            ],
            'priority'       => ['type' => 'ENUM', 'constraint' => ['low', 'medium', 'high', 'urgent'], 'default' => 'medium'],
            'resource_uri'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_read'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'read_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['recipient_type', 'user_id', 'is_read']);
        $this->forge->addKey(['recipient_type', 'agent_id', 'is_read']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('collaboration_notifications', true);

        // 15. Communication Audit Events
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'event_type'    => ['type' => 'VARCHAR', 'constraint' => 100],
            'actor_type'    => ['type' => 'ENUM', 'constraint' => ['user', 'agent', 'system'], 'default' => 'user'],
            'actor_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'actor_name'    => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'entity_type'   => ['type' => 'VARCHAR', 'constraint' => 50],
            'entity_id'     => ['type' => 'VARCHAR', 'constraint' => 50],
            'metadata_json' => ['type' => 'TEXT', 'null' => true],
            'ip_address'    => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at'    => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('event_type');
        $this->forge->addKey(['entity_type', 'entity_id']);
        $this->forge->addKey('created_at');
        $this->forge->createTable('collaboration_audit_events', true);
    }

    public function down()
    {
        $this->forge->dropTable('collaboration_audit_events', true);
        $this->forge->dropTable('collaboration_notifications', true);
        $this->forge->dropTable('collaboration_directive_acknowledgements', true);
        $this->forge->dropTable('collaboration_meeting_action_items', true);
        $this->forge->dropTable('collaboration_meeting_minutes', true);
        $this->forge->dropTable('collaboration_meeting_agendas', true);
        $this->forge->dropTable('collaboration_meeting_participants', true);
        $this->forge->dropTable('collaboration_meetings', true);
        $this->forge->dropTable('collaboration_announcements', true);
        $this->forge->dropTable('collaboration_message_references', true);
        $this->forge->dropTable('collaboration_messages', true);
        $this->forge->dropTable('collaboration_channel_members', true);
        $this->forge->dropTable('collaboration_channels', true);
        $this->forge->dropTable('collaboration_conversation_participants', true);
        $this->forge->dropTable('collaboration_conversations', true);
    }
}
