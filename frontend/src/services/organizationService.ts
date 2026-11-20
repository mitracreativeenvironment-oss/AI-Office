import { apiClient } from './apiClient';
import type {
  OrganizationUnit,
  OrganizationPosition,
  OrganizationAppointment,
  CoreValue,
  GovernanceBody,
  AuthorityProfile,
  DelegationRecord,
  OwnerDirection,
  ExecutiveDecision,
  ReportingRelationship,
} from '../types/organization';

export const organizationService = {
  // 1. Organizational Hierarchy
  async getTree(): Promise<OrganizationUnit[]> {
    const res = await apiClient.request<OrganizationUnit[]>('/organization/tree');
    return res.data;
  },

  async getUnits(status?: string): Promise<OrganizationUnit[]> {
    const query = status ? `?status=${encodeURIComponent(status)}` : '';
    const res = await apiClient.request<OrganizationUnit[]>(`/organization/units${query}`);
    return res.data;
  },

  async getUnit(id: number): Promise<OrganizationUnit> {
    const res = await apiClient.request<OrganizationUnit>(`/organization/units/${id}`);
    return res.data;
  },

  async getPositions(unitId?: number): Promise<OrganizationPosition[]> {
    const query = unitId ? `?unit_id=${unitId}` : '';
    const res = await apiClient.request<OrganizationPosition[]>(`/organization/positions${query}`);
    return res.data;
  },

  async getPosition(id: number): Promise<OrganizationPosition> {
    const res = await apiClient.request<OrganizationPosition>(`/organization/positions/${id}`);
    return res.data;
  },

  async getAppointments(positionId?: number, unitId?: number): Promise<OrganizationAppointment[]> {
    const params = new URLSearchParams();
    if (positionId) params.append('position_id', String(positionId));
    if (unitId) params.append('unit_id', String(unitId));
    const query = params.toString() ? `?${params.toString()}` : '';
    const res = await apiClient.request<OrganizationAppointment[]>(`/organization/appointments${query}`);
    return res.data;
  },

  async getReporting(): Promise<ReportingRelationship[]> {
    const res = await apiClient.request<ReportingRelationship[]>('/organization/reporting');
    return res.data;
  },

  // 2. Corporate Governance & Reference Data
  async getCoreValues(): Promise<CoreValue[]> {
    const res = await apiClient.request<CoreValue[]>('/governance/core-values');
    return res.data;
  },

  async getBodies(): Promise<GovernanceBody[]> {
    const res = await apiClient.request<GovernanceBody[]>('/governance/bodies');
    return res.data;
  },

  async getBody(id: number): Promise<GovernanceBody> {
    const res = await apiClient.request<GovernanceBody>(`/governance/bodies/${id}`);
    return res.data;
  },

  async getAuthorityProfiles(): Promise<AuthorityProfile[]> {
    const res = await apiClient.request<AuthorityProfile[]>('/governance/authority-profiles');
    return res.data;
  },

  async checkAuthority(authorityLevel: string, amount: number, action: string) {
    const res = await apiClient.request<{
      allowed: boolean;
      authority_level: string;
      amount: number;
      action: string;
      requires_owner: boolean;
      reason: string;
    }>('/governance/authority-check', {
      method: 'POST',
      body: JSON.stringify({
        authority_level: authorityLevel,
        amount,
        action,
      }),
    });
    return res.data;
  },

  async getDelegations(status?: string): Promise<DelegationRecord[]> {
    const query = status ? `?status=${encodeURIComponent(status)}` : '';
    const res = await apiClient.request<DelegationRecord[]>(`/governance/delegations${query}`);
    return res.data;
  },

  async getDirections(status?: string, type?: string): Promise<OwnerDirection[]> {
    const params = new URLSearchParams();
    if (status) params.append('status', status);
    if (type) params.append('type', type);
    const query = params.toString() ? `?${params.toString()}` : '';
    const res = await apiClient.request<OwnerDirection[]>(`/governance/directions${query}`);
    return res.data;
  },

  async getDecisions(status?: string, category?: string): Promise<ExecutiveDecision[]> {
    const params = new URLSearchParams();
    if (status) params.append('status', status);
    if (category) params.append('category', category);
    const query = params.toString() ? `?${params.toString()}` : '';
    const res = await apiClient.request<ExecutiveDecision[]>(`/governance/decisions${query}`);
    return res.data;
  },

  async getAuditLogs(limit = 50, offset = 0) {
    const res = await apiClient.request<any[]>(`/governance/audit-logs?limit=${limit}&offset=${offset}`);
    return res.data;
  },
};
