import { createContext, useContext, useState, useCallback, useEffect } from 'react';
import { login as apiLogin, logout as apiLogout, restoreSession } from './api.js';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  // Starts true: on first load we don't yet know whether the browser's
  // WordPress cookie (from a previous visit) is still valid — restoreSession()
  // below settles that. Nothing should redirect to /login until it resolves,
  // or a returning, still-signed-in visitor would get bounced unnecessarily.
  const [checking, setChecking] = useState(true);

  useEffect(() => {
    let cancelled = false;
    restoreSession()
      .then((u) => { if (!cancelled) setUser(u); })
      .catch(() => { if (!cancelled) setUser(null); })
      .finally(() => { if (!cancelled) setChecking(false); });
    return () => { cancelled = true; };
  }, []);

  const login = useCallback(async (username, password) => {
    // Usernames cannot meaningfully start or end with whitespace, but
    // passwords can. Preserve every password character exactly as entered.
    const u = await apiLogin(username.trim(), password);
    setUser(u);
    return u;
  }, []);

  const logout = useCallback(async () => {
    await apiLogout();
    setUser(null);
  }, []);

  // Fired by api.js the moment any request comes back 401 — the session is
  // genuinely gone (expired, or ended elsewhere), so clear it here too and
  // let RequireAuth send the person to /login, instead of leaving them
  // stuck on a page that still looks signed in while everything fails.
  useEffect(() => {
    function handleUnauthorized() {
      setUser(null);
    }
    window.addEventListener('dahim:unauthorized', handleUnauthorized);
    return () => window.removeEventListener('dahim:unauthorized', handleUnauthorized);
  }, []);

  return (
    <AuthContext.Provider value={{ user, checking, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used inside AuthProvider');
  return ctx;
}
