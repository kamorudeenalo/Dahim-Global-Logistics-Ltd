import { useState } from 'react';
import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import { useAuth } from './AuthContext.jsx';
import Logo from './Logo.jsx';
import { IconHome, IconInbox, IconTruck, IconRoute, IconUsers, IconBriefcase, IconFile, IconSettings, IconLogout, IconMenu, IconClose } from './icons.jsx';

const NAV_ITEMS = [
  { to: '/', label: 'Overview', icon: IconHome, end: true },
  { to: '/inquiries', label: 'Inquiries', icon: IconInbox },
  { to: '/shipments', label: 'Shipments', icon: IconTruck },
  { to: '/trade-lanes', label: 'Trade Lanes', icon: IconRoute },
  { to: '/departments', label: 'Departments', icon: IconUsers },
  { to: '/jobs', label: 'Jobs', icon: IconBriefcase },
  { to: '/posts', label: 'Insights', icon: IconFile },
  { to: '/settings', label: 'Settings', icon: IconSettings },
];

// Mobile bottom bar keeps the highest-traffic sections; Jobs remains available in the expanded menu.
const MOBILE_TABS = NAV_ITEMS.filter((i) =>
  ['/', '/inquiries', '/shipments', '/posts', '/settings'].includes(i.to)
);

export default function Layout() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  async function handleLogout() {
    await logout();
    navigate('/login');
  }

  return (
    <div className="app-shell">
      <aside className="app-sidebar">
        <div className="brand"><Logo height={28} /></div>
        <nav className="app-nav">
          {NAV_ITEMS.map((item) => (
            <NavLink key={item.to} to={item.to} end={item.end} className={({ isActive }) => (isActive ? 'active' : '')}>
              <item.icon />
              {item.label}
            </NavLink>
          ))}
        </nav>
        <div className="sidebar-footer">
          <div className="sidebar-user">{user?.name || user?.username}</div>
          <button className="logout-btn" onClick={handleLogout}>Sign Out</button>
        </div>
      </aside>

      <div className="app-main">
        <div className="app-topbar">
          <Logo height={24} />
          <button className="icon-btn" onClick={() => setMobileMenuOpen(true)} aria-label="Open menu">
            <IconMenu />
          </button>
        </div>

        {mobileMenuOpen && (
          <div className="modal-overlay" onClick={() => setMobileMenuOpen(false)}>
            <div className="modal" style={{ maxWidth: 300, marginLeft: 'auto', marginRight: 0, height: '100vh', maxHeight: '100vh', borderRadius: 0 }} onClick={(e) => e.stopPropagation()}>
              <div className="modal-header">
                <strong>Menu</strong>
                <button className="icon-btn" onClick={() => setMobileMenuOpen(false)}><IconClose /></button>
              </div>
              <div className="modal-body">
                <nav className="app-nav" style={{ padding: 0 }}>
                  {NAV_ITEMS.map((item) => (
                    <NavLink key={item.to} to={item.to} end={item.end} onClick={() => setMobileMenuOpen(false)} className={({ isActive }) => (isActive ? 'active' : '')} style={{ color: 'var(--graphite)' }}>
                      <item.icon />
                      {item.label}
                    </NavLink>
                  ))}
                </nav>
                <div style={{ marginTop: 20, paddingTop: 16, borderTop: '1px solid var(--line)' }}>
                  <div style={{ fontSize: 12.5, color: 'var(--steel)', marginBottom: 10 }}>{user?.name || user?.username}</div>
                  <button className="btn btn-outline" style={{ width: '100%', justifyContent: 'center' }} onClick={handleLogout}>
                    <IconLogout /> Sign Out
                  </button>
                </div>
              </div>
            </div>
          </div>
        )}

        <main className="app-content">
          <Outlet />
        </main>
      </div>

      <nav className="mobile-tabbar">
        {MOBILE_TABS.map((item) => (
          <NavLink key={item.to} to={item.to} end={item.end} className={({ isActive }) => (isActive ? 'active' : '')}>
            <item.icon />
            {item.label}
          </NavLink>
        ))}
      </nav>
    </div>
  );
}
