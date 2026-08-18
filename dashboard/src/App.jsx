import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './AuthContext.jsx';
import { ToastProvider } from './ToastContext.jsx';
import Login from './Login.jsx';
import Register from './Register.jsx';
import ForgotPassword from './ForgotPassword.jsx';
import ResetPassword from './ResetPassword.jsx';
import Layout from './Layout.jsx';
import Overview from './pages/Overview.jsx';
import Inquiries from './pages/Inquiries.jsx';
import Shipments from './pages/Shipments.jsx';
import TradeLanes from './pages/TradeLanes.jsx';
import Departments from './pages/Departments.jsx';
import Jobs from './pages/Jobs.jsx';
import Posts from './pages/Posts.jsx';
import Settings from './pages/Settings.jsx';

function RequireAuth({ children }) {
  const { user, checking } = useAuth();
  if (checking) return <div className="center-loading"><div className="spinner" /></div>;
  if (!user) return <Navigate to="/login" replace />;
  return children;
}

function RedirectIfAuthed({ children }) {
  const { user, checking } = useAuth();
  if (checking) return <div className="center-loading"><div className="spinner" /></div>;
  if (user) return <Navigate to="/" replace />;
  return children;
}

export default function App() {
  return (
    <AuthProvider>
      <ToastProvider>
        {/* Dashboard is served from the root of its own subdomain. */}
        <BrowserRouter>
          <Routes>
            <Route path="/login" element={<RedirectIfAuthed><Login /></RedirectIfAuthed>} />
            <Route path="/register" element={<RedirectIfAuthed><Register /></RedirectIfAuthed>} />
            <Route path="/forgot-password" element={<RedirectIfAuthed><ForgotPassword /></RedirectIfAuthed>} />
            <Route path="/reset-password" element={<RedirectIfAuthed><ResetPassword /></RedirectIfAuthed>} />
            <Route path="/" element={<RequireAuth><Layout /></RequireAuth>}>
              <Route index element={<Overview />} />
              <Route path="inquiries" element={<Inquiries />} />
              <Route path="inquiries/:id" element={<Inquiries />} />
              <Route path="shipments" element={<Shipments />} />
              <Route path="trade-lanes" element={<TradeLanes />} />
              <Route path="departments" element={<Departments />} />
              <Route path="jobs" element={<Jobs />} />
              <Route path="posts" element={<Posts />} />
              <Route path="settings" element={<Settings />} />
            </Route>
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </BrowserRouter>
      </ToastProvider>
    </AuthProvider>
  );
}
