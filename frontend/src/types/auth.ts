/**
 * MCE AI Office — IAM & Authentication Types
 */

export interface User {
  id: number;
  username: string;
  email: string;
  display_name: string;
  full_name?: string;
  status: 'active' | 'locked' | 'suspended' | 'disabled';
  roles: string[];
  permissions: string[];
  last_login_at?: string | null;
  created_at?: string;
}

export interface AuthSessionData {
  token: string;
  expires_at: string;
  user: User;
}

export interface Role {
  id: number;
  name: string;
  slug: string;
  description?: string;
  is_system: number;
  permissions?: Permission[];
}

export interface Permission {
  id: number;
  name: string;
  slug: string;
  module: string;
  description?: string;
}

export interface SecurityEvent {
  id: number;
  user_id?: number | null;
  event_type: string;
  actor_identifier?: string | null;
  ip_address?: string | null;
  user_agent?: string | null;
  status: 'success' | 'failure';
  metadata?: Record<string, unknown> | null;
  created_at: string;
}
