import { useEffect, useState } from 'react';
import { getContactSettings, updateContactSettings, ApiError } from '../api.js';
import { useToast } from '../ToastContext.jsx';

export default function Settings() {
  const { push } = useToast();
  const [form, setForm] = useState(null);
  const [error, setError] = useState('');
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const data = await getContactSettings();
        if (!cancelled) setForm(data);
      } catch (err) {
        if (!cancelled) {
          const message = err instanceof ApiError ? err.message : 'Could not load settings.';
          setError(message);
          push(message, 'error');
        }
      }
    })();
    return () => { cancelled = true; };
  }, [push]);

  function setField(key, value) {
    setForm((prev) => ({ ...prev, [key]: value }));
  }

  async function handleSave(e) {
    e.preventDefault();
    setSaving(true);
    try {
      await updateContactSettings(form);
      push('Contact details updated.', 'success');
    } catch (err) {
      push(err instanceof ApiError ? err.message : 'Could not save settings.', 'error');
    } finally {
      setSaving(false);
    }
  }

  return (
    <div>
      <div className="page-head">
        <div><h1>Settings</h1><p>Contact details shown across the site.</p></div>
      </div>

      {error ? (
        <div className="empty-state">
          <h3>Couldn't load settings</h3>
          <p>{error}</p>
          <p style={{ fontSize: 12, marginTop: 6 }}>Editing site-wide contact details requires an Administrator account.</p>
        </div>
      ) : !form ? (
        <div className="center-loading"><div className="spinner" /></div>
      ) : (
        <div className="card" style={{ maxWidth: 560 }}>
          <form onSubmit={handleSave}>
            <div className="field-row">
              <div className="field"><label>Primary Phone</label><input value={form.phone} onChange={(e) => setField('phone', e.target.value)} /></div>
              <div className="field"><label>Secondary Phone</label><input value={form.phone2} onChange={(e) => setField('phone2', e.target.value)} /></div>
            </div>
            <div className="field-row">
              <div className="field"><label>Primary Email</label><input type="email" value={form.email} onChange={(e) => setField('email', e.target.value)} /></div>
              <div className="field"><label>Ops Email</label><input type="email" value={form.email_ops} onChange={(e) => setField('email_ops', e.target.value)} /></div>
            </div>
            <div className="field"><label>WhatsApp Number</label><input value={form.whatsapp} onChange={(e) => setField('whatsapp', e.target.value)} placeholder="e.g. 2348031234567" /></div>
            <div className="field"><label>Office Address</label><textarea value={form.address} onChange={(e) => setField('address', e.target.value)} rows={3} /></div>
            <button className="btn btn-primary" type="submit" disabled={saving}>{saving ? 'Saving…' : 'Save Changes'}</button>
          </form>
        </div>
      )}

      <div className="card" style={{ maxWidth: 560, marginTop: 20 }}>
        <h3 style={{ fontSize: 14, marginBottom: 10 }}>Connected Site</h3>
        <p style={{ fontSize: 13, color: 'var(--steel)', wordBreak: 'break-all' }}>{window.location.origin}</p>
        <p style={{ fontSize: 12, color: 'var(--steel)', marginTop: 10 }}>
          Deeper site settings (theme colors, homepage layout, analytics) live in the WordPress admin
          under Appearance → Customize, and aren't managed from this dashboard.
        </p>
      </div>
    </div>
  );
}
