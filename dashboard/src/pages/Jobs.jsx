import { useEffect, useState, useCallback } from 'react';
import { listItems, getItem, createItem, updateItem, deleteItem, ApiError } from '../api.js';
import { useToast } from '../ToastContext.jsx';
import Modal from '../Modal.jsx';
import { IconPlus, IconEdit, IconTrash } from '../icons.jsx';
import { decodeHtmlEntities } from '../utils.js';

const BLANK = { location: '', type: 'Full-time', deadline: '', status: 'open' };

export default function Jobs() {
  const { push } = useToast();
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState(null);
  const [saving, setSaving] = useState(false);
  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    try {
      setItems(await listItems('jobs', { per_page: 100 }));
    } catch (err) {
      push(err instanceof ApiError ? err.message : 'Could not load jobs.', 'error');
    } finally {
      setLoading(false);
    }
  }, [push]);

  useEffect(() => { load(); }, [load]);

  function openNew() { setTitle(''); setContent(''); setEditing({ id: null, form: { ...BLANK } }); }
  async function openEdit(item) {
    const full = await getItem('jobs', item.id).catch(() => item); // fall back to list data if the fresh fetch fails
    const form = { ...BLANK };
    Object.keys(form).forEach((k) => { form[k] = full.meta?.[`_dahim_job_${k}`] || form[k]; });
    setTitle(decodeHtmlEntities(full.title?.raw ?? full.title?.rendered ?? ''));
    setContent(full.content?.raw ?? stripHtml(full.content?.rendered || ''));
    setEditing({ id: item.id, form });
  }

  async function handleSave(e) {
    e.preventDefault();
    setSaving(true);
    const meta = {};
    Object.entries(editing.form).forEach(([k, v]) => { meta[`_dahim_job_${k}`] = v; });
    try {
      if (editing.id) {
        await updateItem('jobs', editing.id, { title, content, meta });
        push('Job updated.', 'success');
      } else {
        await createItem('jobs', { title, content, status: 'publish', meta });
        push('Job posted.', 'success');
      }
      setEditing(null);
      load();
    } catch (err) {
      push(err instanceof ApiError ? err.message : 'Could not save job.', 'error');
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete(id) {
    if (!window.confirm('Delete this job posting?')) return;
    try {
      await deleteItem('jobs', id);
      push('Job deleted.', 'success');
      load();
    } catch (err) {
      push(err instanceof ApiError ? err.message : 'Could not delete job.', 'error');
    }
  }

  function setField(key, value) {
    setEditing((prev) => ({ ...prev, form: { ...prev.form, [key]: value } }));
  }

  return (
    <div>
      <div className="page-head">
        <div><h1>Jobs</h1><p>Open roles shown on the public Careers page.</p></div>
        <button className="btn btn-primary" onClick={openNew}><IconPlus /> Post a Job</button>
      </div>

      {loading ? (
        <div className="center-loading"><div className="spinner" /></div>
      ) : items.length === 0 ? (
        <div className="empty-state"><h3>No jobs posted yet</h3></div>
      ) : (
        <>
          <div className="table-wrap">
            <table className="data-table">
              <thead><tr><th>Role</th><th>Location</th><th>Type</th><th>Deadline</th><th>Status</th><th></th></tr></thead>
              <tbody>
                {items.map((item) => (
                  <tr key={item.id}>
                    <td>{decodeHtmlEntities(item.title?.rendered)}</td>
                    <td>{item.meta?._dahim_job_location || '—'}</td>
                    <td>{item.meta?._dahim_job_type || '—'}</td>
                    <td className="mono">{item.meta?._dahim_job_deadline || '—'}</td>
                    <td>
                      <span className="status-pill" style={{ borderColor: item.meta?._dahim_job_status === 'closed' ? '#8A8F98' : '#008751', color: item.meta?._dahim_job_status === 'closed' ? '#8A8F98' : '#008751' }}>
                        {item.meta?._dahim_job_status === 'closed' ? 'Closed' : 'Open'}
                      </span>
                    </td>
                    <td style={{ textAlign: 'right', whiteSpace: 'nowrap' }}>
                      <button className="icon-btn" onClick={() => openEdit(item)}><IconEdit /></button>
                      <button className="icon-btn" onClick={() => handleDelete(item.id)}><IconTrash /></button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <div className="row-cards">
            {items.map((item) => (
              <div key={item.id} className="row-card">
                <div className="row-card-top">
                  <div className="row-card-title">{decodeHtmlEntities(item.title?.rendered)}</div>
                  <div>
                    <button className="icon-btn" onClick={() => openEdit(item)}><IconEdit /></button>
                    <button className="icon-btn" onClick={() => handleDelete(item.id)}><IconTrash /></button>
                  </div>
                </div>
                <div className="row-card-meta">
                  <span>{item.meta?._dahim_job_location}</span>
                  <span>{item.meta?._dahim_job_type}</span>
                  <span>{item.meta?._dahim_job_status === 'closed' ? 'Closed' : 'Open'}</span>
                </div>
              </div>
            ))}
          </div>
        </>
      )}

      {editing && (
        <Modal title={editing.id ? 'Edit Job' : 'Post a Job'} onClose={() => setEditing(null)} wide>
          <form onSubmit={handleSave}>
            <div className="field"><label>Role Title</label><input value={title} onChange={(e) => setTitle(e.target.value)} placeholder="e.g. Customs Documentation Officer" required /></div>
            <div className="field-row">
              <div className="field"><label>Location</label><input value={editing.form.location} onChange={(e) => setField('location', e.target.value)} placeholder="e.g. Apapa, Lagos" /></div>
              <div className="field"><label>Employment Type</label>
                <select value={editing.form.type} onChange={(e) => setField('type', e.target.value)}>
                  {['Full-time', 'Part-time', 'Contract', 'Internship'].map((t) => <option key={t} value={t}>{t}</option>)}
                </select>
              </div>
            </div>
            <div className="field-row">
              <div className="field"><label>Application Deadline</label><input type="date" value={editing.form.deadline} onChange={(e) => setField('deadline', e.target.value)} /></div>
              <div className="field"><label>Status</label>
                <select value={editing.form.status} onChange={(e) => setField('status', e.target.value)}>
                  <option value="open">Open — visible on Careers page</option>
                  <option value="closed">Closed — hidden from Careers page</option>
                </select>
              </div>
            </div>
            <div className="field"><label>Full Description</label><textarea value={content} onChange={(e) => setContent(e.target.value)} rows={8} placeholder="Responsibilities, requirements, and anything else applicants should know" /></div>

            <button className="btn btn-primary" type="submit" disabled={saving} style={{ width: '100%', justifyContent: 'center' }}>
              {saving ? 'Saving…' : editing.id ? 'Save Changes' : 'Post Job'}
            </button>
          </form>
        </Modal>
      )}
    </div>
  );
}

function stripHtml(html) {
  const div = document.createElement('div');
  div.innerHTML = html;
  return div.textContent || '';
}
