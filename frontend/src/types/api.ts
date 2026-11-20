/**
 * MCE AI Office — API Contract and Response Types
 */

export interface ApiResponse<T = unknown> {
  status: 'success' | 'error';
  code: number;
  message: string;
  data: T;
  meta?: Record<string, unknown>;
  errors?: unknown;
  timestamp: string;
}

export interface SystemHealthData {
  system: string;
  service: string;
  status: string;
  environment: string;
  php_version: string;
  ci_version: string;
  database: {
    connected: boolean;
    driver: string;
    version: string | null;
  };
  server_time: string;
}

export interface DatabaseHealthData {
  connected: boolean;
  driver: string;
  version: string | null;
  database: string | null;
}

export interface ApiClientConfig {
  baseUrl: string;
  timeoutMs?: number;
}
