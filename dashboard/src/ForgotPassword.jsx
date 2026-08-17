import { useState } from 'react';
import { Link } from 'react-router-dom';
import { forgotPassword, ApiError } from './api.js';
import Logo from './Logo.jsx';
import { IconArrowLeft } from './icons.jsx';

export default function ForgotPassword() {
  const [identifier, setIdentifier] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [sent, setSent] = useState(false);

  async function handleSubmit(e) {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      await forgotPassword(identifier.trim());
      setSent(true);
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

        {sent ? (
          <>
            <p className="sub">Check your email</p>
            <p style={{ fontSize: 13.5, color: 'var(--steel)', marginBottom: 20 }}>
              If an account matches <strong>{identifier}</strong>, a password reset link is on its way.
              It expires in 24 hours — request a new one below if it doesn't arrive.
            </p>
            <Link to="/login" className="btn btn-primary" style={{ width: '100%', justifyContent: 'center', textDecoration: 'none' }}>
              Back to Sign In
            </Link>
          </>
        ) : (
          <>
            <p className="sub">Reset your password</p>
            <p style={{ fontSize: 13, color: 'var(--steel)', marginBottom: 20 }}>
              Enter your username or email and we'll send you a link to set a new password.
            </p>

            {error && <div className="login-error">{error}</div>}

            <form onSubmit={handleSubmit}>
              <div className="field">
                <label>Username or Email</label>
                <input type="text" value={identifier} onChange={(e) => setIdentifier(e.target.value)} autoComplete="username" required />
              </div>
              <button className="btn btn-primary" type="submit" disabled={loading}>
                {loading ? 'Sending…' : 'Send Reset Link'}
              </button>
            </form>
          </>
        )}

        {!sent && (
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
