import { useState, useEffect, useCallback } from 'react';
import { NavigationHeader } from './components/NavigationHeader';
import { DashboardShell } from './components/DashboardShell';
import { VhqView } from './components/VhqView';
import { LoginPage } from './components/LoginPage';
import { RuntimeManagementView } from './components/RuntimeManagementView';
import { WelfareManagementView } from './components/WelfareManagementView';
import { KnowledgeManagementView } from './components/KnowledgeManagementView';
import { apiClient } from './services/apiClient';
import { authService } from './services/authService';
import type { User } from './types/auth';

export default function App() {
  const getInitialView = (): 'dashboard' | 'vhq' | 'runtime' | 'welfare' | 'knowledge' => {
    const hash = window.location.hash.toLowerCase();
    if (hash === '#vhq' || hash === '#/vhq') return 'vhq';
    if (hash === '#dashboard' || hash === '#/dashboard') return 'dashboard';
    if (hash === '#runtime' || hash === '#/runtime') return 'runtime';
    if (hash === '#welfare' || hash === '#/welfare') return 'welfare';
    if (hash === '#knowledge' || hash === '#/knowledge') return 'knowledge';
    const params = new URLSearchParams(window.location.search);
    const viewParam = params.get('view');
    if (viewParam === 'vhq') return 'vhq';
    if (viewParam === 'runtime') return 'runtime';
    if (viewParam === 'welfare') return 'welfare';
    if (viewParam === 'knowledge') return 'knowledge';
    return 'dashboard';
  };

  const [currentView, setCurrentView] = useState<'dashboard' | 'vhq' | 'runtime' | 'welfare' | 'knowledge'>(getInitialView);
  const [apiStatus, setApiStatus] = useState<'checking' | 'online' | 'degraded' | 'offline'>('checking');
  const [latencyMs, setLatencyMs] = useState<number | null>(null);

  // Authentication State
  const [currentUser, setCurrentUser] = useState<User | null>(null);
  const [isLoginOpen, setIsLoginOpen] = useState<boolean>(false);

  const checkConnectivity = useCallback(async () => {
    setApiStatus('checking');
    const start = performance.now();
    try {
      const [sysRes, dbRes] = await Promise.all([
        apiClient.checkHealth(),
        apiClient.checkDatabaseHealth(),
      ]);

      const duration = Math.round(performance.now() - start);
      setLatencyMs(duration);

      if (sysRes.status === 'success' && dbRes.data.connected) {
        setApiStatus('online');
      } else if (sysRes.status === 'success' && !dbRes.data.connected) {
        setApiStatus('degraded');
      } else {
        setApiStatus('offline');
      }
    } catch {
      setLatencyMs(null);
      setApiStatus('offline');
    }
  }, []);

  const restoreSession = useCallback(async () => {
    if (authService.isAuthenticated()) {
      const user = await authService.getMe();
      if (user) {
        setCurrentUser(user);
      }
    }
  }, []);

  useEffect(() => {
    checkConnectivity();
    restoreSession();
    const timer = setInterval(checkConnectivity, 30000);
    return () => clearInterval(timer);
  }, [checkConnectivity, restoreSession]);

  const handleNavigate = (view: 'dashboard' | 'vhq' | 'runtime' | 'welfare' | 'knowledge') => {
    setCurrentView(view);
    window.location.hash = `#${view}`;
  };

  const handleLogout = async () => {
    await authService.logout();
    setCurrentUser(null);
  };

  return (
    <div className="mce-app-root">
      <NavigationHeader
        currentView={currentView}
        onNavigate={handleNavigate}
        apiStatus={apiStatus}
        latencyMs={latencyMs}
        user={currentUser}
        onLoginClick={() => setIsLoginOpen(true)}
        onLogout={handleLogout}
      />

      <div className="mce-app-viewport">
        {isLoginOpen ? (
          <LoginPage
            onSuccess={(user) => {
              setCurrentUser(user);
              setIsLoginOpen(false);
            }}
            onCancel={() => setIsLoginOpen(false)}
          />
        ) : currentView === 'dashboard' ? (
          <DashboardShell
            onRefreshHealth={checkConnectivity}
            apiStatus={apiStatus}
            latencyMs={latencyMs}
            currentUser={currentUser}
            onOpenLogin={() => setIsLoginOpen(true)}
          />
        ) : currentView === 'vhq' ? (
          <VhqView onBackToDashboard={() => handleNavigate('dashboard')} />
        ) : currentView === 'runtime' ? (
          <RuntimeManagementView />
        ) : currentView === 'welfare' ? (
          <WelfareManagementView
            isAuthenticated={!!currentUser}
            onOpenLogin={() => setIsLoginOpen(true)}
          />
        ) : (
          <KnowledgeManagementView
            isAuthenticated={!!currentUser}
            onOpenLogin={() => setIsLoginOpen(true)}
          />
        )}
      </div>
    </div>
  );
}
