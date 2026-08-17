import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from './AuthContext.jsx';
import PasswordField from './PasswordField.jsx';
import Logo from './Logo.jsx';

export default function Login() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const [username, setUsername] = useState('');
  const [appPassword, setAppPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e) {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      await login(username, appPassword);
      navigate('/');
    } catch (err) {
      setError(err.message || 'Sign in failed. Check your details and try again.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="login-screen">
      <div className="login-card">
        <div style={{ marginBottom: 18 }}><Logo height={34} /></div>
        <p className="sub">Sign in to the dashboard</p>

        {error && <div className="login-error">{error}</div>}

        <form onSubmit={handleSubmit}>
          <div className="field">
            <label>Username</label>
            <input
              type="text"
              placeholder="Your username"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              autoComplete="username"
              required
            />
          </div>
          <PasswordField
            label="Password"
            value={appPassword}
            onChange={(e) => setAppPassword(e.target.value)}
            placeholder="Your password"
            autoComplete="current-password"
            required
          />
          <button className="btn btn-primary" type="submit" disabled={loading}>
            {loading ? 'Signing in…' : 'Sign In'}
          </button>
        </form>

        <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 18, fontSize: 13 }}>
          <Link to="/forgot-password" style={{ color: 'var(--amber-deep)' }}>Forgot password?</Link>
          <Link to="/register" style={{ color: 'var(--amber-deep)' }}>Create an account</Link>
        </div>
      </div>
    </div>
  );
}
