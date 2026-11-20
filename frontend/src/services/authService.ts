/**
 * MCE AI Office — Authentication & IAM Client Service
 */

import { apiClient } from './apiClient';
import type { ApiResponse } from '../types/api';
import type { User, AuthSessionData, Role, Permission, SecurityEvent } from '../types/auth';

const TOKEN_KEY = 'mce_bearer_token';

class AuthService {
  private currentToken: string | null = null;

  constructor() {
    this.currentToken = localStorage.getItem(TOKEN_KEY);
    apiClient.setTokenProvider(() => this.currentToken);
  }

  public getToken(): string | null {
    return this.currentToken;
  }

  public isAuthenticated(): boolean {
    return Boolean(this.currentToken);
  }

  /**
   * Log in user with credentials and persist bearer token.
   */
  public async login(login: string, password: string): Promise<User> {
    const res = await apiClient.request<AuthSessionData>('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ login, password }),
    });

    const session = res.data;
    this.currentToken = session.token;
    localStorage.setItem(TOKEN_KEY, session.token);

    return session.user;
  }

  /**
   * Log out user, revoking token on server and clearing storage.
   */
  public async logout(): Promise<void> {
    try {
      if (this.currentToken) {
        await apiClient.request('/auth/logout', { method: 'POST' });
      }
    } finally {
      this.currentToken = null;
      localStorage.removeItem(TOKEN_KEY);
    }
  }

  /**
   * Fetch current authenticated identity profile & permissions from server.
   */
  public async getMe(): Promise<User | null> {
    if (!this.currentToken) return null;
    try {
      const res = await apiClient.request<User>('/auth/me', { method: 'GET' });
      return res.data;
    } catch {
      // Token is invalid or expired
      this.currentToken = null;
      localStorage.removeItem(TOKEN_KEY);
      return null;
    }
  }

  /**
   * Change password and refresh session.
   */
  public async changePassword(currentPassword: string, newPassword: string): Promise<string> {
    const res = await apiClient.request<{ token: string; message: string }>('/auth/change-password', {
      method: 'POST',
      body: JSON.stringify({ current_password: currentPassword, new_password: newPassword }),
    });

    if (res.data?.token) {
      this.currentToken = res.data.token;
      localStorage.setItem(TOKEN_KEY, res.data.token);
    }

    return res.data.message;
  }

  /**
   * RBAC Admin: List all users
   */
  public async listUsers(): Promise<User[]> {
    const res = await apiClient.request<User[]>('/admin/users');
    return res.data;
  }

  /**
   * RBAC Admin: List all roles with assigned permissions
   */
  public async listRoles(): Promise<Role[]> {
    const res = await apiClient.request<Role[]>('/admin/roles');
    return res.data;
  }

  /**
   * RBAC Admin: List all permissions
   */
  public async listPermissions(): Promise<Permission[]> {
    const res = await apiClient.request<Permission[]>('/admin/permissions');
    return res.data;
  }

  /**
   * RBAC Admin: List recent security audit logs
   */
  public async listAuditLogs(): Promise<SecurityEvent[]> {
    const res = await apiClient.request<SecurityEvent[]>('/admin/audit-logs');
    return res.data;
  }
}

export const authService = new AuthService();
