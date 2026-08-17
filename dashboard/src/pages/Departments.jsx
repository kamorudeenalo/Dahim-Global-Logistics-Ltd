import { useEffect, useState, useCallback } from 'react';
import { listItems, createItem, updateItem, deleteItem, ApiError } from '../api.js';
import { useToast } from '../ToastContext.jsx';
import Modal from '../Modal.jsx';
import { IconPlus, IconEdit, IconTrash } from '../icons.jsx';
import { decodeHtmlEntities } from '../utils.js';

const ICONS = ['briefcase', 'handshake', 'target', 'chat', 'search', 'truck', 'globe', 'shield'];

const BLANK = {
  description: '', icon: 'chat', link_text: 'Get in touch →', external_url: '',
  eyebrow: 'Get In Touch', heading: 'How can we help?',
  message_label: 'Your Message', message_placeholder: 'How can we help?', submit_label: 'Send Message',
  show_company: true, show_service: false, show_role_cv: false,
};

export default function Departments() {
  const { push } = useToast();
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState(null);
  const [saving, setSaving] = useState(false);
  const [title, setTitle] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    try {
      setItems(await listItems('departments', { per_page: 100, orderby: 'menu_order', order: 'asc' }));
    } catch (err) {
      push(err instanceof ApiError ? err.message : 'Could not load departments.', 'error');
    } finally {
      setLoading(false);
    }
  }, [push]);

  useEffect(() => { load(); }, [load]);

  function openNew() { setTitle(''); setEditing({ id: null, form: { ...BLANK } }); }
  function openEdit(item) {
    const form = { ...BLANK };
    Object.keys(form).forEach((k) => {
      const raw = item.meta?.[`_dahim_dept_${k}`];
      if (k.startsWith('show_')) form[k] = raw !== '0';
      else form[k] = raw || form[k];
    });
    setTitle(decodeHtmlEntities(item.title?.rendered || ''));
    setEditing({ id: item.id, form });
  }

  async function handleSave(e) {
    e.preventDefault();
    setSaving(true);
    const meta = {};
    Object.entries(editing.form).forEach(([k, v]) => {
      meta[`_dahim_dept_${k}`] = typeof v === 'boolean' ? (v ? '1' : '0') : v;
    });
    try {
      if (editing.id) {
        await updateItem('departments', editing.id, { title, meta });
        push('Department updated.', 'success');
      } else {
        await createItem('departments', { title, status: 'publish', meta });
        push('Department created.', 'success');
      }
      setEditing(null);
      load();
    } catch (err) {
      push(err instanceof ApiError ? err.message : 'Could not save department.', 'error');
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete(id) {
    if (!window.confirm('Delete this department? Its desk card and form option will disappear from the site.')) return;
    try {
      await deleteItem('departments', id);
      push('Department deleted.', 'success');
      load();
    } catch (err) {
      push(err instanceof ApiError ? err.message : 'Could not delete department.', 'error');
    }
  }

  function setField(key, value) {
    setEditing((prev) => ({ ...prev, form: { ...prev.form, [key]: value } }));
  }

  return (
    <div>
      <div className="page-head">
        <div><h1>Departments</h1><p>The "reach the right desk" cards and form options on Contact Us.</p></div>
        <button className="btn btn-primary" onClick={openNew}><IconPlus /> New Department</button>
      </div>

      {loading ? (
        <div className="center-loading"><div className="spinner" /></div>
      ) : (
        <div className="row-cards" style={{ display: 'flex' }}>
          {items.map((item) => (
            <div key={item.id} className="row-card">
              <div className="row-card-top">
                <div className="row-card-title">{decodeHtmlEntities(item.title?.rendered)}</div>
                <div>
                  <button className="icon-btn" onClick={() => openEdit(item)}><IconEdit /></button>
                  <button className="icon-btn" onClick={() => handleDelete(item.id)}><IconTrash /></button>
                </div>
              </div>
              <div style={{ fontSize: 12.5, color: 'var(--steel)', display: 'flex', flexDirection: 'column', gap: 4 }}>
                <span>{decodeHtmlEntities(item.meta?._dahim_dept_description) || 'No description'}</span>
                <span>{item.meta?._dahim_dept_external_url ? `Links to ${item.meta._dahim_dept_external_url}` : 'Opens contact form'}</span>
              </div>
            </div>
          ))}
        </div>
      )}

      {editing && (
        <Modal title={editing.id ? 'Edit Department' : 'New Department'} onClose={() => setEditing(null)} wide>
          <form onSubmit={handleSave}>
            <div className="field"><label>Name</label><input value={title} onChange={(e) => setTitle(e.target.value)} placeholder="e.g. Sales & Shipping Quotes" required /></div>
            <div className="field"><label>Description (shown on the desk card)</label><textarea value={editing.form.description} onChange={(e) => setField('description', e.target.value)} /></div>
            <div className="field-row">
              <div className="field"><label>Icon</label>
                <select value={editing.form.icon} onChange={(e) => setField('icon', e.target.value)}>
                  {ICONS.map((i) => <option key={i} value={i}>{i[0].toUpperCase() + i.slice(1)}</option>)}
                </select>
              </div>
              <div className="field"><label>Card Link Text</label><input value={editing.form.link_text} onChange={(e) => setField('link_text', e.target.value)} /></div>
            </div>
            <div className="field">
              <label>External Link (optional)</label>
              <input value={editing.form.external_url} onChange={(e) => setField('external_url', e.target.value)} placeholder="e.g. /track/ — leave blank to open the contact form instead" />
              <div className="hint">If set, this card links straight there instead of the contact form, and won't appear as a form option.</div>
            </div>

            <hr style={{ margin: '18px 0', border: 'none', borderTop: '1px solid var(--line)' }} />
            <p style={{ fontWeight: 700, fontSize: 13.5, marginBottom: 12 }}>Contact form behavior for this department</p>

            <div className="field-row">
              <div className="field"><label>Form Eyebrow</label><input value={editing.form.eyebrow} onChange={(e) => setField('eyebrow', e.target.value)} /></div>
              <div className="field"><label>Form Heading</label><input value={editing.form.heading} onChange={(e) => setField('heading', e.target.value)} /></div>
            </div>
            <div className="field-row">
              <div className="field"><label>Message Field Label</label><input value={editing.form.message_label} onChange={(e) => setField('message_label', e.target.value)} /></div>
              <div className="field"><label>Message Placeholder</label><input value={editing.form.message_placeholder} onChange={(e) => setField('message_placeholder', e.target.value)} /></div>
            </div>
            <div className="field"><label>Submit Button Text</label><input value={editing.form.submit_label} onChange={(e) => setField('submit_label', e.target.value)} /></div>

            <div className="checkbox-field" style={{ marginBottom: 10 }}>
              <input type="checkbox" id="show_company" checked={editing.form.show_company} onChange={(e) => setField('show_company', e.target.checked)} />
              <label htmlFor="show_company">Show the Company field</label>
            </div>
            <div className="checkbox-field" style={{ marginBottom: 10 }}>
              <input type="checkbox" id="show_service" checked={editing.form.show_service} onChange={(e) => setField('show_service', e.target.checked)} />
              <label htmlFor="show_service">Show Service Needed dropdown</label>
            </div>
            <div className="checkbox-field" style={{ marginBottom: 18 }}>
              <input type="checkbox" id="show_role_cv" checked={editing.form.show_role_cv} onChange={(e) => setField('show_role_cv', e.target.checked)} />
              <label htmlFor="show_role_cv">Show Role Applying For + CV/Portfolio Link fields</label>
            </div>

            <button className="btn btn-primary" type="submit" disabled={saving} style={{ width: '100%', justifyContent: 'center' }}>
              {saving ? 'Saving…' : editing.id ? 'Save Changes' : 'Create Department'}
            </button>
          </form>
        </Modal>
      )}
    </div>
  );
}
