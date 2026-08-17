import { Component } from 'react';

export default class ErrorBoundary extends Component {
  constructor(props) {
    super(props);
    this.state = { error: null };
  }

  static getDerivedStateFromError(error) {
    return { error };
  }

  componentDidCatch(error, info) {
    // Surfaced in the browser console for debugging — the dashboard itself
    // never sends this anywhere.
    console.error('Dashboard crashed:', error, info);
  }

  render() {
    if (this.state.error) {
      return (
        <div className="login-screen">
          <div className="login-card">
            <div className="brand"><span className="dot" /> DAHIM</div>
            <p className="sub">Something went wrong</p>
            <div className="login-error">
              {this.state.error.message || 'An unexpected error occurred.'}
            </div>
            <button className="btn btn-primary" style={{ width: '100%', justifyContent: 'center' }} onClick={() => window.location.assign('/dashboard/')}>
              Reload Dashboard
            </button>
          </div>
        </div>
      );
    }
    return this.props.children;
  }
}
