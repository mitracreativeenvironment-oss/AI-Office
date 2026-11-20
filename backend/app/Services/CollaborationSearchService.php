<?php

namespace App\Services;

class CollaborationSearchService
{
    protected $db;
    protected $accessService;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->accessService = new CollaborationAccessService();
    }

    /**
     * Perform unified search across collaboration entities.
     */
    public function search(string $query, ?array $userContext = null): array
    {
        $query = trim($query);
        if ($query === '') {
            return [
                'query'         => '',
                'total_results' => 0,
                'channels'      => [],
                'messages'      => [],
                'meetings'      => [],
                'announcements' => [],
                'action_items'  => [],
            ];
        }

        $allowedClassifications = $this->accessService->getAllowedClassifications($userContext);

        // 1. Search Channels
        $channels = $this->db->table('collaboration_channels')
            ->whereIn('classification', $allowedClassifications)
            ->groupStart()
                ->like('name', $query)
                ->orLike('channel_code', $query)
                ->orLike('description', $query)
            ->groupEnd()
            ->limit(10)
            ->get()
            ->getResultArray();

        // 2. Search Messages
        $messages = $this->db->table('collaboration_messages m')
            ->select('m.*, c.name AS channel_name, c.channel_code')
            ->join('collaboration_channels c', 'c.id = m.channel_id', 'left')
            ->whereIn('m.classification', $allowedClassifications)
            ->where('m.is_deleted', 0)
            ->like('m.content', $query)
            ->limit(15)
            ->get()
            ->getResultArray();

        // 3. Search Meetings
        $meetings = $this->db->table('collaboration_meetings m')
            ->select('m.*, r.room_code, r.name AS room_name')
            ->join('hq_rooms r', 'r.id = m.room_id', 'left')
            ->whereIn('m.classification', $allowedClassifications)
            ->groupStart()
                ->like('m.title', $query)
                ->orLike('m.meeting_code', $query)
                ->orLike('m.description', $query)
            ->groupEnd()
            ->limit(10)
            ->get()
            ->getResultArray();

        // 4. Search Announcements
        $announcements = $this->db->table('collaboration_announcements')
            ->whereIn('classification', $allowedClassifications)
            ->where('is_published', 1)
            ->groupStart()
                ->like('title', $query)
                ->orLike('announcement_code', $query)
                ->orLike('content', $query)
            ->groupEnd()
            ->limit(10)
            ->get()
            ->getResultArray();

        // 5. Search Action Items
        $actionItems = $this->db->table('collaboration_meeting_action_items ai')
            ->select('ai.*, m.meeting_code, m.title AS meeting_title')
            ->join('collaboration_meetings m', 'm.id = ai.meeting_id', 'left')
            ->like('ai.description', $query)
            ->limit(10)
            ->get()
            ->getResultArray();

        $totalResults = count($channels) + count($messages) + count($meetings) + count($announcements) + count($actionItems);

        return [
            'query'         => $query,
            'total_results' => $totalResults,
            'channels'      => $channels,
            'messages'      => $messages,
            'meetings'      => $meetings,
            'announcements' => $announcements,
            'action_items'  => $actionItems,
        ];
    }
}
