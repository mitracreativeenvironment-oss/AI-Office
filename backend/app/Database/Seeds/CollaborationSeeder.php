<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CollaborationSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $db = $this->db;

        // Fetch User and Agent references safely
        $userRow = $db->table('users')->orderBy('id', 'ASC')->get(1)->getRowArray();
        $userId = $userRow ? (int)$userRow['id'] : 1;
        $userName = $userRow ? ($userRow['full_name'] ?? $userRow['username']) : 'Human Owner';

        $agents = [];
        $agentRows = $db->table('ai_agent_registry')->orderBy('id', 'ASC')->get()->getResultArray();
        foreach ($agentRows as $a) {
            $agents[$a['agent_code']] = (int)$a['id'];
        }
        $agentDirId = $agents['AGT-DIR-001'] ?? ($agents['AGENT-MCE-DIR-001'] ?? (isset($agentRows[0]) ? (int)$agentRows[0]['id'] : null));
        $agentTechId = $agents['AGT-TECH-001'] ?? ($agents['AGENT-MCE-TECH-001'] ?? (isset($agentRows[1]) ? (int)$agentRows[1]['id'] : $agentDirId));
        $agentOpsId = $agents['AGT-OPS-001'] ?? ($agents['AGENT-MCE-OPS-001'] ?? (isset($agentRows[2]) ? (int)$agentRows[2]['id'] : $agentDirId));

        // Fetch Room references
        $boardRoom = $db->table('hq_rooms')->where('room_code', 'ROOM-MAIN-BOARD')->get()->getRowArray();
        $boardRoomId = $boardRoom ? (int)$boardRoom['id'] : null;

        $techRoom = $db->table('hq_rooms')->where('room_code', 'ROOM-TECH-DEV')->get()->getRowArray();
        $techRoomId = $techRoom ? (int)$techRoom['id'] : null;

        // Fetch Division / Unit references
        $rootUnit = $db->table('organization_units')->where('code', 'ORG-MCE')->get()->getRowArray();
        $rootUnitId = $rootUnit ? (int)$rootUnit['id'] : null;

        $techUnit = $db->table('organization_units')->where('code', 'DIV-TECH')->get()->getRowArray();
        $techUnitId = $techUnit ? (int)$techUnit['id'] : null;

        // 1. Seed Organizational Channels
        $channelsData = [
            [
                'channel_code'       => 'CHN-EXEC-BOARD',
                'name'               => 'Continuous Executive Board',
                'description'        => 'Saluran koordinasi eksekutif tertinggi antara Human Owner dan AI Executive Board untuk kebijakan strategis dan DoA.',
                'domain'             => 'executive_coordination',
                'scope_unit_id'      => $rootUnitId,
                'classification'     => 'restricted',
                'is_private'         => 1,
                'is_archived'        => 0,
                'created_by_user_id' => $userId,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'channel_code'       => 'CHN-CORP-OPS',
                'name'               => 'Corporate Operations',
                'description'        => 'Saluran koordinasi lintas divisi untuk pemantauan operasi harian, eskalasi hambatan, dan sinkronisasi shift.',
                'domain'             => 'corporate_ops',
                'scope_unit_id'      => $rootUnitId,
                'classification'     => 'internal',
                'is_private'         => 0,
                'is_archived'        => 0,
                'created_by_user_id' => $userId,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'channel_code'       => 'CHN-DIV-TECH',
                'name'               => 'Technology & Engineering Pod',
                'description'        => 'Saluran diskusi teknis arsitektur sistem, runtime Hermes, gateway 9Router, dan pengembangan software.',
                'domain'             => 'technical',
                'scope_unit_id'      => $techUnitId,
                'classification'     => 'internal',
                'is_private'         => 0,
                'is_archived'        => 0,
                'created_by_user_id' => $userId,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'channel_code'       => 'CHN-ANNOUNCEMENTS',
                'name'               => 'Company Announcements',
                'description'        => 'Saluran pengumuman resmi perusahaan untuk seluruh personel manusia dan AI workforce.',
                'domain'             => 'announcements',
                'scope_unit_id'      => $rootUnitId,
                'classification'     => 'public',
                'is_private'         => 0,
                'is_archived'        => 0,
                'created_by_user_id' => $userId,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'channel_code'       => 'CHN-ALERTS',
                'name'               => 'Operational Alerts',
                'description'        => 'Notifikasi dan telemetri otomatis sistem mengenai status runtime, kuota model, dan insiden operasional.',
                'domain'             => 'operational_alerts',
                'scope_unit_id'      => $rootUnitId,
                'classification'     => 'internal',
                'is_private'         => 0,
                'is_archived'        => 0,
                'created_by_user_id' => $userId,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
        ];

        $channelMap = [];
        foreach ($channelsData as $c) {
            $existing = $db->table('collaboration_channels')->where('channel_code', $c['channel_code'])->get()->getRowArray();
            if (!$existing) {
                $db->table('collaboration_channels')->insert($c);
                $channelMap[$c['channel_code']] = (int)$db->insertID();
            } else {
                $channelMap[$c['channel_code']] = (int)$existing['id'];
            }
        }

        // 2. Seed Channel Members
        $memberships = [
            // CHN-EXEC-BOARD
            ['channel_id' => $channelMap['CHN-EXEC-BOARD'], 'member_type' => 'human', 'user_id' => $userId, 'agent_id' => null, 'role' => 'admin', 'notifications_enabled' => 1, 'joined_at' => $now],
            ['channel_id' => $channelMap['CHN-EXEC-BOARD'], 'member_type' => 'agent', 'user_id' => null, 'agent_id' => $agentDirId, 'role' => 'moderator', 'notifications_enabled' => 1, 'joined_at' => $now],
            // CHN-CORP-OPS
            ['channel_id' => $channelMap['CHN-CORP-OPS'], 'member_type' => 'human', 'user_id' => $userId, 'agent_id' => null, 'role' => 'admin', 'notifications_enabled' => 1, 'joined_at' => $now],
            ['channel_id' => $channelMap['CHN-CORP-OPS'], 'member_type' => 'agent', 'user_id' => null, 'agent_id' => $agentDirId, 'role' => 'member', 'notifications_enabled' => 1, 'joined_at' => $now],
            ['channel_id' => $channelMap['CHN-CORP-OPS'], 'member_type' => 'agent', 'user_id' => null, 'agent_id' => $agentTechId, 'role' => 'member', 'notifications_enabled' => 1, 'joined_at' => $now],
            ['channel_id' => $channelMap['CHN-CORP-OPS'], 'member_type' => 'agent', 'user_id' => null, 'agent_id' => $agentOpsId, 'role' => 'member', 'notifications_enabled' => 1, 'joined_at' => $now],
            // CHN-DIV-TECH
            ['channel_id' => $channelMap['CHN-DIV-TECH'], 'member_type' => 'human', 'user_id' => $userId, 'agent_id' => null, 'role' => 'admin', 'notifications_enabled' => 1, 'joined_at' => $now],
            ['channel_id' => $channelMap['CHN-DIV-TECH'], 'member_type' => 'agent', 'user_id' => null, 'agent_id' => $agentTechId, 'role' => 'moderator', 'notifications_enabled' => 1, 'joined_at' => $now],
            // CHN-ANNOUNCEMENTS
            ['channel_id' => $channelMap['CHN-ANNOUNCEMENTS'], 'member_type' => 'human', 'user_id' => $userId, 'agent_id' => null, 'role' => 'admin', 'notifications_enabled' => 1, 'joined_at' => $now],
            ['channel_id' => $channelMap['CHN-ANNOUNCEMENTS'], 'member_type' => 'agent', 'user_id' => null, 'agent_id' => $agentDirId, 'role' => 'member', 'notifications_enabled' => 1, 'joined_at' => $now],
        ];

        foreach ($memberships as $m) {
            $builder = $db->table('collaboration_channel_members')->where('channel_id', $m['channel_id']);
            if ($m['member_type'] === 'human') {
                $builder->where('user_id', $m['user_id']);
            } else {
                $builder->where('agent_id', $m['agent_id']);
            }
            if ($builder->countAllResults() === 0) {
                $db->table('collaboration_channel_members')->insert($m);
            }
        }

        // 3. Seed Conversations (Direct & Executive Session)
        $conversationsData = [
            [
                'conversation_code'   => 'CONV-DIR-OWNER-001',
                'type'                => 'direct',
                'title'               => 'Human Owner & Director AI Direct Line',
                'created_by_user_id'  => $userId,
                'created_by_agent_id' => null,
                'classification'      => 'confidential',
                'is_active'           => 1,
                'last_message_at'     => $now,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'conversation_code'   => 'CONV-EXEC-SESSION-001',
                'type'                => 'executive_session',
                'title'               => 'Executive Board Ad-hoc Deliberation Session',
                'created_by_user_id'  => $userId,
                'created_by_agent_id' => null,
                'classification'      => 'restricted',
                'is_active'           => 1,
                'last_message_at'     => $now,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
        ];

        $conversationMap = [];
        foreach ($conversationsData as $conv) {
            $existing = $db->table('collaboration_conversations')->where('conversation_code', $conv['conversation_code'])->get()->getRowArray();
            if (!$existing) {
                $db->table('collaboration_conversations')->insert($conv);
                $conversationMap[$conv['conversation_code']] = (int)$db->insertID();
            } else {
                $conversationMap[$conv['conversation_code']] = (int)$existing['id'];
            }
        }

        // 4. Seed Conversation Participants
        $convParticipants = [
            ['conversation_id' => $conversationMap['CONV-DIR-OWNER-001'], 'participant_type' => 'human', 'user_id' => $userId, 'agent_id' => null, 'role' => 'owner', 'is_muted' => 0, 'joined_at' => $now],
            ['conversation_id' => $conversationMap['CONV-DIR-OWNER-001'], 'participant_type' => 'agent', 'user_id' => null, 'agent_id' => $agentDirId, 'role' => 'member', 'is_muted' => 0, 'joined_at' => $now],
            ['conversation_id' => $conversationMap['CONV-EXEC-SESSION-001'], 'participant_type' => 'human', 'user_id' => $userId, 'agent_id' => null, 'role' => 'owner', 'is_muted' => 0, 'joined_at' => $now],
            ['conversation_id' => $conversationMap['CONV-EXEC-SESSION-001'], 'participant_type' => 'agent', 'user_id' => null, 'agent_id' => $agentDirId, 'role' => 'admin', 'is_muted' => 0, 'joined_at' => $now],
            ['conversation_id' => $conversationMap['CONV-EXEC-SESSION-001'], 'participant_type' => 'agent', 'user_id' => null, 'agent_id' => $agentTechId, 'role' => 'member', 'is_muted' => 0, 'joined_at' => $now],
        ];

        foreach ($convParticipants as $cp) {
            $builder = $db->table('collaboration_conversation_participants')->where('conversation_id', $cp['conversation_id']);
            if ($cp['participant_type'] === 'human') {
                $builder->where('user_id', $cp['user_id']);
            } else {
                $builder->where('agent_id', $cp['agent_id']);
            }
            if ($builder->countAllResults() === 0) {
                $db->table('collaboration_conversation_participants')->insert($cp);
            }
        }

        // 5. Seed Messages
        $messagesData = [
            [
                'message_code'        => 'MSG-EXEC-001',
                'channel_id'          => $channelMap['CHN-EXEC-BOARD'],
                'conversation_id'     => null,
                'parent_message_id'   => null,
                'sender_type'         => 'human',
                'sender_user_id'      => $userId,
                'sender_agent_id'     => null,
                'sender_display_name' => $userName . ' (Human Owner)',
                'content'             => 'Dewan Direksi AI, pastikan seluruh tata kelola operasional dan koordinasi lintas divisi tetap berjalan dalam koridor Delegated Authority (DoA Level B/C). Evaluasi integrasi Knowledge Management dan Internal Collaboration hari ini.',
                'content_format'      => 'markdown',
                'classification'      => 'restricted',
                'is_pinned'           => 1,
                'created_at'          => date('Y-m-d H:i:s', strtotime('-2 hours')),
            ],
            [
                'message_code'        => 'MSG-EXEC-002',
                'channel_id'          => $channelMap['CHN-EXEC-BOARD'],
                'conversation_id'     => null,
                'parent_message_id'   => null, // Will update to parent MSG-EXEC-001 ID
                'sender_type'         => 'agent',
                'sender_user_id'      => null,
                'sender_agent_id'     => $agentDirId,
                'sender_display_name' => 'Director AI (AGT-DIR-001)',
                'content'             => 'Siap Human Owner. Arahan strategis telah diterima dan diagendakan dalam Continuous Executive Board Strategy Sync di Boardroom. Seluruh batasan DoA dijaga ketat tanpa bypass otorisasi.',
                'content_format'      => 'markdown',
                'classification'      => 'restricted',
                'is_pinned'           => 0,
                'created_at'          => date('Y-m-d H:i:s', strtotime('-90 minutes')),
            ],
            [
                'message_code'        => 'MSG-DIRECT-001',
                'channel_id'          => null,
                'conversation_id'     => $conversationMap['CONV-DIR-OWNER-001'],
                'parent_message_id'   => null,
                'sender_type'         => 'human',
                'sender_user_id'      => $userId,
                'sender_agent_id'     => null,
                'sender_display_name' => $userName,
                'content'             => 'Director, siapkan notulen rapat (MoM) untuk sinkronisasi strategi eksekutif dan teruskan action items ke tim teknis.',
                'content_format'      => 'markdown',
                'classification'      => 'confidential',
                'is_pinned'           => 0,
                'created_at'          => date('Y-m-d H:i:s', strtotime('-1 hour')),
            ],
            [
                'message_code'        => 'MSG-DIRECT-002',
                'channel_id'          => null,
                'conversation_id'     => $conversationMap['CONV-DIR-OWNER-001'],
                'parent_message_id'   => null,
                'sender_type'         => 'agent',
                'sender_user_id'      => null,
                'sender_agent_id'     => $agentDirId,
                'sender_display_name' => 'Director AI',
                'content'             => 'Baik Human Owner, MoM MTG-2026-EXEC-01 telah selesai disusun dan diserahkan untuk persetujuan final Anda.',
                'content_format'      => 'markdown',
                'classification'      => 'confidential',
                'is_pinned'           => 0,
                'created_at'          => date('Y-m-d H:i:s', strtotime('-45 minutes')),
            ],
        ];

        $messageMap = [];
        foreach ($messagesData as $msg) {
            $existing = $db->table('collaboration_messages')->where('message_code', $msg['message_code'])->get()->getRowArray();
            if (!$existing) {
                if ($msg['message_code'] === 'MSG-EXEC-002' && isset($messageMap['MSG-EXEC-001'])) {
                    $msg['parent_message_id'] = $messageMap['MSG-EXEC-001'];
                }
                $db->table('collaboration_messages')->insert($msg);
                $messageMap[$msg['message_code']] = (int)$db->insertID();
            } else {
                $messageMap[$msg['message_code']] = (int)$existing['id'];
            }
        }

        // 6. Seed Message References
        if (isset($messageMap['MSG-EXEC-002'])) {
            $ownerDir = $db->table('owner_directions')->orderBy('id', 'ASC')->get(1)->getRowArray();
            $dirId = $ownerDir ? (int)$ownerDir['id'] : 1;
            $dirCode = $ownerDir['direction_code'] ?? 'DIR-OWNER-2026-001';

            $execDec = $db->table('executive_decisions')->orderBy('id', 'ASC')->get(1)->getRowArray();
            $decId = $execDec ? (int)$execDec['id'] : 1;
            $decCode = $execDec['decision_code'] ?? 'DEC-BOARD-2026-001';

            $refExists = $db->table('collaboration_message_references')->where('message_id', $messageMap['MSG-EXEC-002'])->countAllResults();
            if ($refExists === 0) {
                $db->table('collaboration_message_references')->insertBatch([
                    [
                        'message_id'      => $messageMap['MSG-EXEC-002'],
                        'reference_type'  => 'owner_direction',
                        'reference_id'    => $dirId,
                        'reference_code'  => $dirCode,
                        'reference_title' => 'Akselerasi Operasional MCE AI Office & Transformasi Digital',
                        'created_at'      => $now,
                    ],
                    [
                        'message_id'      => $messageMap['MSG-EXEC-002'],
                        'reference_type'  => 'executive_decision',
                        'reference_id'    => $decId,
                        'reference_code'  => $decCode,
                        'reference_title' => 'Ratifikasi Rencana Kerja Sprint dan Tata Kelola AI Workforce MCE 2026',
                        'created_at'      => $now,
                    ],
                ]);
            }
        }

        // 7. Seed Announcements
        $announcementsData = [
            [
                'announcement_code'    => 'ANN-2026-001',
                'title'                => 'Peluncuran Sistem Komunikasi Internal & Rapat Kolaboratif MCE AI Office',
                'content'              => "Seluruh jajaran organisasi MCE AI Office kini terhubung melalui sistem komunikasi dan koordinasi terpadu. Sistem ini mencakup:\n\n1. Komunikasi Langsung Human-Human, Human-Agent, dan Agent-Agent.\n2. Saluran Koordinasi Berjenjang (Executive Board, Corporate Ops, Div-Tech).\n3. Tata Kelola Rapat Terstruktur dengan Notulen Resmi (MoM) dan Action Items otomatis.\n4. Integrasi penuh dengan Virtual Headquarters 3D dan Delegated Authority.",
                'target_scope'         => 'all_organization',
                'target_unit_id'       => null,
                'priority'             => 'important',
                'classification'       => 'public',
                'published_by_user_id' => $userId,
                'published_by_name'    => $userName,
                'is_published'         => 1,
                'published_at'         => $now,
                'expires_at'           => date('Y-m-d H:i:s', strtotime('+30 days')),
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            [
                'announcement_code'    => 'ANN-2026-002',
                'title'                => 'Pemberlakuan Standar Koordinasi Eksekutif & Siklus Keputusan Terstruktur',
                'content'              => "Diumumkan kepada seluruh pimpinan unit bahwa setiap keputusan eksekutif wajib melalui siklus: Diskusi -> Proposal -> Rekomendasi -> Keputusan -> Ratifikasi/Approval -> Eksekusi -> Review. Tidak diperkenankan mengambil tindakan finansial atau kebijakan di luar batas DoA yang telah ditentukan.",
                'target_scope'         => 'all_organization',
                'target_unit_id'       => null,
                'priority'             => 'routine',
                'classification'       => 'internal',
                'published_by_user_id' => $userId,
                'published_by_name'    => $userName,
                'is_published'         => 1,
                'published_at'         => $now,
                'expires_at'           => date('Y-m-d H:i:s', strtotime('+60 days')),
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
        ];

        foreach ($announcementsData as $ann) {
            $existing = $db->table('collaboration_announcements')->where('announcement_code', $ann['announcement_code'])->get()->getRowArray();
            if (!$existing) {
                $db->table('collaboration_announcements')->insert($ann);
            }
        }

        // 8. Seed Meetings
        $meetingsData = [
            [
                'meeting_code'         => 'MTG-2026-EXEC-01',
                'title'                => 'Continuous Executive Board Strategy Sync Q4',
                'category'             => 'executive_coordination',
                'status'               => 'minutes_finalized',
                'classification'       => 'restricted',
                'room_id'              => $boardRoomId,
                'organizer_user_id'    => $userId,
                'organizer_agent_id'   => null,
                'scheduled_start_time' => date('Y-m-d 09:00:00'),
                'scheduled_end_time'   => date('Y-m-d 10:30:00'),
                'actual_start_time'    => date('Y-m-d 09:02:00'),
                'actual_end_time'      => date('Y-m-d 10:28:00'),
                'description'          => 'Sinkronisasi strategi kuartal 4 antara Human Owner dan Continuous Executive Board. Membahas delegasi kewenangan, prioritas sistem kolaborasi, dan target kinerja AI Workforce.',
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            [
                'meeting_code'         => 'MTG-2026-TECH-01',
                'title'                => 'Weekly Technical Review & Hermes Agent Architecture',
                'category'             => 'technical_review',
                'status'               => 'scheduled',
                'classification'       => 'internal',
                'room_id'              => $techRoomId,
                'organizer_user_id'    => null,
                'organizer_agent_id'   => $agentTechId,
                'scheduled_start_time' => date('Y-m-d 14:00:00', strtotime('+1 day')),
                'scheduled_end_time'   => date('Y-m-d 15:30:00', strtotime('+1 day')),
                'actual_start_time'    => null,
                'actual_end_time'      => null,
                'description'          => 'Review arsitektur orkestrasi runtime Hermes, utilisasi gateway 9Router, dan stabilitas endpoint kolaborasi backend CodeIgniter 4.',
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
        ];

        $meetingMap = [];
        foreach ($meetingsData as $mtg) {
            $existing = $db->table('collaboration_meetings')->where('meeting_code', $mtg['meeting_code'])->get()->getRowArray();
            if (!$existing) {
                $db->table('collaboration_meetings')->insert($mtg);
                $meetingMap[$mtg['meeting_code']] = (int)$db->insertID();
            } else {
                $meetingMap[$mtg['meeting_code']] = (int)$existing['id'];
            }
        }

        // 9. Seed Meeting Participants
        $execMeetingId = $meetingMap['MTG-2026-EXEC-01'];
        $techMeetingId = $meetingMap['MTG-2026-TECH-01'];

        $meetingParticipants = [
            // MTG-2026-EXEC-01
            ['meeting_id' => $execMeetingId, 'participant_type' => 'human', 'user_id' => $userId, 'agent_id' => null, 'role' => 'chair', 'invitation_status' => 'confirmed', 'attendance_status' => 'attended', 'notes' => 'Memimpin jalannya rapat dewan.'],
            ['meeting_id' => $execMeetingId, 'participant_type' => 'agent', 'user_id' => null, 'agent_id' => $agentDirId, 'role' => 'secretary', 'invitation_status' => 'confirmed', 'attendance_status' => 'attended', 'notes' => 'Mencatat notulen dan mengelola agenda.'],
            ['meeting_id' => $execMeetingId, 'participant_type' => 'agent', 'user_id' => null, 'agent_id' => $agentTechId, 'role' => 'participant', 'invitation_status' => 'confirmed', 'attendance_status' => 'attended', 'notes' => 'Pemaparan kesiapan arsitektur teknis.'],
            // MTG-2026-TECH-01
            ['meeting_id' => $techMeetingId, 'participant_type' => 'agent', 'user_id' => null, 'agent_id' => $agentTechId, 'role' => 'chair', 'invitation_status' => 'confirmed', 'attendance_status' => 'unrecorded', 'notes' => 'Ketua rapat review arsitektur.'],
            ['meeting_id' => $techMeetingId, 'participant_type' => 'human', 'user_id' => $userId, 'agent_id' => null, 'role' => 'participant', 'invitation_status' => 'confirmed', 'attendance_status' => 'unrecorded', 'notes' => 'Pengarah kebutuhan fungsional.'],
            ['meeting_id' => $techMeetingId, 'participant_type' => 'agent', 'user_id' => null, 'agent_id' => $agentDirId, 'role' => 'observer', 'invitation_status' => 'confirmed', 'attendance_status' => 'unrecorded', 'notes' => 'Pemantau keselarasan tata kelola.'],
        ];

        foreach ($meetingParticipants as $mp) {
            $builder = $db->table('collaboration_meeting_participants')->where('meeting_id', $mp['meeting_id']);
            if ($mp['participant_type'] === 'human') {
                $builder->where('user_id', $mp['user_id']);
            } else {
                $builder->where('agent_id', $mp['agent_id']);
            }
            if ($builder->countAllResults() === 0) {
                $db->table('collaboration_meeting_participants')->insert($mp);
            }
        }

        // 10. Seed Meeting Agendas
        $agendasData = [
            ['meeting_id' => $execMeetingId, 'item_order' => 1, 'title' => 'Evaluasi Kinerja & Delegated Authority DoA Level B/C', 'description' => 'Tinjauan operasional keputusan mandiri dewan direksi AI dalam koridor anggaran dan kebijakan yang telah disetujui.', 'allocated_minutes' => 30, 'presenter_name' => 'Director AI', 'status' => 'concluded'],
            ['meeting_id' => $execMeetingId, 'item_order' => 2, 'title' => 'Pemberlakuan Sistem Kolaborasi Internal & Notulen Formal', 'description' => 'Pembahasan tata cara komunikasi, rapat, MoM, dan penerbitan action items terikat tugas.', 'allocated_minutes' => 45, 'presenter_name' => 'Human Owner', 'status' => 'concluded'],
            ['meeting_id' => $techMeetingId, 'item_order' => 1, 'title' => 'Arsitektur Router Gateway 9Router & Hermes Integration', 'description' => 'Evaluasi routing model LLM, latency, dan fallback mekanisme.', 'allocated_minutes' => 45, 'presenter_name' => 'Tech Lead AI', 'status' => 'pending'],
            ['meeting_id' => $techMeetingId, 'item_order' => 2, 'title' => 'Audit Keamanan & Pencegahan Impersonasi Agen AI', 'description' => 'Verifikasi bahwa AI Agent tidak dapat meniru identitas manusia atau melangkahi izin RBAC.', 'allocated_minutes' => 30, 'presenter_name' => 'Tech Lead AI', 'status' => 'pending'],
        ];

        foreach ($agendasData as $ag) {
            $exists = $db->table('collaboration_meeting_agendas')->where('meeting_id', $ag['meeting_id'])->where('item_order', $ag['item_order'])->countAllResults();
            if ($exists === 0) {
                $db->table('collaboration_meeting_agendas')->insert($ag);
            }
        }

        // 11. Seed Minutes of Meeting (MoM)
        $existingMom = $db->table('collaboration_meeting_minutes')->where('meeting_id', $execMeetingId)->get()->getRowArray();
        if (!$existingMom) {
            $db->table('collaboration_meeting_minutes')->insert([
                'meeting_id'           => $execMeetingId,
                'prepared_by_user_id'  => null,
                'prepared_by_agent_id' => $agentDirId,
                'approved_by_user_id'  => $userId,
                'attendance_summary'   => 'Dihadiri lengkap: Human Owner (Chair), Director AI (Secretary), Tech Lead AI (Member). Kuorum 100% terpenuhi.',
                'discussion_notes'     => "1. Human Owner menegaskan bahwa seluruh arahan strategis pemilik bersifat panduan advisory kecuali jika secara eksplisit diterbitkan sebagai binding directive dengan referensi otoritas.\n2. Continuous Executive Board beroperasi mandiri dalam batas DoA Level B/C.\n3. Rapat menyepakati bahwa setiap action item rapat harus terhubung langsung ke Task Management untuk memastikan akuntabilitas operasional.",
                'formal_decisions'     => "1. Menetapkan implementasi modul Internal Collaboration, Executive Coordination, dan Meeting Management.\n2. Mengesahkan saluran CHN-EXEC-BOARD sebagai forum resmi konsultasi dewan.\n3. Memberikan mandat kepada Tech Lead AI untuk memverifikasi keamanan identitas agen.",
                'is_approved'          => 1,
                'approved_at'          => $now,
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);
        }

        // 12. Seed Meeting Action Items
        // Check if there is an existing workforce_tasks record to link to
        $taskRow = $db->table('workforce_tasks')->orderBy('id', 'ASC')->get(1)->getRowArray();
        $sampleTaskId = $taskRow ? (int)$taskRow['id'] : null;

        $actionItemsData = [
            [
                'meeting_id'        => $execMeetingId,
                'agenda_item_id'    => null,
                'task_id'           => $sampleTaskId,
                'description'       => 'Mengembangkan endpoint REST API Internal Collaboration dan mengintegrasikan dengan frontend React.',
                'assignee_type'     => 'agent',
                'assignee_user_id'  => null,
                'assignee_agent_id' => $agentTechId,
                'due_date'          => date('Y-m-d', strtotime('+3 days')),
                'status'            => 'assigned_to_task',
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
            [
                'meeting_id'        => $execMeetingId,
                'agenda_item_id'    => null,
                'task_id'           => null,
                'description'       => 'Menyusun dokumentasi arsitektur komunikasi internal dan panduan operasional kolaborasi.',
                'assignee_type'     => 'agent',
                'assignee_user_id'  => null,
                'assignee_agent_id' => $agentDirId,
                'due_date'          => date('Y-m-d', strtotime('+5 days')),
                'status'            => 'in_progress',
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
        ];

        foreach ($actionItemsData as $ai) {
            $exists = $db->table('collaboration_meeting_action_items')->where('meeting_id', $ai['meeting_id'])->where('description', $ai['description'])->countAllResults();
            if ($exists === 0) {
                $db->table('collaboration_meeting_action_items')->insert($ai);
            }
        }

        // 13. Seed Directive Acknowledgements
        $ownerDir = $db->table('owner_directions')->orderBy('id', 'ASC')->get(1)->getRowArray();
        if ($ownerDir) {
            $dirId = (int)$ownerDir['id'];
            $ackExists = $db->table('collaboration_directive_acknowledgements')->where('direction_id', $dirId)->countAllResults();
            if ($ackExists === 0) {
                $db->table('collaboration_directive_acknowledgements')->insert([
                    'direction_id'           => $dirId,
                    'acknowledging_agent_id' => $agentDirId,
                    'acknowledging_user_id'  => null,
                    'status'                 => 'executed',
                    'notes'                  => 'Arahan pemilik telah diratifikasi melalui Rapat Direksi DEC-BOARD-2026-001 dan dieksekusi secara terkoordinasi lintas divisi.',
                    'acknowledged_at'        => $now,
                ]);
            }
        }

        // 14. Seed Notifications
        $notificationsData = [
            [
                'recipient_type' => 'human',
                'user_id'        => $userId,
                'agent_id'       => null,
                'title'          => 'Undangan Rapat Direksi Strategis',
                'message'        => 'Anda dijadwalkan sebagai Chair pada Continuous Executive Board Strategy Sync Q4 di Boardroom (ROOM-MAIN-BOARD).',
                'type'           => 'meeting_invite',
                'priority'       => 'high',
                'resource_uri'   => '/collaboration/meetings/' . $execMeetingId,
                'is_read'        => 0,
                'read_at'        => null,
                'created_at'     => date('Y-m-d H:i:s', strtotime('-3 hours')),
            ],
            [
                'recipient_type' => 'human',
                'user_id'        => $userId,
                'agent_id'       => null,
                'title'          => 'Penyelesaian Notulen Rapat (MoM)',
                'message'        => 'Director AI telah menyelesaikan penyusunan MoM MTG-2026-EXEC-01 dan menunggu pengesahan Anda.',
                'type'           => 'decision_published',
                'priority'       => 'medium',
                'resource_uri'   => '/collaboration/meetings/' . $execMeetingId . '/minutes',
                'is_read'        => 1,
                'read_at'        => date('Y-m-d H:i:s', strtotime('-30 minutes')),
                'created_at'     => date('Y-m-d H:i:s', strtotime('-1 hour')),
            ],
            [
                'recipient_type' => 'agent',
                'user_id'        => null,
                'agent_id'       => $agentTechId,
                'title'          => 'Penugasan Action Item Baru',
                'message'        => 'Anda ditugaskan pada action item: Mengembangkan endpoint REST API Internal Collaboration.',
                'type'           => 'action_item_assigned',
                'priority'       => 'urgent',
                'resource_uri'   => '/collaboration/meetings/' . $execMeetingId . '/action-items',
                'is_read'        => 0,
                'read_at'        => null,
                'created_at'     => date('Y-m-d H:i:s', strtotime('-40 minutes')),
            ],
        ];

        foreach ($notificationsData as $notif) {
            $builder = $db->table('collaboration_notifications')->where('title', $notif['title']);
            if ($notif['recipient_type'] === 'human') {
                $builder->where('user_id', $notif['user_id']);
            } else {
                $builder->where('agent_id', $notif['agent_id']);
            }
            if ($builder->countAllResults() === 0) {
                $db->table('collaboration_notifications')->insert($notif);
            }
        }

        // 15. Seed Audit Event
        $db->table('collaboration_audit_events')->insert([
            'event_type'    => 'collaboration_system_initialized',
            'actor_type'    => 'user',
            'actor_id'      => $userId,
            'actor_name'    => $userName,
            'entity_type'   => 'system',
            'entity_id'     => 'collaboration_module',
            'metadata_json' => json_encode(['seeder' => 'CollaborationSeeder', 'channels_seeded' => count($channelsData)]),
            'ip_address'    => '127.0.0.1',
            'created_at'    => $now,
        ]);
    }
}
