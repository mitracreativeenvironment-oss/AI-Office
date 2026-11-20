/**
 * MCE AI Office — Centralized API Client Abstraction
 * Handles timeouts, network errors, and typed envelopes with future token readiness.
 */

import type { ApiResponse, SystemHealthData, DatabaseHealthData } from '../types/api';

export class ApiError extends Error {
  public readonly code: number;
  public readonly status: string;
  public readonly isNetworkError: boolean;
  public readonly isTimeout: boolean;
  public readonly rawError?: unknown;

  constructor(message: string, options?: { code?: number; status?: string; isNetworkError?: boolean; isTimeout?: boolean; rawError?: unknown }) {
    super(message);
    this.name = 'ApiError';
    this.code = options?.code ?? 500;
    this.status = options?.status ?? 'error';
    this.isNetworkError = options?.isNetworkError ?? false;
    this.isTimeout = options?.isTimeout ?? false;
    this.rawError = options?.rawError;
  }
}

class ApiClient {
  private baseUrl: string;
  private defaultTimeoutMs: number;
  private tokenProvider: (() => string | null) | null = null;

  constructor() {
    // In Vite development, relative `/api/v1` uses the dev proxy, avoiding CORS issues.
    this.baseUrl = import.meta.env.VITE_API_BASE_URL || '/api/v1';
    this.defaultTimeoutMs = 8000;
  }

  /**
   * Register a dynamic bearer token provider for future authentication (Prompt 17+).
   */
  public setTokenProvider(provider: () => string | null) {
    this.tokenProvider = provider;
  }

  public getBaseUrl(): string {
    return this.baseUrl;
  }

  /**
   * Core request dispatcher with abort controller and error normalization.
   */
  public async request<T>(endpoint: string, options: RequestInit = {}, timeoutMs?: number): Promise<ApiResponse<T>> {
    const controller = new AbortController();
    const timeout = timeoutMs ?? this.defaultTimeoutMs;
    const timer = setTimeout(() => controller.abort(), timeout);

    const cleanEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
    const url = `${this.baseUrl}${cleanEndpoint}`;

    const headers: Record<string, string> = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      ...(options.headers as Record<string, string> || {}),
    };

    if (this.tokenProvider) {
      const token = this.tokenProvider();
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }
    }

    try {
      const response = await fetch(url, {
        ...options,
        headers,
        signal: controller.signal,
      });

      clearTimeout(timer);

      const json = await response.json().catch(() => null);

      if (!response.ok) {
        throw new ApiError(json?.message || `HTTP ${response.status} Error`, {
          code: response.status,
          status: json?.status || 'error',
          rawError: json,
        });
      }

      return json as ApiResponse<T>;
    } catch (err: unknown) {
      clearTimeout(timer);

      if (err instanceof ApiError) {
        throw err;
      }

      if (err instanceof DOMException && err.name === 'AbortError') {
        throw new ApiError(`Request timeout after ${timeout}ms: ${url}`, {
          code: 408,
          isTimeout: true,
        });
      }

      const msg = err instanceof Error ? err.message : 'Unknown network failure';
      throw new ApiError(`Network communication error: ${msg}`, {
        code: 0,
        isNetworkError: true,
        rawError: err,
      });
    }
  }

  /**
   * Health Check Diagnostic (System & Environment)
   */
  public async checkHealth(): Promise<ApiResponse<SystemHealthData>> {
    return this.request<SystemHealthData>('/health');
  }

  /**
   * Database Connectivity Diagnostic
   */
  public async checkDatabaseHealth(): Promise<ApiResponse<DatabaseHealthData>> {
    return this.request<DatabaseHealthData>('/health/database');
  }
}

export const apiClient = new ApiClient();
