import { useEffect, useState, useCallback } from 'react';
import { listItems, createItem, updateItem, deleteItem, ApiError } from '../api.js';
import { useToast } from '../ToastContext.jsx';
import Modal from '../Modal.jsx';
import { IconPlus, IconEdit, IconTrash } from '../icons.jsx';

const BLANK = { origin: '', destination: '', mode: '', transit: '' };

export default function TradeLanes() {
  const { push } = useToast();
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState(null);
  const [saving, setSaving] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      setItems(await listItems('trade-lanes', { per_page: 100 }));
    } catch (err) {
      push(err instanceof ApiError ? err.message : 'Could not load trade lanes.', 'error');
    } finally {
      setLoading(false);
    }
  }, [push]);

  useEffect(() => { load(); }, [load]);

  function openNew() { setEditing({ id: null, form: { ...BLANK } }); }
  function openEdit(item) {
    const form = { ...BLANK };
    Object.keys(form).forEach((k) => { form[k] = item.meta?.[`_dahim_lane_${k}`] || ''; });
    setEditing({ id: item.id, form });
  }

  async function handleSave(e) {
    e.preventDefault();
    setSaving(true);
    const meta = {};
    Object.entries(editing.form).forEach(([k, v]) => { meta[`_dahim_lane_${k}`] = v; });
    const title = `${editing.form.origin} → ${editing.form.destination}`;
    try {
      if (editing.id) {
        await updateItem('trade-lanes', editing.id, { title, meta });
        push('Trade lane updated.', 'success');
      } else {
        await createItem('trade-lanes', { title, status: 'publish', meta });
        push('Trade lane created.', 'success');
      }
      setEditing(null);
      load();
    } catch (err) {
      push(err instanceof ApiError ? err.message : 'Could not save trade lane.', 'error');
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete(id) {
    if (!window.confirm('Delete this trade lane?')) return;
    try {
      await deleteItem('trade-lanes', id);
      push('Trade lane deleted.', 'success');
      load();
    } catch (err) {
      push(err instanceof ApiError ? err.message : 'Could not delete trade lane.', 'error');
    }
  }

  function setField(key, value) {
    setEditing((prev) => ({ ...prev, form: { ...prev.form, [key]: value } }));
  }

  return (
    <div>
      <div className="page-head">
        <div><h1>Trade Lanes</h1><p>Key shipping routes shown on the Services page.</p></div>
        <button className="btn btn-primary" onClick={openNew}><IconPlus /> New Trade Lane</button>
      </div>

      {loading ? (
        <div className="center-loading"><div className="spinner" /></div>
      ) : items.length === 0 ? (
        <div className="empty-state"><h3>No trade lanes yet</h3></div>
      ) : (
        <>
          <div className="table-wrap">
            <table className="data-table">
              <thead><tr><th>Route</th><th>Mode</th><th>Transit Time</th><th></th></tr></thead>
              <tbody>
                {items.map((item) => (
                  <tr key={item.id}>
                    <td>{item.meta?._dahim_lane_origin} → {item.meta?._dahim_lane_destination}</td>
                    <td>{item.meta?._dahim_lane_mode || '—'}</td>
                    <td>{item.meta?._dahim_lane_transit || '—'}</td>
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
                  <div className="row-card-title">{item.meta?._dahim_lane_origin} → {item.meta?._dahim_lane_destination}</div>
                  <div>
                    <button className="icon-btn" onClick={() => openEdit(item)}><IconEdit /></button>
                    <button className="icon-btn" onClick={() => handleDelete(item.id)}><IconTrash /></button>
                  </div>
                </div>
                <div className="row-card-meta"><span>{item.meta?._dahim_lane_mode}</span><span>{item.meta?._dahim_lane_transit}</span></div>
              </div>
            ))}
          </div>
        </>
      )}

      {editing && (
        <Modal title={editing.id ? 'Edit Trade Lane' : 'New Trade Lane'} onClose={() => setEditing(null)}>
          <form onSubmit={handleSave}>
            <div className="field-row">
              <div className="field"><label>Origin</label><input value={editing.form.origin} onChange={(e) => setField('origin', e.target.value)} placeholder="e.g. China" required /></div>
              <div className="field"><label>Destination</label><input value={editing.form.destination} onChange={(e) => setField('destination', e.target.value)} placeholder="e.g. Apapa Port, Lagos" required /></div>
            </div>
            <div className="field"><label>Mode</label><input value={editing.form.mode} onChange={(e) => setField('mode', e.target.value)} placeholder="e.g. Ocean FCL/LCL" /></div>
            <div className="field"><label>Transit Time</label><input value={editing.form.transit} onChange={(e) => setField('transit', e.target.value)} placeholder="e.g. 28–35 days" /></div>
            <button className="btn btn-primary" type="submit" disabled={saving} style={{ width: '100%', justifyContent: 'center' }}>
              {saving ? 'Saving…' : editing.id ? 'Save Changes' : 'Create Trade Lane'}
            </button>
          </form>
        </Modal>
      )}
    </div>
  );
}
