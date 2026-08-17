import { useState } from 'react';
import { Link } from 'react-router-dom';
import { registerAccount, ApiError } from './api.js';
import PasswordField from './PasswordField.jsx';
import Logo from './Logo.jsx';
import { IconArrowLeft } from './icons.jsx';

export default function Register() {
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [done, setDone] = useState(false);

  async function handleSubmit(e) {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      await registerAccount(username.trim(), email.trim(), password);
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

        {done ? (
          <>
            <p className="sub">Account created</p>
            <p style={{ fontSize: 13.5, color: 'var(--steel)', marginBottom: 20 }}>
              Your account has been created and is <strong>pending approval</strong>. An administrator
              needs to grant it access before you can sign in — you'll be notified once that's done.
            </p>
            <Link to="/login" className="btn btn-primary" style={{ width: '100%', justifyContent: 'center', textDecoration: 'none' }}>
              Back to Sign In
            </Link>
          </>
        ) : (
          <>
            <p className="sub">Create a dashboard account</p>
            <p style={{ fontSize: 13, color: 'var(--steel)', marginBottom: 20 }}>
              New accounts need to be approved by an administrator before they can access anything.
            </p>

            {error && <div className="login-error">{error}</div>}

            <form onSubmit={handleSubmit}>
              <div className="field">
                <label>Username</label>
                <input type="text" value={username} onChange={(e) => setUsername(e.target.value)} autoComplete="username" required />
              </div>
              <div className="field">
                <label>Email</label>
                <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} autoComplete="email" required />
              </div>
              <PasswordField
                label="Password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="At least 8 characters"
                autoComplete="new-password"
                required
              />
              <button className="btn btn-primary" type="submit" disabled={loading}>
                {loading ? 'Creating account…' : 'Create Account'}
              </button>
            </form>
          </>
        )}

        {!done && (
          <div style={{ marginTop: 18 }}>
            <Link to="/login" style={{ color: 'var(--amber-deep)', fontSize: 13, display: 'inline-flex', alignItems: 'center', gap: 6 }}>
              <IconArrowLeft width={14} height={14} /> Back to Sign In
            </Link>
          </div>
        )}
      </div>
    </div>
  );
}
