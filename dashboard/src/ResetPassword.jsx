import { useState } from 'react';
import { Link, useSearchParams, useNavigate } from 'react-router-dom';
import { resetPassword, ApiError } from './api.js';
import PasswordField from './PasswordField.jsx';
import Logo from './Logo.jsx';

export default function ResetPassword() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const username = searchParams.get('username') || '';
  const key = searchParams.get('key') || '';

  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [done, setDone] = useState(false);

  const linkMissing = !username || !key;

  async function handleSubmit(e) {
    e.preventDefault();
    setError('');
    if (password.length < 8) {
      setError('Password must be at least 8 characters.');
      return;
    }
    if (password !== confirm) {
      setError("Passwords don't match.");
      return;
    }
    setLoading(true);
    try {
      await resetPassword(username, key, password);
      setDone(true);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Something went wrong. Try again.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="login-screen">
      <div className="login-card">
        <div style={{ marginBottom: 18 }}><Logo height={34} /></div>

        {linkMissing ? (
          <>
            <p className="sub">Invalid reset link</p>
            <p style={{ fontSize: 13.5, color: 'var(--steel)', marginBottom: 20 }}>
              This link is missing some information — open the link from your email again, or request a new one.
            </p>
            <Link to="/forgot-password" className="btn btn-primary" style={{ width: '100%', justifyContent: 'center', textDecoration: 'none' }}>
              Request a New Link
            </Link>
          </>
        ) : done ? (
          <>
            <p className="sub">Password updated</p>
            <p style={{ fontSize: 13.5, color: 'var(--steel)', marginBottom: 20 }}>
              Your password has been changed. Sign in with your new password whenever you're ready.
            </p>
            <button className="btn btn-primary" style={{ width: '100%', justifyContent: 'center' }} onClick={() => navigate('/login')}>
              Go to Sign In
            </button>
          </>
        ) : (
          <>
            <p className="sub">Set a new password</p>
            <p style={{ fontSize: 13, color: 'var(--steel)', marginBottom: 20 }}>
              For <strong>{username}</strong>. This link expires 24 hours after it was sent.
            </p>

            {error && <div className="login-error">{error}</div>}

            <form onSubmit={handleSubmit}>
              <PasswordField
                label="New Password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="At least 8 characters"
                autoComplete="new-password"
                required
              />
              <PasswordField
                label="Confirm New Password"
                value={confirm}
                onChange={(e) => setConfirm(e.target.value)}
                autoComplete="new-password"
                required
              />
              <button className="btn btn-primary" type="submit" disabled={loading}>
                {loading ? 'Saving…' : 'Set New Password'}
              </button>
            </form>
          </>
        )}
      </div>
    </div>
  );
}
