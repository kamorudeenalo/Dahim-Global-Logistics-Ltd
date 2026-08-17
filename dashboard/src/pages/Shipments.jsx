import { useEffect, useState, useCallback } from 'react';
import { listItems, createItem, updateItem, deleteItem, ApiError } from '../api.js';
import { useToast } from '../ToastContext.jsx';
import Modal from '../Modal.jsx';
import { IconPlus, IconEdit, IconTrash } from '../icons.jsx';

const STAGES = { 1: 'Booked', 2: 'Cleared / Picked Up', 3: 'In Transit', 4: 'Out for Delivery', 5: 'Delivered' };

const BLANK = {
  title: '',
  owner_name: '', owner_email: '', owner_phone: '',
  consignee_name: '', consignee_phone: '',
  origin: '', destination: '', current_location: '',
  package_description: '', weight: '', pieces: '', dimensions: '', declared_value: '',
  service_type: '', carrier: '', date_booked: '', estimated_delivery: '', special_instructions: '',
  stage: '1',
};

export default function Shipments() {
  const { push } = useToast();
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState(null);
  const [saving, setSaving] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      setItems(await listItems('shipments', { per_page: 100 }));
    } catch (err) {
      push(err instanceof ApiError ? err.message : 'Could not load shipments.', 'error');
    } finally {
      setLoading(false);
    }
  }, [push]);

  useEffect(() => { load(); }, [load]);

  function openNew() { setEditing({ id: null, form: { ...BLANK } }); }

  function openEdit(item) {
    const form = { ...BLANK };
    form.title = item.title?.raw ?? item.title?.rendered ?? '';
    Object.keys(form).filter((k) => k !== 'title').forEach((k) => {
      form[k] = item.meta?.[`_dahim_ship_${k}`] || form[k];
    });
    setEditing({ id: item.id, form, tracking: item.meta?._dahim_tracking_number });
  }

  async function handleSave(e) {
    e.preventDefault();
    setSaving(true);
    const meta = {};
    Object.entries(editing.form).forEach(([k, v]) => {
      if (k !== 'title') meta[`_dahim_ship_${k}`] = v;
    });
    try {
      const title = editing.form.title.trim() || editing.form.owner_name.trim() || 'New shipment';
      const payload = { title, meta };
      if (editing.id) {
        await updateItem('shipments', editing.id, payload);
        push('Shipment updated.', 'success');
      } else {
        await createItem('shipments', { ...payload, status: 'publish' });
        push('Shipment created — tracking number generated automatically.', 'success');
      }
      setEditing(null);
      load();
    } catch (err) {
      push(err instanceof ApiError ? err.message : 'Could not save shipment.', 'error');
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete(id) {
    if (!window.confirm('Delete this shipment permanently? This cannot be undone.')) return;
    try {
      await deleteItem('shipments', id);
      push('Shipment deleted.', 'success');
      load();
    } catch (err) {
      push(err instanceof ApiError ? err.message : 'Could not delete shipment.', 'error');
    }
  }

  function setField(key, value) {
    setEditing((prev) => ({ ...prev, form: { ...prev.form, [key]: value } }));
  }

  return (
    <div>
      <div className="page-head">
        <div>
          <h1>Shipments</h1>
          <p>Every shipment tracked on the public site.</p>
        </div>
        <button className="btn btn-primary" onClick={openNew}><IconPlus /> New Shipment</button>
      </div>

      {loading ? (
        <div className="center-loading"><div className="spinner" /></div>
      ) : items.length === 0 ? (
        <div className="empty-state"><h3>No shipments yet</h3><p>Create one to get started.</p></div>
      ) : (
        <>
          <div className="table-wrap">
            <table className="data-table">
              <thead><tr><th>Title</th><th>Tracking No.</th><th>Owner</th><th>Route</th><th>Status</th><th></th></tr></thead>
              <tbody>
                {items.map((item) => (
                  <tr key={item.id}>
                    <td>{item.title?.rendered || '—'}</td>
                    <td className="mono">{item.meta?._dahim_tracking_number || '—'}</td>
                    <td>{item.meta?._dahim_ship_owner_name || '—'}</td>
                    <td>{item.meta?._dahim_ship_origin || '—'} → {item.meta?._dahim_ship_destination || '—'}</td>
                    <td><span className="status-pill" style={{ borderColor: 'var(--amber)', color: 'var(--amber-deep)' }}>{STAGES[item.meta?._dahim_ship_stage] || 'Booked'}</span></td>
                    <td style={{ textAlign: 'right', whiteSpace: 'nowrap' }}>
                      <button className="icon-btn" onClick={() => openEdit(item)} aria-label="Edit"><IconEdit /></button>
                      <button className="icon-btn" onClick={() => handleDelete(item.id)} aria-label="Delete"><IconTrash /></button>
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
                  <div className="row-card-title">{item.title?.rendered || item.meta?._dahim_ship_owner_name || '—'}</div>
                  <div>
                    <button className="icon-btn" onClick={() => openEdit(item)}><IconEdit /></button>
                    <button className="icon-btn" onClick={() => handleDelete(item.id)}><IconTrash /></button>
                  </div>
                </div>
                <div className="row-card-meta">
                  <span className="mono">{item.meta?._dahim_tracking_number}</span>
                  <span>{item.meta?._dahim_ship_origin} → {item.meta?._dahim_ship_destination}</span>
                  <span>{STAGES[item.meta?._dahim_ship_stage] || 'Booked'}</span>
                </div>
              </div>
            ))}
          </div>
        </>
      )}

      {editing && (
        <Modal title={editing.id ? `Edit Shipment${editing.tracking ? ` — ${editing.tracking}` : ''}` : 'New Shipment'} onClose={() => setEditing(null)} wide>
          <form onSubmit={handleSave}>
            <div className="field">
              <label>Title</label>
              <input
                value={editing.form.title}
                onChange={(e) => setField('title', e.target.value)}
                placeholder="Shipment title"
              />
              <p className="hint">This is the WordPress post title shown in WP Admin.</p>
            </div>

            <div className="field-row">
              <div className="field"><label>Owner Name</label><input value={editing.form.owner_name} onChange={(e) => setField('owner_name', e.target.value)} required /></div>
              <div className="field"><label>Owner Email</label><input type="email" value={editing.form.owner_email} onChange={(e) => setField('owner_email', e.target.value)} /></div>
            </div>
            <div className="field-row">
              <div className="field"><label>Owner Phone</label><input value={editing.form.owner_phone} onChange={(e) => setField('owner_phone', e.target.value)} /></div>
              <div className="field"><label>Stage</label>
                <select value={editing.form.stage} onChange={(e) => setField('stage', e.target.value)}>
                  {Object.entries(STAGES).map(([val, label]) => <option key={val} value={val}>{label}</option>)}
                </select>
              </div>
            </div>
            <div className="field-row">
              <div className="field"><label>Consignee Name</label><input value={editing.form.consignee_name} onChange={(e) => setField('consignee_name', e.target.value)} /></div>
              <div className="field"><label>Consignee Phone</label><input value={editing.form.consignee_phone} onChange={(e) => setField('consignee_phone', e.target.value)} /></div>
            </div>
            <div className="field-row">
              <div className="field"><label>Origin</label><input value={editing.form.origin} onChange={(e) => setField('origin', e.target.value)} /></div>
              <div className="field"><label>Destination</label><input value={editing.form.destination} onChange={(e) => setField('destination', e.target.value)} /></div>
            </div>
            <div className="field"><label>Current Location</label><input value={editing.form.current_location} onChange={(e) => setField('current_location', e.target.value)} /></div>
            <div className="field"><label>Package Description</label><textarea value={editing.form.package_description} onChange={(e) => setField('package_description', e.target.value)} /></div>
            <div className="field-row">
              <div className="field"><label>Weight</label><input value={editing.form.weight} onChange={(e) => setField('weight', e.target.value)} /></div>
              <div className="field"><label>Pieces</label><input value={editing.form.pieces} onChange={(e) => setField('pieces', e.target.value)} /></div>
            </div>
            <div className="field-row">
              <div className="field"><label>Dimensions</label><input value={editing.form.dimensions} onChange={(e) => setField('dimensions', e.target.value)} /></div>
              <div className="field"><label>Declared Value</label><input value={editing.form.declared_value} onChange={(e) => setField('declared_value', e.target.value)} /></div>
            </div>
            <div className="field-row">
              <div className="field"><label>Service Type</label><input value={editing.form.service_type} onChange={(e) => setField('service_type', e.target.value)} /></div>
              <div className="field"><label>Carrier</label><input value={editing.form.carrier} onChange={(e) => setField('carrier', e.target.value)} /></div>
            </div>
            <div className="field-row">
              <div className="field"><label>Date Booked</label><input type="date" value={editing.form.date_booked} onChange={(e) => setField('date_booked', e.target.value)} /></div>
              <div className="field"><label>Estimated Delivery</label><input type="date" value={editing.form.estimated_delivery} onChange={(e) => setField('estimated_delivery', e.target.value)} /></div>
            </div>
            <div className="field"><label>Special Instructions</label><textarea value={editing.form.special_instructions} onChange={(e) => setField('special_instructions', e.target.value)} /></div>

            {!editing.id && <p className="hint" style={{ marginBottom: 14 }}>A tracking number is generated automatically once you save.</p>}

            <button className="btn btn-primary" type="submit" disabled={saving} style={{ width: '100%', justifyContent: 'center' }}>
              {saving ? 'Saving…' : editing.id ? 'Save Changes' : 'Create Shipment'}
            </button>
          </form>
        </Modal>
      )}
    </div>
  );
}
