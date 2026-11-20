import React, { useState } from 'react';
import { authService } from '../services/authService';
import type { User } from '../types/auth';

interface LoginPageProps {
  onSuccess: (user: User) => void;
  onCancel?: () => void;
}

export const LoginPage: React.FC<LoginPageProps> = ({ onSuccess, onCancel }) => {
  const [login, setLogin] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!login.trim() || !password) {
      setError('Please provide both username/email and password.');
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const user = await authService.login(login.trim(), password);
      onSuccess(user);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Authentication failed.');
    } finally {
      setLoading(false);
    }
  };

  const fillCredentials = (user: string, pass: string) => {
    setLogin(user);
    setPassword(pass);
    setError(null);
  };

  return (
    <div className="mce-login-container">
      <div className="mce-login-card">
        <div className="mce-login-header">
          <div className="mce-logo-badge mce-logo-large">MCE</div>
          <h2 className="mce-login-title">Sign In to MCE AI Office</h2>
          <p className="mce-login-subtitle">Mitra Creative Environment — Enterprise Access Portal</p>
        </div>

        {error && (
          <div className="mce-alert-error" role="alert">
            <span>⚠️</span>
            <span>{error}</span>
          </div>
        )}

        <form onSubmit={handleSubmit} className="mce-login-form">
          <div className="mce-form-group">
            <label htmlFor="login-identifier" className="mce-form-label">
              Username or Email
            </label>
            <input
              id="login-identifier"
              type="text"
              autoComplete="username"
              className="mce-input"
              placeholder="e.g. owner or admin@mce.local"
              value={login}
              onChange={(e) => setLogin(e.target.value)}
              disabled={loading}
              required
            />
          </div>

          <div className="mce-form-group">
            <div className="mce-form-label-row">
              <label htmlFor="login-password" className="mce-form-label">
                Password
              </label>
              <button
                type="button"
                className="mce-btn-link"
                onClick={() => setShowPassword(!showPassword)}
                tabIndex={-1}
              >
                {showPassword ? 'Hide' : 'Show'}
              </button>
            </div>
            <input
              id="login-password"
              type={showPassword ? 'text' : 'password'}
              autoComplete="current-password"
              className="mce-input"
              placeholder="Enter your account password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              disabled={loading}
              required
            />
          </div>

          <button
            type="submit"
            className="mce-btn mce-btn-primary mce-btn-block"
            disabled={loading}
          >
            {loading ? 'Authenticating...' : 'Sign In with Bearer Token'}
          </button>
        </form>

        <div className="mce-dev-credentials">
          <p className="mce-dev-label">Development Quick-Fill Accounts:</p>
          <div className="mce-dev-buttons">
            <button
              type="button"
              className="mce-btn mce-btn-secondary mce-btn-sm"
              onClick={() => fillCredentials('owner', 'admin123')}
            >
              Fill: Human Owner
            </button>
            <button
              type="button"
              className="mce-btn mce-btn-secondary mce-btn-sm"
              onClick={() => fillCredentials('admin', 'admin123')}
            >
              Fill: Administrator
            </button>
          </div>
        </div>

        {onCancel && (
          <button
            type="button"
            className="mce-btn mce-btn-secondary mce-btn-block"
            onClick={onCancel}
            style={{ marginTop: '12px' }}
          >
            Cancel / Back to Overview
          </button>
        )}
      </div>
    </div>
  );
};
