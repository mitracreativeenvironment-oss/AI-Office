import React from 'react';
import type { User } from '../types/auth';

interface NavigationHeaderProps {
  currentView: 'dashboard' | 'vhq' | 'runtime' | 'welfare';
  onNavigate: (view: 'dashboard' | 'vhq' | 'runtime' | 'welfare') => void;
  apiStatus: 'checking' | 'online' | 'degraded' | 'offline';
  latencyMs: number | null;
  user: User | null;
  onLoginClick: () => void;
  onLogout: () => void;
}

export const NavigationHeader: React.FC<NavigationHeaderProps> = ({
  currentView,
  onNavigate,
  apiStatus,
  latencyMs,
  user,
  onLoginClick,
  onLogout,
}) => {
  const getStatusBadge = () => {
    switch (apiStatus) {
      case 'online':
        return (
          <span className="mce-status-pill mce-status-online" title="Backend API and MySQL are operational">
            <span className="mce-status-dot"></span>
            API ONLINE {latencyMs !== null ? `(${latencyMs}ms)` : ''}
          </span>
        );
      case 'degraded':
        return (
          <span className="mce-status-pill mce-status-degraded" title="Backend responsive, database disconnected">
            <span className="mce-status-dot"></span>
            DB DISCONNECTED
          </span>
        );
      case 'offline':
        return (
          <span className="mce-status-pill mce-status-offline" title="Backend API unreachable">
            <span className="mce-status-dot"></span>
            API OFFLINE
          </span>
        );
      case 'checking':
      default:
        return (
          <span className="mce-status-pill mce-status-checking" title="Connecting to backend...">
            <span className="mce-status-dot mce-pulse"></span>
            CHECKING...
          </span>
        );
    }
  };

  return (
    <header className="mce-nav-header">
      <div className="mce-nav-brand">
        <div className="mce-logo-badge">MCE</div>
        <div>
          <h1 className="mce-brand-title">MCE AI Office</h1>
          <p className="mce-brand-sub">Mitra Creative Environment</p>
        </div>
      </div>

      <nav className="mce-nav-links" aria-label="Main Navigation">
        <button
          type="button"
          className={`mce-nav-btn ${currentView === 'dashboard' ? 'mce-nav-btn-active' : ''}`}
          onClick={() => onNavigate('dashboard')}
        >
          <span className="mce-nav-icon">📊</span>
          Administrative Dashboard
        </button>
        <button
          type="button"
          className={`mce-nav-btn ${currentView === 'vhq' ? 'mce-nav-btn-active' : ''}`}
          onClick={() => onNavigate('vhq')}
        >
          <span className="mce-nav-icon">🏢</span>
          Virtual Headquarters (3D)
        </button>
        <button
          type="button"
          className={`mce-nav-btn ${currentView === 'runtime' ? 'mce-nav-btn-active' : ''}`}
          onClick={() => onNavigate('runtime')}
        >
          <span className="mce-nav-icon">⚡</span>
          Runtime & 9Router
        </button>
        <button
          type="button"
          className={`mce-nav-btn ${currentView === 'welfare' ? 'mce-nav-btn-active' : ''}`}
          onClick={() => onNavigate('welfare')}
        >
          <span className="mce-nav-icon">🌿</span>
          Agent Welfare
        </button>
      </nav>

      <div className="mce-nav-tools">
        {getStatusBadge()}

        {user ? (
          <div className="mce-user-menu">
            <div className="mce-user-avatar" title={`User ID: ${user.id}`}>
              {user.display_name.charAt(0).toUpperCase()}
            </div>
            <div className="mce-user-info">
              <span className="mce-user-name">{user.display_name}</span>
              <span className="mce-user-role-badge">
                {(user.roles && user.roles.length > 0 ? user.roles[0] : 'user').toUpperCase()}
              </span>
            </div>
            <button
              type="button"
              className="mce-btn mce-btn-secondary mce-btn-sm"
              onClick={onLogout}
              title="Sign out and revoke token"
            >
              Sign Out
            </button>
          </div>
        ) : (
          <button
            type="button"
            className="mce-btn mce-btn-primary mce-btn-sm"
            onClick={onLoginClick}
          >
            Sign In
          </button>
        )}
      </div>
    </header>
  );
};
