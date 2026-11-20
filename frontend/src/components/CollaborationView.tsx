import React, { useState, useEffect, useCallback } from 'react';
import { collaborationService } from '../services/collaborationService';
import type {
  ExecutiveOverview,
  OwnerDirective,
  ExecutiveDecision,
  Channel,
  Conversation,
  Message,
  Announcement,
  Meeting,
  MeetingActionItem,
  MeetingMinutes,
  CollaborationNotification,
  CollaborationSearchResult,
  CollaborationClassification,
  DirectiveAckStatus,
} from '../types/collaboration';

interface CollaborationViewProps {
  isAuthenticated?: boolean;
  onOpenLogin?: () => void;
}

export const CollaborationView: React.FC<CollaborationViewProps> = ({
  isAuthenticated = false,
  onOpenLogin,
}) => {
  // Navigation Tabs
  const [activeTab, setActiveTab] = useState<
    'executive' | 'channels' | 'direct' | 'announcements' | 'meetings' | 'directives' | 'action_items' | 'notifications_search'
  >('executive');

  const [loading, setLoading] = useState<boolean>(true);
  const [actionMessage, setActionMessage] = useState<{ text: string; type: 'success' | 'error' } | null>(null);

  // 1. Executive Coordination States
  const [overview, setOverview] = useState<ExecutiveOverview | null>(null);
  const [directives, setDirectives] = useState<OwnerDirective[]>([]);
  const [decisions, setDecisions] = useState<ExecutiveDecision[]>([]);
  const [selectedDirective, setSelectedDirective] = useState<OwnerDirective | null>(null);
  const [ackModalOpen, setAckModalOpen] = useState<boolean>(false);
  const [ackStatus, setAckStatus] = useState<DirectiveAckStatus>('acknowledged');
  const [ackNotes, setAckNotes] = useState<string>('');

  // 2. Channels States
  const [channels, setChannels] = useState<Channel[]>([]);
  const [selectedChannelId, setSelectedChannelId] = useState<number | null>(null);
  const [channelMessages, setChannelMessages] = useState<Message[]>([]);
  const [selectedChannel, setSelectedChannel] = useState<Channel | null>(null);
  const [channelMessageInput, setChannelMessageInput] = useState<string>('');
  const [channelReplyToId, setChannelReplyToId] = useState<number | null>(null);

  // 3. Direct Conversations States
  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [selectedConversationId, setSelectedConversationId] = useState<number | null>(null);
  const [conversationMessages, setConversationMessages] = useState<Message[]>([]);
  const [selectedConversation, setSelectedConversation] = useState<Conversation | null>(null);
  const [directMessageInput, setDirectMessageInput] = useState<string>('');

  // 4. Announcements States
  const [announcements, setAnnouncements] = useState<Announcement[]>([]);
  const [announcementScopeFilter, setAnnouncementScopeFilter] = useState<string>('');
  const [showCreateAnnouncementModal, setShowCreateAnnouncementModal] = useState<boolean>(false);
  const [announcementForm, setAnnouncementForm] = useState({
    title: '',
    content: '',
    target_scope: 'all_organization' as any,
    priority: 'important' as any,
    classification: 'internal' as CollaborationClassification,
  });

  // 5. Meetings States
  const [meetings, setMeetings] = useState<Meeting[]>([]);
  const [meetingStatusFilter, setMeetingStatusFilter] = useState<string>('');
  const [selectedMeetingId, setSelectedMeetingId] = useState<number | null>(null);
  const [selectedMeeting, setSelectedMeeting] = useState<Meeting | null>(null);
  const [showCreateMeetingModal, setShowCreateMeetingModal] = useState<boolean>(false);
  const [meetingForm, setMeetingForm] = useState({
    title: '',
    category: 'executive_coordination' as any,
    classification: 'internal' as CollaborationClassification,
    scheduled_start_time: '',
    scheduled_end_time: '',
    description: '',
  });

  // MoM Form States
  const [showMomModal, setShowMomModal] = useState<boolean>(false);
  const [momForm, setMomForm] = useState({
    attendance_summary: '',
    discussion_notes: '',
    formal_decisions: '',
  });

  // 6. Action Items States
  const [actionItems, setActionItems] = useState<MeetingActionItem[]>([]);
  const [actionItemStatusFilter, setActionItemStatusFilter] = useState<string>('');

  // 7. Notifications & Search States
  const [notifications, setNotifications] = useState<CollaborationNotification[]>([]);
  const [unreadCount, setUnreadCount] = useState<number>(0);
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [searchResults, setSearchResults] = useState<CollaborationSearchResult | null>(null);
  const [searchLoading, setSearchLoading] = useState<boolean>(false);

  // Notice Banner Helper
  const showBanner = (text: string, type: 'success' | 'error' = 'success') => {
    setActionMessage({ text, type });
    setTimeout(() => setActionMessage(null), 5000);
  };

  // ==========================================
  // DATA LOADERS
  // ==========================================

  const loadExecutiveData = useCallback(async () => {
    try {
      const [ovRes, dirRes, decRes] = await Promise.all([
        collaborationService.getExecutiveOverview(),
        collaborationService.listDirectives(),
        collaborationService.listDecisions(),
      ]);
      setOverview(ovRes);
      setDirectives(dirRes);
      setDecisions(decRes);
    } catch (e: any) {
      console.error('Failed to load executive data', e);
    }
  }, []);

  const loadChannels = useCallback(async () => {
    try {
      const data = await collaborationService.listChannels();
      setChannels(data);
      if (data.length > 0 && !selectedChannelId) {
        setSelectedChannelId(data[0].id);
      }
    } catch (e: any) {
      console.error('Failed to load channels', e);
    }
  }, [selectedChannelId]);

  const loadChannelMessages = useCallback(async (channelId: number) => {
    try {
      const msgs = await collaborationService.getChannelMessages(channelId);
      setChannelMessages(msgs);
      const ch = await collaborationService.getChannel(channelId);
      setSelectedChannel(ch);
    } catch (e: any) {
      console.error('Failed to load channel messages', e);
    }
  }, []);

  const loadConversations = useCallback(async () => {
    try {
      const data = await collaborationService.listConversations();
      setConversations(data);
      if (data.length > 0 && !selectedConversationId) {
        setSelectedConversationId(data[0].id);
      }
    } catch (e: any) {
      console.error('Failed to load conversations', e);
    }
  }, [selectedConversationId]);

  const loadConversationMessages = useCallback(async (convId: number) => {
    try {
      const msgs = await collaborationService.getConversationMessages(convId);
      setConversationMessages(msgs);
      const conv = await collaborationService.getConversation(convId);
      setSelectedConversation(conv);
    } catch (e: any) {
      console.error('Failed to load conversation messages', e);
    }
  }, []);

  const loadAnnouncements = useCallback(async () => {
    try {
      const data = await collaborationService.listAnnouncements({
        target_scope: announcementScopeFilter || undefined,
      });
      setAnnouncements(data);
    } catch (e: any) {
      console.error('Failed to load announcements', e);
    }
  }, [announcementScopeFilter]);

  const loadMeetings = useCallback(async () => {
    try {
      const data = await collaborationService.listMeetings({
        status: meetingStatusFilter || undefined,
      });
      setMeetings(data);
      if (data.length > 0 && !selectedMeetingId) {
        setSelectedMeetingId(data[0].id);
      }
    } catch (e: any) {
      console.error('Failed to load meetings', e);
    }
  }, [meetingStatusFilter, selectedMeetingId]);

  const loadMeetingDetails = useCallback(async (id: number) => {
    try {
      const mtg = await collaborationService.getMeeting(id);
      setSelectedMeeting(mtg);
      if (mtg?.minutes) {
        setMomForm({
          attendance_summary: mtg.minutes.attendance_summary || '',
          discussion_notes: mtg.minutes.discussion_notes || '',
          formal_decisions: mtg.minutes.formal_decisions || '',
        });
      }
    } catch (e: any) {
      console.error('Failed to load meeting details', e);
    }
  }, []);

  const loadActionItems = useCallback(async () => {
    try {
      const data = await collaborationService.listActionItems({
        status: actionItemStatusFilter || undefined,
      });
      setActionItems(data);
    } catch (e: any) {
      console.error('Failed to load action items', e);
    }
  }, [actionItemStatusFilter]);

  const loadNotifications = useCallback(async () => {
    try {
      const [list, count] = await Promise.all([
        collaborationService.listNotifications(),
        collaborationService.getUnreadCount(),
      ]);
      setNotifications(list);
      setUnreadCount(count);
    } catch (e: any) {
      console.error('Failed to load notifications', e);
    }
  }, []);

  // Initial Load
  useEffect(() => {
    const init = async () => {
      setLoading(true);
      await Promise.all([
        loadExecutiveData(),
        loadChannels(),
        loadAnnouncements(),
        loadMeetings(),
        loadActionItems(),
        loadNotifications(),
      ]);
      setLoading(false);
    };
    init();
  }, [loadExecutiveData, loadChannels, loadAnnouncements, loadMeetings, loadActionItems, loadNotifications]);

  // Handle Channel Selection
  useEffect(() => {
    if (selectedChannelId) {
      loadChannelMessages(selectedChannelId);
    }
  }, [selectedChannelId, loadChannelMessages]);

  // Handle Conversation Selection
  useEffect(() => {
    if (selectedConversationId) {
      loadConversationMessages(selectedConversationId);
    }
  }, [selectedConversationId, loadConversationMessages]);

  // Handle Meeting Selection
  useEffect(() => {
    if (selectedMeetingId) {
      loadMeetingDetails(selectedMeetingId);
    }
  }, [selectedMeetingId, loadMeetingDetails]);

  // ==========================================
  // HANDLERS
  // ==========================================

  const handleSendChannelMessage = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!channelMessageInput.trim() || !selectedChannelId) return;

    try {
      await collaborationService.sendChannelMessage(selectedChannelId, {
        content: channelMessageInput,
        parent_message_id: channelReplyToId || undefined,
      });
      setChannelMessageInput('');
      setChannelReplyToId(null);
      await loadChannelMessages(selectedChannelId);
      showBanner('Message transmitted to channel.', 'success');
    } catch (e: any) {
      showBanner(e?.message || 'Failed to send message', 'error');
    }
  };

  const handleSendDirectMessage = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!directMessageInput.trim() || !selectedConversationId) return;

    try {
      await collaborationService.sendConversationMessage(selectedConversationId, {
        content: directMessageInput,
      });
      setDirectMessageInput('');
      await loadConversationMessages(selectedConversationId);
      showBanner('Direct message sent.', 'success');
    } catch (e: any) {
      showBanner(e?.message || 'Failed to send direct message', 'error');
    }
  };

  const handleDirectiveAck = async () => {
    if (!selectedDirective) return;
    try {
      await collaborationService.acknowledgeDirective(selectedDirective.id, {
        status: ackStatus,
        notes: ackNotes,
        actor_type: 'human',
      });
      setAckModalOpen(false);
      await loadExecutiveData();
      showBanner(`Directive ${selectedDirective.direction_code} updated to ${ackStatus}.`, 'success');
    } catch (e: any) {
      showBanner(e?.message || 'Failed to update directive status', 'error');
    }
  };

  const handleCreateAnnouncement = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await collaborationService.createAnnouncement(announcementForm);
      setShowCreateAnnouncementModal(false);
      setAnnouncementForm({
        title: '',
        content: '',
        target_scope: 'all_organization',
        priority: 'important',
        classification: 'internal',
      });
      await loadAnnouncements();
      showBanner('Official announcement published.', 'success');
    } catch (e: any) {
      showBanner(e?.message || 'Failed to create announcement', 'error');
    }
  };

  const handleCreateMeeting = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await collaborationService.createMeeting(meetingForm);
      setShowCreateMeetingModal(false);
      setMeetingForm({
        title: '',
        category: 'executive_coordination',
        classification: 'internal',
        scheduled_start_time: '',
        scheduled_end_time: '',
        description: '',
      });
      await loadMeetings();
      showBanner('Meeting scheduled successfully.', 'success');
    } catch (e: any) {
      showBanner(e?.message || 'Failed to schedule meeting', 'error');
    }
  };

  const handleSaveMom = async () => {
    if (!selectedMeeting) return;
    try {
      await collaborationService.saveMeetingMinutes(selectedMeeting.id, momForm);
      setShowMomModal(false);
      await loadMeetingDetails(selectedMeeting.id);
      showBanner('Minutes of Meeting (MoM) saved.', 'success');
    } catch (e: any) {
      showBanner(e?.message || 'Failed to save MoM', 'error');
    }
  };

  const handleApproveMom = async () => {
    if (!selectedMeeting) return;
    try {
      await collaborationService.approveMeetingMinutes(selectedMeeting.id);
      await loadMeetingDetails(selectedMeeting.id);
      await loadMeetings();
      showBanner('Minutes of Meeting formally approved and finalized.', 'success');
    } catch (e: any) {
      showBanner(e?.message || 'Failed to approve MoM', 'error');
    }
  };

  const handleActionItemStatus = async (id: number, newStatus: string) => {
    try {
      await collaborationService.updateActionItemStatus(id, newStatus);
      await loadActionItems();
      if (selectedMeetingId) await loadMeetingDetails(selectedMeetingId);
      showBanner('Action item status updated.', 'success');
    } catch (e: any) {
      showBanner(e?.message || 'Failed to update action item', 'error');
    }
  };

  const handleSearch = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!searchQuery.trim()) return;
    setSearchLoading(true);
    try {
      const res = await collaborationService.searchCollaboration(searchQuery);
      setSearchResults(res);
    } catch (e: any) {
      showBanner(e?.message || 'Search failed', 'error');
    } finally {
      setSearchLoading(false);
    }
  };

  const handleMarkAllRead = async () => {
    try {
      await collaborationService.markAllNotificationsRead();
      await loadNotifications();
      showBanner('All notifications marked as read.', 'success');
    } catch (e: any) {
      showBanner(e?.message || 'Failed to mark read', 'error');
    }
  };

  const getClassificationBadge = (cls: string) => {
    const map: Record<string, string> = {
      public: 'bg-emerald-950 text-emerald-300 border-emerald-800',
      internal: 'bg-blue-950 text-blue-300 border-blue-800',
      confidential: 'bg-purple-950 text-purple-300 border-purple-800',
      restricted: 'bg-amber-950 text-amber-300 border-amber-800',
      highly_restricted: 'bg-red-950 text-red-300 border-red-800',
    };
    return (
      <span className={`px-2 py-0.5 text-xs font-mono uppercase tracking-wider rounded border ${map[cls] || 'bg-slate-800 text-slate-300'}`}>
        {cls.replace('_', ' ')}
      </span>
    );
  };

  const getPriorityBadge = (p: string) => {
    const map: Record<string, string> = {
      routine: 'bg-slate-800 text-slate-300',
      important: 'bg-blue-900 text-blue-200',
      urgent: 'bg-amber-900 text-amber-200',
      critical: 'bg-red-900 text-red-100 font-bold animate-pulse',
    };
    return (
      <span className={`px-2 py-0.5 text-xs rounded uppercase font-semibold ${map[p] || 'bg-slate-800 text-slate-300'}`}>
        {p}
      </span>
    );
  };

  return (
    <div className="flex flex-col h-full bg-slate-950 text-slate-100 font-sans">
      {/* View Header */}
      <div className="flex flex-wrap items-center justify-between px-6 py-4 border-b border-slate-800 bg-slate-900/80 backdrop-blur">
        <div>
          <div className="flex items-center gap-3">
            <span className="text-2xl">🏛️</span>
            <div>
              <h1 className="text-xl font-bold tracking-tight text-white flex items-center gap-2">
                Executive Coordination & Internal Collaboration Center
                <span className="text-xs px-2 py-0.5 rounded bg-indigo-950 border border-indigo-700 text-indigo-300 font-mono">
                  Prompt 26
                </span>
              </h1>
              <p className="text-xs text-slate-400 mt-0.5">
                Authoritative governance, continuous executive board deliberative forum, multi-agent channels, meetings, and MoM workflow.
              </p>
            </div>
          </div>
        </div>

        {/* Global Stats / Quick Badges */}
        <div className="flex items-center gap-3 mt-2 sm:mt-0">
          <div className="px-3 py-1.5 rounded bg-slate-800/80 border border-slate-700 text-xs flex items-center gap-2">
            <span className="text-slate-400">DoA Status:</span>
            <span className="text-emerald-400 font-semibold font-mono">LEVEL B/C AUTONOMOUS</span>
          </div>
          <button
            onClick={() => setActiveTab('notifications_search')}
            className="relative px-3 py-1.5 rounded bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs text-slate-200 flex items-center gap-2 transition"
          >
            <span>🔔 Notifications</span>
            {unreadCount > 0 && (
              <span className="px-1.5 py-0.2 rounded-full bg-rose-600 text-white font-bold text-[10px]">
                {unreadCount}
              </span>
            )}
          </button>
        </div>
      </div>

      {/* Action Notification Banner */}
      {actionMessage && (
        <div
          className={`px-6 py-2 text-xs font-medium flex items-center justify-between border-b ${
            actionMessage.type === 'success'
              ? 'bg-emerald-950/80 border-emerald-800 text-emerald-200'
              : 'bg-rose-950/80 border-rose-800 text-rose-200'
          }`}
        >
          <span>{actionMessage.text}</span>
          <button onClick={() => setActionMessage(null)} className="opacity-70 hover:opacity-100">✕</button>
        </div>
      )}

      {/* Main Navigation Tabs */}
      <div className="flex overflow-x-auto border-b border-slate-800 bg-slate-900/50 px-6 gap-1">
        {[
          { id: 'executive', label: '👑 Executive Board', icon: '🏛️' },
          { id: 'channels', label: '💬 Organizational Channels', icon: '📢' },
          { id: 'direct', label: '✉️ Direct Messages', icon: '👤' },
          { id: 'announcements', label: '📣 Official Announcements', icon: '📰' },
          { id: 'meetings', label: '📅 Meetings & MoM', icon: '👥' },
          { id: 'directives', label: '📜 Strategic Directives', icon: '⚡' },
          { id: 'action_items', label: '✅ Action Items', icon: '🎯' },
          { id: 'notifications_search', label: '🔍 Search & Alerts', icon: '🔔' },
        ].map((tab) => (
          <button
            key={tab.id}
            onClick={() => setActiveTab(tab.id as any)}
            className={`flex items-center gap-2 px-4 py-3 text-xs font-semibold whitespace-nowrap border-b-2 transition ${
              activeTab === tab.id
                ? 'border-indigo-500 text-indigo-400 bg-indigo-950/30'
                : 'border-transparent text-slate-400 hover:text-slate-200 hover:border-slate-700'
            }`}
          >
            <span>{tab.icon}</span>
            {tab.label}
          </button>
        ))}
      </div>

      {/* Content Area */}
      <div className="flex-1 overflow-y-auto p-6">
        {loading ? (
          <div className="flex flex-col items-center justify-center h-64 text-slate-400 gap-3">
            <div className="w-8 h-8 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin" />
            <p className="text-sm">Synchronizing collaboration streams and governance records...</p>
          </div>
        ) : (
          <>
            {/* ============================================================== */}
            {/* TAB 1: EXECUTIVE COORDINATION CENTER */}
            {/* ============================================================== */}
            {activeTab === 'executive' && (
              <div className="space-y-6">
                {/* Metrics Grid */}
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                  {[
                    { label: 'Active Directives', val: overview?.metrics.active_directives ?? 0, sub: 'of ' + (overview?.metrics.total_directives ?? 0) + ' total', color: 'text-indigo-400' },
                    { label: 'Ratified Decisions', val: overview?.metrics.ratified_decisions ?? 0, sub: 'of ' + (overview?.metrics.total_decisions ?? 0) + ' total', color: 'text-emerald-400' },
                    { label: 'Pending Acks', val: overview?.metrics.pending_acknowledgments ?? 0, sub: 'requires sign-off', color: 'text-amber-400' },
                    { label: 'Active Meetings', val: overview?.metrics.active_board_meetings ?? 0, sub: 'board sessions', color: 'text-blue-400' },
                    { label: 'DoA Profiles', val: overview?.doa_profiles.length ?? 0, sub: 'active tiers', color: 'text-purple-400' },
                    { label: 'Human Owner', val: 'Advisory/Binding', sub: 'veto reserved', color: 'text-rose-400' },
                  ].map((m, idx) => (
                    <div key={idx} className="p-4 rounded-lg bg-slate-900 border border-slate-800 flex flex-col justify-between">
                      <span className="text-xs text-slate-400 font-medium">{m.label}</span>
                      <span className={`text-2xl font-bold font-mono my-1 ${m.color}`}>{m.val}</span>
                      <span className="text-[10px] text-slate-500">{m.sub}</span>
                    </div>
                  ))}
                </div>

                {/* Two Column Layout: Directives & Decisions */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                  {/* Left: Strategic Instructions & Guidance */}
                  <div className="p-5 rounded-lg bg-slate-900 border border-slate-800 space-y-4">
                    <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                      <div>
                        <h2 className="text-sm font-bold text-white flex items-center gap-2">
                          <span>⚡</span> Human Owner Strategic Instructions & Guidance
                        </h2>
                        <p className="text-xs text-slate-400">Distinguishes advisory recommendations from binding directives.</p>
                      </div>
                      <button
                        onClick={() => setActiveTab('directives')}
                        className="text-xs text-indigo-400 hover:text-indigo-300 font-medium"
                      >
                        Manage Directives →
                      </button>
                    </div>

                    <div className="space-y-3">
                      {directives.slice(0, 4).map((d) => (
                        <div
                          key={d.id}
                          className="p-3.5 rounded bg-slate-950/70 border border-slate-800/80 hover:border-slate-700 transition space-y-2"
                        >
                          <div className="flex items-center justify-between text-xs">
                            <span className="font-mono text-indigo-400 font-semibold">{d.direction_code}</span>
                            <span className={`px-2 py-0.5 rounded text-[10px] font-mono ${
                              d.direction_type === 'strategic_instruction'
                                ? 'bg-amber-950 text-amber-300 border border-amber-800'
                                : 'bg-blue-950 text-blue-300 border border-blue-800'
                            }`}>
                              {d.direction_type.replace('_', ' ').toUpperCase()}
                            </span>
                          </div>
                          <p className="text-sm font-semibold text-slate-200">{d.subject}</p>
                          <p className="text-xs text-slate-400 line-clamp-2">{d.description}</p>
                          
                          <div className="flex items-center justify-between pt-2 border-t border-slate-800/60 text-xs">
                            <span className="text-slate-500">
                              Status: <strong className="text-slate-300">{d.status.toUpperCase()}</strong>
                            </span>
                            <button
                              onClick={() => {
                                setSelectedDirective(d);
                                setAckStatus(d.ack_status || 'acknowledged');
                                setAckNotes(d.ack_notes || '');
                                setAckModalOpen(true);
                              }}
                              className="px-2.5 py-1 text-xs rounded bg-indigo-900/60 hover:bg-indigo-800 text-indigo-200 border border-indigo-700/60 transition"
                            >
                              ✍️ Acknowledge / Report
                            </button>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>

                  {/* Right: Board Decisions & Ratifications */}
                  <div className="p-5 rounded-lg bg-slate-900 border border-slate-800 space-y-4">
                    <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                      <div>
                        <h2 className="text-sm font-bold text-white flex items-center gap-2">
                          <span>⚖️</span> Continuous Executive Board Decisions (DoA Level B/C)
                        </h2>
                        <p className="text-xs text-slate-400">Formal decisions enacted by the Board within delegated limits.</p>
                      </div>
                      <span className="text-xs font-mono px-2 py-0.5 rounded bg-emerald-950 text-emerald-300 border border-emerald-800">
                        {decisions.length} Ratified
                      </span>
                    </div>

                    <div className="space-y-3">
                      {decisions.slice(0, 4).map((dec) => (
                        <div
                          key={dec.id}
                          className="p-3.5 rounded bg-slate-950/70 border border-slate-800/80 hover:border-slate-700 transition space-y-2"
                        >
                          <div className="flex items-center justify-between text-xs">
                            <span className="font-mono text-emerald-400 font-semibold">{dec.decision_code}</span>
                            <span className="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-800 text-slate-300">
                              LEVEL {dec.decision_level} • {dec.status.toUpperCase()}
                            </span>
                          </div>
                          <p className="text-sm font-semibold text-slate-200">{dec.title}</p>
                          <p className="text-xs text-slate-400 line-clamp-2">{dec.summary}</p>
                          <div className="flex items-center justify-between text-[11px] text-slate-500 pt-1">
                            <span>Decided by: <strong className="text-slate-300">{dec.decided_by}</strong></span>
                            <span>Effective: {dec.effective_date}</span>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>

                {/* DoA Framework Summary Card */}
                <div className="p-5 rounded-lg bg-slate-900 border border-slate-800">
                  <h3 className="text-sm font-bold text-white mb-2 flex items-center gap-2">
                    <span>🛡️</span> Delegated Authority (DoA) Operational Boundary Summary
                  </h3>
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <div className="p-3 rounded bg-slate-950 border border-slate-800">
                      <span className="font-bold text-rose-400 block mb-1">LEVEL A — Human Owner Authority</span>
                      <p className="text-slate-400">
                        Sole authority for founding charter amendments, financial commitments &gt; IDR 5,000,000, new division creation, and executive veto.
                      </p>
                    </div>
                    <div className="p-3 rounded bg-slate-950 border border-slate-800">
                      <span className="font-bold text-indigo-400 block mb-1">LEVEL B — Continuous Executive Board</span>
                      <p className="text-slate-400">
                        Autonomous operational mandates, sprint milestones, workforce policy, and financial commitments up to IDR 5,000,000.
                      </p>
                    </div>
                    <div className="p-3 rounded bg-slate-950 border border-slate-800">
                      <span className="font-bold text-emerald-400 block mb-1">LEVEL C/D — Division Leads & Operational Agents</span>
                      <p className="text-slate-400">
                        Task decomposition, shift coverage management, technical architecture execution, and day-to-day zero-budget operations.
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* ============================================================== */}
            {/* TAB 2: ORGANIZATIONAL CHANNELS */}
            {/* ============================================================== */}
            {activeTab === 'channels' && (
              <div className="grid grid-cols-1 md:grid-cols-4 gap-6 h-[calc(100vh-230px)]">
                {/* Channel Directory (Left Column) */}
                <div className="md:col-span-1 p-4 rounded-lg bg-slate-900 border border-slate-800 flex flex-col h-full">
                  <div className="flex items-center justify-between mb-3">
                    <h3 className="text-xs font-bold text-slate-300 uppercase tracking-wider">Channels</h3>
                    <span className="text-[10px] text-slate-500 font-mono">{channels.length} active</span>
                  </div>

                  <div className="flex-1 overflow-y-auto space-y-1.5 pr-1">
                    {channels.map((c) => (
                      <button
                        key={c.id}
                        onClick={() => setSelectedChannelId(c.id)}
                        className={`w-full text-left p-2.5 rounded text-xs transition flex flex-col gap-1 border ${
                          selectedChannelId === c.id
                            ? 'bg-indigo-950/70 border-indigo-700/80 text-white'
                            : 'bg-slate-950/50 border-slate-800/80 text-slate-400 hover:bg-slate-800/50 hover:text-slate-200'
                        }`}
                      >
                        <div className="flex items-center justify-between">
                          <span className="font-semibold truncate">
                            {c.is_private ? '🔒 ' : '# '}{c.name}
                          </span>
                          {getClassificationBadge(c.classification)}
                        </div>
                        <span className="text-[11px] text-slate-500 line-clamp-1">{c.description}</span>
                      </button>
                    ))}
                  </div>
                </div>

                {/* Channel Active View (Right 3 Columns) */}
                <div className="md:col-span-3 p-4 rounded-lg bg-slate-900 border border-slate-800 flex flex-col h-full">
                  {selectedChannel ? (
                    <>
                      {/* Channel Header */}
                      <div className="flex items-center justify-between pb-3 border-b border-slate-800">
                        <div>
                          <div className="flex items-center gap-2">
                            <h2 className="text-sm font-bold text-white flex items-center gap-1.5">
                              <span>{selectedChannel.is_private ? '🔒' : '#'}</span> {selectedChannel.name}
                            </h2>
                            {getClassificationBadge(selectedChannel.classification)}
                          </div>
                          <p className="text-xs text-slate-400 mt-0.5">{selectedChannel.description}</p>
                        </div>
                        <div className="text-xs text-slate-400 flex items-center gap-2">
                          <span>👥 {selectedChannel.members?.length || selectedChannel.member_count || 0} members</span>
                        </div>
                      </div>

                      {/* Messages Stream */}
                      <div className="flex-1 overflow-y-auto py-4 space-y-4 pr-1">
                        {channelMessages.length === 0 ? (
                          <div className="flex flex-col items-center justify-center h-48 text-slate-500 text-xs">
                            <span>💬 No messages in this channel yet. Begin the conversation below!</span>
                          </div>
                        ) : (
                          channelMessages.map((msg) => (
                            <div
                              key={msg.id}
                              className={`p-3 rounded-lg border text-xs space-y-1.5 ${
                                msg.is_pinned
                                  ? 'bg-indigo-950/20 border-indigo-800/60'
                                  : 'bg-slate-950/60 border-slate-800/70'
                              }`}
                            >
                              <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                  {msg.sender_type === 'human' ? (
                                    <span className="px-1.5 py-0.5 rounded bg-emerald-950 text-emerald-300 font-bold text-[10px] border border-emerald-800">
                                      👤 HUMAN
                                    </span>
                                  ) : (
                                    <span className="px-1.5 py-0.5 rounded bg-indigo-950 text-indigo-300 font-bold text-[10px] border border-indigo-800">
                                      🤖 AGENT
                                    </span>
                                  )}
                                  <span className="font-semibold text-slate-200">{msg.sender_display_name}</span>
                                </div>
                                <div className="flex items-center gap-2 text-slate-500 text-[10px]">
                                  {msg.is_pinned === 1 && <span className="text-amber-400">📌 Pinned</span>}
                                  <span>{new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                                </div>
                              </div>

                              <p className="text-slate-300 whitespace-pre-line leading-relaxed">{msg.content}</p>

                              {/* References */}
                              {msg.references && msg.references.length > 0 && (
                                <div className="pt-2 flex flex-wrap gap-1.5">
                                  {msg.references.map((r, i) => (
                                    <span key={i} className="px-2 py-0.5 rounded bg-slate-900 border border-slate-700 text-indigo-300 text-[10px] font-mono flex items-center gap-1">
                                      🔗 {r.reference_type.toUpperCase()}: {r.reference_code || `#${r.reference_id}`}
                                    </span>
                                  ))}
                                </div>
                              )}
                            </div>
                          ))
                        )}
                      </div>

                      {/* Message Composer */}
                      <form onSubmit={handleSendChannelMessage} className="pt-3 border-t border-slate-800 flex gap-2">
                        <input
                          type="text"
                          value={channelMessageInput}
                          onChange={(e) => setChannelMessageInput(e.target.value)}
                          placeholder={`Message #${selectedChannel.name}... (strictly validated sender identity)`}
                          className="flex-1 px-3 py-2 rounded bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                        />
                        <button
                          type="submit"
                          className="px-4 py-2 rounded bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition"
                        >
                          Send 📤
                        </button>
                      </form>
                    </>
                  ) : (
                    <div className="flex items-center justify-center h-full text-slate-500 text-xs">
                      Select a channel from the directory.
                    </div>
                  )}
                </div>
              </div>
            )}

            {/* ============================================================== */}
            {/* TAB 3: DIRECT MESSAGES */}
            {/* ============================================================== */}
            {activeTab === 'direct' && (
              <div className="grid grid-cols-1 md:grid-cols-4 gap-6 h-[calc(100vh-230px)]">
                {/* Conversations List */}
                <div className="md:col-span-1 p-4 rounded-lg bg-slate-900 border border-slate-800 flex flex-col h-full">
                  <div className="flex items-center justify-between mb-3">
                    <h3 className="text-xs font-bold text-slate-300 uppercase tracking-wider">Direct Lines</h3>
                    <span className="text-[10px] text-slate-500 font-mono">{conversations.length} total</span>
                  </div>

                  <div className="flex-1 overflow-y-auto space-y-1.5 pr-1">
                    {conversations.map((c) => (
                      <button
                        key={c.id}
                        onClick={() => setSelectedConversationId(c.id)}
                        className={`w-full text-left p-2.5 rounded text-xs transition flex flex-col gap-1 border ${
                          selectedConversationId === c.id
                            ? 'bg-indigo-950/70 border-indigo-700/80 text-white'
                            : 'bg-slate-950/50 border-slate-800/80 text-slate-400 hover:bg-slate-800/50 hover:text-slate-200'
                        }`}
                      >
                        <div className="flex items-center justify-between">
                          <span className="font-semibold truncate">
                            {c.type === 'executive_session' ? '🏛️ ' : '👤 '}{c.title || c.conversation_code}
                          </span>
                          {getClassificationBadge(c.classification)}
                        </div>
                        <span className="text-[11px] text-slate-500">
                          {c.participant_count || c.participants?.length || 2} participants
                        </span>
                      </button>
                    ))}
                  </div>
                </div>

                {/* Direct Messages View */}
                <div className="md:col-span-3 p-4 rounded-lg bg-slate-900 border border-slate-800 flex flex-col h-full">
                  {selectedConversation ? (
                    <>
                      {/* Header */}
                      <div className="flex items-center justify-between pb-3 border-b border-slate-800">
                        <div>
                          <div className="flex items-center gap-2">
                            <h2 className="text-sm font-bold text-white">
                              {selectedConversation.title || selectedConversation.conversation_code}
                            </h2>
                            {getClassificationBadge(selectedConversation.classification)}
                          </div>
                          <p className="text-xs text-slate-400 mt-0.5">
                            Participants: {selectedConversation.participants?.map(p => p.user_name || p.agent_name).join(', ')}
                          </p>
                        </div>
                      </div>

                      {/* Stream */}
                      <div className="flex-1 overflow-y-auto py-4 space-y-4 pr-1">
                        {conversationMessages.map((msg) => (
                          <div
                            key={msg.id}
                            className="p-3 rounded-lg border border-slate-800/80 bg-slate-950/70 text-xs space-y-1"
                          >
                            <div className="flex items-center justify-between">
                              <span className="font-bold text-indigo-300">{msg.sender_display_name}</span>
                              <span className="text-[10px] text-slate-500">{new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                            </div>
                            <p className="text-slate-300">{msg.content}</p>
                          </div>
                        ))}
                      </div>

                      {/* Composer */}
                      <form onSubmit={handleSendDirectMessage} className="pt-3 border-t border-slate-800 flex gap-2">
                        <input
                          type="text"
                          value={directMessageInput}
                          onChange={(e) => setDirectMessageInput(e.target.value)}
                          placeholder="Type direct message..."
                          className="flex-1 px-3 py-2 rounded bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                        />
                        <button
                          type="submit"
                          className="px-4 py-2 rounded bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition"
                        >
                          Send 📤
                        </button>
                      </form>
                    </>
                  ) : (
                    <div className="flex items-center justify-center h-full text-slate-500 text-xs">
                      Select a direct line to view history.
                    </div>
                  )}
                </div>
              </div>
            )}

            {/* ============================================================== */}
            {/* TAB 4: OFFICIAL ANNOUNCEMENTS */}
            {/* ============================================================== */}
            {activeTab === 'announcements' && (
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <h2 className="text-sm font-bold text-white">Broadcast Announcements</h2>
                    <select
                      value={announcementScopeFilter}
                      onChange={(e) => setAnnouncementScopeFilter(e.target.value)}
                      className="px-2.5 py-1 text-xs rounded bg-slate-900 border border-slate-800 text-slate-300"
                    >
                      <option value="">All Scopes</option>
                      <option value="all_organization">Entire Organization</option>
                      <option value="division">Division Only</option>
                      <option value="unit">Unit Only</option>
                    </select>
                  </div>

                  <button
                    onClick={() => setShowCreateAnnouncementModal(true)}
                    className="px-3 py-1.5 rounded bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition flex items-center gap-1.5"
                  >
                    <span>➕ Publish Announcement</span>
                  </button>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {announcements.map((ann) => (
                    <div
                      key={ann.id}
                      className="p-4 rounded-lg bg-slate-900 border border-slate-800 space-y-2.5 hover:border-slate-700 transition"
                    >
                      <div className="flex items-center justify-between text-xs">
                        <span className="font-mono text-indigo-400 font-bold">{ann.announcement_code}</span>
                        <div className="flex items-center gap-2">
                          {getPriorityBadge(ann.priority)}
                          {getClassificationBadge(ann.classification)}
                        </div>
                      </div>

                      <h3 className="text-sm font-bold text-slate-100">{ann.title}</h3>
                      <p className="text-xs text-slate-300 whitespace-pre-line leading-relaxed">{ann.content}</p>

                      <div className="pt-2 border-t border-slate-800 text-[11px] text-slate-500 flex items-center justify-between">
                        <span>Published by: <strong className="text-slate-400">{ann.published_by_name}</strong></span>
                        <span>Scope: <strong className="text-slate-400 font-mono">{ann.target_scope.toUpperCase()}</strong></span>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* ============================================================== */}
            {/* TAB 5: MEETINGS & MoM */}
            {/* ============================================================== */}
            {activeTab === 'meetings' && (
              <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Meetings List */}
                <div className="lg:col-span-1 space-y-3">
                  <div className="flex items-center justify-between">
                    <h3 className="text-xs font-bold text-slate-300 uppercase tracking-wider">Scheduled Meetings</h3>
                    <button
                      onClick={() => setShowCreateMeetingModal(true)}
                      className="px-2.5 py-1 text-xs rounded bg-indigo-600 hover:bg-indigo-500 text-white font-semibold transition"
                    >
                      ➕ Schedule
                    </button>
                  </div>

                  <div className="space-y-2">
                    {meetings.map((m) => (
                      <div
                        key={m.id}
                        onClick={() => setSelectedMeetingId(m.id)}
                        className={`p-3 rounded-lg border text-xs cursor-pointer transition space-y-1.5 ${
                          selectedMeetingId === m.id
                            ? 'bg-indigo-950/60 border-indigo-600 text-white'
                            : 'bg-slate-900 border-slate-800 hover:border-slate-700 text-slate-300'
                        }`}
                      >
                        <div className="flex items-center justify-between">
                          <span className="font-mono text-indigo-400 font-bold">{m.meeting_code}</span>
                          <span className="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-800 text-slate-300">
                            {m.status.toUpperCase()}
                          </span>
                        </div>
                        <p className="font-semibold text-slate-200 line-clamp-1">{m.title}</p>
                        <div className="flex items-center justify-between text-[11px] text-slate-500">
                          <span>📍 {m.room_name || m.room_code || 'VHQ Room'}</span>
                          <span>{m.scheduled_start_time.substring(5, 16)}</span>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>

                {/* Meeting Details & MoM (Right 2 cols) */}
                <div className="lg:col-span-2 p-5 rounded-lg bg-slate-900 border border-slate-800 space-y-5">
                  {selectedMeeting ? (
                    <>
                      {/* Meeting Title Banner */}
                      <div className="flex items-center justify-between pb-4 border-b border-slate-800">
                        <div>
                          <div className="flex items-center gap-2">
                            <span className="font-mono text-sm text-indigo-400 font-bold">{selectedMeeting.meeting_code}</span>
                            <span className="px-2 py-0.5 rounded text-xs font-mono bg-indigo-950 text-indigo-300 border border-indigo-800">
                              {selectedMeeting.status.toUpperCase()}
                            </span>
                          </div>
                          <h2 className="text-base font-bold text-white mt-1">{selectedMeeting.title}</h2>
                          <p className="text-xs text-slate-400 mt-0.5">{selectedMeeting.description}</p>
                        </div>
                        <div className="text-right text-xs text-slate-400 space-y-1">
                          <div className="font-mono text-slate-300">Room: {selectedMeeting.room_name || selectedMeeting.room_code || 'Virtual Boardroom'}</div>
                          <div>Start: {selectedMeeting.scheduled_start_time}</div>
                        </div>
                      </div>

                      {/* Agendas */}
                      <div>
                        <h3 className="text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">📋 Meeting Agendas</h3>
                        <div className="space-y-1.5">
                          {selectedMeeting.agendas?.map((ag) => (
                            <div key={ag.id} className="p-2.5 rounded bg-slate-950 border border-slate-800 text-xs flex items-center justify-between">
                              <div>
                                <span className="font-bold text-slate-200 mr-2">{ag.item_order}. {ag.title}</span>
                                {ag.description && <span className="text-slate-400 text-[11px]">{ag.description}</span>}
                              </div>
                              <span className="text-[10px] font-mono text-indigo-400">{ag.allocated_minutes} mins</span>
                            </div>
                          ))}
                        </div>
                      </div>

                      {/* Participants */}
                      <div>
                        <h3 className="text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">👥 Participants</h3>
                        <div className="flex flex-wrap gap-2">
                          {selectedMeeting.participants?.map((p) => (
                            <span key={p.id} className="px-2.5 py-1 rounded bg-slate-950 border border-slate-800 text-xs flex items-center gap-1.5">
                              <span>{p.participant_type === 'human' ? '👤' : '🤖'}</span>
                              <span className="font-medium text-slate-200">{p.user_name || p.agent_name}</span>
                              <span className="text-[10px] font-mono text-slate-500 uppercase">({p.role})</span>
                            </span>
                          ))}
                        </div>
                      </div>

                      {/* Minutes of Meeting (MoM) Section */}
                      <div className="p-4 rounded-lg bg-slate-950 border border-slate-800/80 space-y-3">
                        <div className="flex items-center justify-between">
                          <h3 className="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-1.5">
                            <span>📝</span> Minutes of Meeting (MoM)
                          </h3>
                          <div className="flex items-center gap-2">
                            {selectedMeeting.minutes?.is_approved ? (
                              <span className="px-2 py-0.5 rounded text-[10px] font-mono bg-emerald-950 text-emerald-300 border border-emerald-800">
                                ✓ FORMALLY APPROVED
                              </span>
                            ) : (
                              <button
                                onClick={handleApproveMom}
                                className="px-2.5 py-1 text-xs rounded bg-emerald-700 hover:bg-emerald-600 text-white font-semibold transition"
                              >
                                Approve MoM ✓
                              </button>
                            )}
                            <button
                              onClick={() => setShowMomModal(true)}
                              className="px-2.5 py-1 text-xs rounded bg-slate-800 hover:bg-slate-700 text-slate-200 transition"
                            >
                              Edit Notes ✏️
                            </button>
                          </div>
                        </div>

                        {selectedMeeting.minutes ? (
                          <div className="space-y-2 text-xs">
                            <div>
                              <span className="text-slate-500 font-semibold block text-[11px]">ATTENDANCE SUMMARY</span>
                              <p className="text-slate-300">{selectedMeeting.minutes.attendance_summary || 'Not recorded'}</p>
                            </div>
                            <div>
                              <span className="text-slate-500 font-semibold block text-[11px]">DISCUSSION NOTES</span>
                              <p className="text-slate-300 whitespace-pre-line">{selectedMeeting.minutes.discussion_notes}</p>
                            </div>
                            <div>
                              <span className="text-slate-500 font-semibold block text-[11px]">FORMAL DECISIONS</span>
                              <p className="text-emerald-300 whitespace-pre-line">{selectedMeeting.minutes.formal_decisions || 'None recorded'}</p>
                            </div>
                          </div>
                        ) : (
                          <p className="text-xs text-slate-500">No minutes prepared yet. Click 'Edit Notes' to draft MoM.</p>
                        )}
                      </div>
                    </>
                  ) : (
                    <div className="text-center py-12 text-slate-500 text-xs">
                      Select a meeting to view details and Minutes of Meeting.
                    </div>
                  )}
                </div>
              </div>
            )}

            {/* ============================================================== */}
            {/* TAB 6: GOVERNANCE DIRECTIVES */}
            {/* ============================================================== */}
            {activeTab === 'directives' && (
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <h2 className="text-sm font-bold text-white">Strategic Directives & Acknowledgements</h2>
                  <span className="text-xs text-slate-400">Strictly tracked against Delegated Authority Level A/B</span>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {directives.map((d) => (
                    <div key={d.id} className="p-4 rounded-lg bg-slate-900 border border-slate-800 space-y-3">
                      <div className="flex items-center justify-between text-xs">
                        <span className="font-mono text-indigo-400 font-bold">{d.direction_code}</span>
                        <span className="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-800 text-slate-300 uppercase">
                          {d.status}
                        </span>
                      </div>
                      <h3 className="text-sm font-bold text-slate-200">{d.subject}</h3>
                      <p className="text-xs text-slate-400">{d.description}</p>
                      
                      {d.ack_notes && (
                        <div className="p-2.5 rounded bg-slate-950 border border-slate-800 text-xs space-y-1">
                          <span className="text-[10px] font-mono text-emerald-400 block">
                            ACKNOWLEDGEMENT ({d.ack_status?.toUpperCase()})
                          </span>
                          <p className="text-slate-300">{d.ack_notes}</p>
                        </div>
                      )}

                      <div className="pt-2 flex justify-end">
                        <button
                          onClick={() => {
                            setSelectedDirective(d);
                            setAckStatus(d.ack_status || 'acknowledged');
                            setAckNotes(d.ack_notes || '');
                            setAckModalOpen(true);
                          }}
                          className="px-3 py-1 text-xs rounded bg-indigo-600 hover:bg-indigo-500 text-white font-medium transition"
                        >
                          Update Status ✍️
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* ============================================================== */}
            {/* TAB 7: ACTION ITEMS */}
            {/* ============================================================== */}
            {activeTab === 'action_items' && (
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <h2 className="text-sm font-bold text-white">Meeting Action Items & Task Links</h2>
                    <select
                      value={actionItemStatusFilter}
                      onChange={(e) => setActionItemStatusFilter(e.target.value)}
                      className="px-2.5 py-1 text-xs rounded bg-slate-900 border border-slate-800 text-slate-300"
                    >
                      <option value="">All Statuses</option>
                      <option value="pending">Pending</option>
                      <option value="assigned_to_task">Assigned to Task</option>
                      <option value="in_progress">In Progress</option>
                      <option value="completed">Completed</option>
                    </select>
                  </div>
                  <span className="text-xs text-slate-400 font-mono">{actionItems.length} items</span>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  {actionItems.map((ai) => (
                    <div key={ai.id} className="p-4 rounded-lg bg-slate-900 border border-slate-800 space-y-2.5 text-xs">
                      <div className="flex items-center justify-between">
                        <span className="font-mono text-indigo-400 font-bold">{ai.meeting_code || `MTG #${ai.meeting_id}`}</span>
                        <span className={`px-2 py-0.5 rounded text-[10px] font-mono ${
                          ai.status === 'completed' ? 'bg-emerald-950 text-emerald-300' : 'bg-slate-800 text-slate-300'
                        }`}>
                          {ai.status.toUpperCase()}
                        </span>
                      </div>
                      <p className="text-slate-200 font-medium">{ai.description}</p>
                      
                      <div className="text-slate-400 text-[11px] space-y-0.5">
                        <div>Assignee: <strong className="text-slate-300">{ai.assignee_user_name || ai.assignee_agent_name || 'Agent'}</strong></div>
                        <div>Due: <strong className="text-slate-300">{ai.due_date || 'No deadline'}</strong></div>
                        {ai.linked_task_code && (
                          <div className="text-indigo-300 font-mono">Linked Task: {ai.linked_task_code}</div>
                        )}
                      </div>

                      <div className="pt-2 border-t border-slate-800 flex justify-end gap-1.5">
                        {ai.status !== 'completed' && (
                          <button
                            onClick={() => handleActionItemStatus(ai.id, 'completed')}
                            className="px-2 py-1 rounded bg-emerald-800 hover:bg-emerald-700 text-white text-[11px] transition"
                          >
                            Mark Done ✓
                          </button>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* ============================================================== */}
            {/* TAB 8: SEARCH & NOTIFICATIONS */}
            {/* ============================================================== */}
            {activeTab === 'notifications_search' && (
              <div className="space-y-6">
                {/* Search Bar */}
                <form onSubmit={handleSearch} className="flex gap-2">
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    placeholder="Search messages, channels, meetings, announcements, action items..."
                    className="flex-1 px-4 py-2.5 rounded bg-slate-900 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                  />
                  <button
                    type="submit"
                    disabled={searchLoading}
                    className="px-6 py-2.5 rounded bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition"
                  >
                    {searchLoading ? 'Searching...' : 'Search 🔍'}
                  </button>
                </form>

                {/* Search Results */}
                {searchResults && (
                  <div className="p-4 rounded-lg bg-slate-900 border border-slate-800 space-y-3">
                    <h3 className="text-xs font-bold text-slate-300 uppercase tracking-wider">
                      Search Results for "{searchResults.query}" ({searchResults.total_results} hits)
                    </h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                      {searchResults.channels.map(c => (
                        <div key={c.id} className="p-2.5 rounded bg-slate-950 border border-slate-800">
                          <span className="font-bold text-indigo-400"># {c.name}</span>
                          <p className="text-slate-400 text-[11px] mt-0.5">{c.description}</p>
                        </div>
                      ))}
                      {searchResults.meetings.map(m => (
                        <div key={m.id} className="p-2.5 rounded bg-slate-950 border border-slate-800">
                          <span className="font-bold text-emerald-400">📅 {m.title}</span>
                          <p className="text-slate-400 text-[11px] mt-0.5">{m.description}</p>
                        </div>
                      ))}
                      {searchResults.messages.map(msg => (
                        <div key={msg.id} className="p-2.5 rounded bg-slate-950 border border-slate-800">
                          <span className="font-bold text-purple-400">💬 {msg.sender_display_name}:</span>
                          <p className="text-slate-300 text-[11px] mt-0.5">{msg.content}</p>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Notifications Center */}
                <div className="p-5 rounded-lg bg-slate-900 border border-slate-800 space-y-3">
                  <div className="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 className="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-2">
                      <span>🔔</span> In-App Notification Center
                    </h3>
                    <button
                      onClick={handleMarkAllRead}
                      className="text-xs text-indigo-400 hover:text-indigo-300"
                    >
                      Mark all as read
                    </button>
                  </div>

                  <div className="space-y-2">
                    {notifications.map((n) => (
                      <div
                        key={n.id}
                        className={`p-3 rounded-lg border text-xs flex items-start justify-between ${
                          n.is_read ? 'bg-slate-950/40 border-slate-800/60 text-slate-400' : 'bg-slate-950 border-indigo-900/60 text-slate-200'
                        }`}
                      >
                        <div className="space-y-0.5">
                          <div className="flex items-center gap-2">
                            <span className="font-bold text-white">{n.title}</span>
                            <span className="text-[10px] font-mono uppercase text-slate-500">[{n.type}]</span>
                          </div>
                          <p className="text-xs text-slate-300">{n.message}</p>
                        </div>
                        <span className="text-[10px] text-slate-500 whitespace-nowrap ml-4">
                          {new Date(n.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                        </span>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            )}
          </>
        )}
      </div>

      {/* ============================================================== */}
      {/* MODALS */}
      {/* ============================================================== */}

      {/* Acknowledge Directive Modal */}
      {ackModalOpen && selectedDirective && (
        <div className="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
          <div className="bg-slate-900 border border-slate-800 rounded-lg max-w-lg w-full p-5 space-y-4">
            <h3 className="text-sm font-bold text-white">
              Acknowledge & Report on {selectedDirective.direction_code}
            </h3>
            <p className="text-xs text-slate-400">{selectedDirective.subject}</p>

            <div className="space-y-3 text-xs">
              <div>
                <label className="block text-slate-400 mb-1">Status</label>
                <select
                  value={ackStatus}
                  onChange={(e) => setAckStatus(e.target.value as any)}
                  className="w-full px-3 py-2 rounded bg-slate-950 border border-slate-800 text-white"
                >
                  <option value="acknowledged">Acknowledged (Received)</option>
                  <option value="in_execution">In Execution (Work underway)</option>
                  <option value="executed">Executed (Completed)</option>
                  <option value="clarification_requested">Clarification Requested</option>
                  <option value="impediment_reported">Impediment Reported</option>
                </select>
              </div>

              <div>
                <label className="block text-slate-400 mb-1">Notes / Progress Report</label>
                <textarea
                  rows={3}
                  value={ackNotes}
                  onChange={(e) => setAckNotes(e.target.value)}
                  placeholder="Details of action taken or impediments encountered..."
                  className="w-full px-3 py-2 rounded bg-slate-950 border border-slate-800 text-white"
                />
              </div>
            </div>

            <div className="flex justify-end gap-2 pt-2">
              <button
                onClick={() => setAckModalOpen(false)}
                className="px-3 py-1.5 rounded bg-slate-800 text-slate-300 text-xs"
              >
                Cancel
              </button>
              <button
                onClick={handleDirectiveAck}
                className="px-4 py-1.5 rounded bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold"
              >
                Submit Report ✓
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Publish Announcement Modal */}
      {showCreateAnnouncementModal && (
        <div className="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
          <div className="bg-slate-900 border border-slate-800 rounded-lg max-w-lg w-full p-5 space-y-4">
            <h3 className="text-sm font-bold text-white">Publish Official Announcement</h3>
            <form onSubmit={handleCreateAnnouncement} className="space-y-3 text-xs">
              <div>
                <label className="block text-slate-400 mb-1">Title</label>
                <input
                  type="text"
                  required
                  value={announcementForm.title}
                  onChange={(e) => setAnnouncementForm({ ...announcementForm, title: e.target.value })}
                  className="w-full px-3 py-2 rounded bg-slate-950 border border-slate-800 text-white"
                />
              </div>

              <div>
                <label className="block text-slate-400 mb-1">Content</label>
                <textarea
                  rows={4}
                  required
                  value={announcementForm.content}
                  onChange={(e) => setAnnouncementForm({ ...announcementForm, content: e.target.value })}
                  className="w-full px-3 py-2 rounded bg-slate-950 border border-slate-800 text-white"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-slate-400 mb-1">Target Scope</label>
                  <select
                    value={announcementForm.target_scope}
                    onChange={(e) => setAnnouncementForm({ ...announcementForm, target_scope: e.target.value as any })}
                    className="w-full px-3 py-2 rounded bg-slate-950 border border-slate-800 text-white"
                  >
                    <option value="all_organization">Entire Organization</option>
                    <option value="division">Division Only</option>
                    <option value="unit">Unit Only</option>
                  </select>
                </div>
                <div>
                  <label className="block text-slate-400 mb-1">Priority</label>
                  <select
                    value={announcementForm.priority}
                    onChange={(e) => setAnnouncementForm({ ...announcementForm, priority: e.target.value as any })}
                    className="w-full px-3 py-2 rounded bg-slate-950 border border-slate-800 text-white"
                  >
                    <option value="routine">Routine</option>
                    <option value="important">Important</option>
                    <option value="urgent">Urgent</option>
                    <option value="critical">Critical</option>
                  </select>
                </div>
              </div>

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setShowCreateAnnouncementModal(false)}
                  className="px-3 py-1.5 rounded bg-slate-800 text-slate-300"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-1.5 rounded bg-indigo-600 hover:bg-indigo-500 text-white font-semibold"
                >
                  Publish 📢
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Schedule Meeting Modal */}
      {showCreateMeetingModal && (
        <div className="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
          <div className="bg-slate-900 border border-slate-800 rounded-lg max-w-lg w-full p-5 space-y-4">
            <h3 className="text-sm font-bold text-white">Schedule Formal Meeting</h3>
            <form onSubmit={handleCreateMeeting} className="space-y-3 text-xs">
              <div>
                <label className="block text-slate-400 mb-1">Title</label>
                <input
                  type="text"
                  required
                  value={meetingForm.title}
                  onChange={(e) => setMeetingForm({ ...meetingForm, title: e.target.value })}
                  className="w-full px-3 py-2 rounded bg-slate-950 border border-slate-800 text-white"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-slate-400 mb-1">Start Time</label>
                  <input
                    type="datetime-local"
                    required
                    value={meetingForm.scheduled_start_time}
                    onChange={(e) => setMeetingForm({ ...meetingForm, scheduled_start_time: e.target.value })}
                    className="w-full px-3 py-2 rounded bg-slate-950 border border-slate-800 text-white"
                  />
                </div>
                <div>
                  <label className="block text-slate-400 mb-1">End Time</label>
                  <input
                    type="datetime-local"
                    required
                    value={meetingForm.scheduled_end_time}
                    onChange={(e) => setMeetingForm({ ...meetingForm, scheduled_end_time: e.target.value })}
                    className="w-full px-3 py-2 rounded bg-slate-950 border border-slate-800 text-white"
                  />
                </div>
              </div>

              <div>
                <label className="block text-slate-400 mb-1">Category</label>
                <select
                  value={meetingForm.category}
                  onChange={(e) => setMeetingForm({ ...meetingForm, category: e.target.value as any })}
                  className="w-full px-3 py-2 rounded bg-slate-950 border border-slate-800 text-white"
                >
                  <option value="executive_coordination">Executive Coordination</option>
                  <option value="board_meeting">Board Meeting</option>
                  <option value="technical_review">Technical Review</option>
                  <option value="division_sync">Division Sync</option>
                </select>
              </div>

              <div>
                <label className="block text-slate-400 mb-1">Description</label>
                <textarea
                  rows={2}
                  value={meetingForm.description}
                  onChange={(e) => setMeetingForm({ ...meetingForm, description: e.target.value })}
                  className="w-full px-3 py-2 rounded bg-slate-950 border border-slate-800 text-white"
                />
              </div>

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setShowCreateMeetingModal(false)}
                  className="px-3 py-1.5 rounded bg-slate-800 text-slate-300"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-1.5 rounded bg-indigo-600 hover:bg-indigo-500 text-white font-semibold"
                >
                  Schedule 📅
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Edit MoM Modal */}
      {showMomModal && (
        <div className="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
          <div className="bg-slate-900 border border-slate-800 rounded-lg max-w-xl w-full p-5 space-y-4">
            <h3 className="text-sm font-bold text-white">Draft Minutes of Meeting (MoM)</h3>
            <div className="space-y-3 text-xs">
              <div>
                <label className="block text-slate-400 mb-1">Attendance Summary</label>
                <input
                  type="text"
                  value={momForm.attendance_summary}
                  onChange={(e) => setMomForm({ ...momForm, attendance_summary: e.target.value })}
                  placeholder="e.g. Dihadiri lengkap oleh Human Owner dan Executive Board"
                  className="w-full px-3 py-2 rounded bg-slate-950 border border-slate-800 text-white"
                />
              </div>

              <div>
                <label className="block text-slate-400 mb-1">Discussion Notes</label>
                <textarea
                  rows={4}
                  value={momForm.discussion_notes}
                  onChange={(e) => setMomForm({ ...momForm, discussion_notes: e.target.value })}
                  placeholder="Summary of deliberative points discussed..."
                  className="w-full px-3 py-2 rounded bg-slate-950 border border-slate-800 text-white"
                />
              </div>

              <div>
                <label className="block text-slate-400 mb-1">Formal Decisions Enacted</label>
                <textarea
                  rows={3}
                  value={momForm.formal_decisions}
                  onChange={(e) => setMomForm({ ...momForm, formal_decisions: e.target.value })}
                  placeholder="Enacted resolutions and formal decisions..."
                  className="w-full px-3 py-2 rounded bg-slate-950 border border-slate-800 text-white"
                />
              </div>
            </div>

            <div className="flex justify-end gap-2 pt-2">
              <button
                type="button"
                onClick={() => setShowMomModal(false)}
                className="px-3 py-1.5 rounded bg-slate-800 text-slate-300"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={handleSaveMom}
                className="px-4 py-1.5 rounded bg-indigo-600 hover:bg-indigo-500 text-white font-semibold"
              >
                Save MoM 💾
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default CollaborationView;
