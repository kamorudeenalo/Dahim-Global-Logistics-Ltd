import { useEffect, useState, useCallback } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { listItems, getItem, updateItem, ApiError } from '../api.js';
import { useToast } from '../ToastContext.jsx';
import Modal from '../Modal.jsx';

const STATUSES = { new: 'New', contacted: 'Contacted', in_progress: 'In Progress', closed: 'Closed', abandoned: 'Abandoned' };
const STATUS_COLORS = { new: '#C79B3C', contacted: '#4C5A78', in_progress: '#2E6F9E', closed: '#008751', abandoned: '#8A8F98' };

export default function Inquiries() {
  const { push } = useToast();
  const { id: urlId } = useParams();
  const navigate = useNavigate();
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [deptFilter, setDeptFilter] = useState('');
  const [active, setActive] = useState(null);
  const [savingId, setSavingId] = useState(null);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await listItems('inquiries', { per_page: 100 });
      setItems(data);
    } catch (err) {
      push(err instanceof ApiError ? err.message : 'Could not load inquiries.', 'error');
    } finally {
      setLoading(false);
    }
  }, [push]);

  useEffect(() => { load(); }, [load]);

  // Deep link support (/inquiries/:id, e.g. from the admin notification
  // email) — open that specific inquiry automatically. Falls back to a
  // direct fetch if it's not among the most recent 100 already loaded.
  useEffect(() => {
    if (!urlId) { setActive(null); return; }
    const idNum = Number(urlId);
    const fromList = items.find((i) => i.id === idNum);
    if (fromList) {
      setActive(fromList);
      return;
    }
    if (!loading) {
      getItem('inquiries', idNum)
        .then((item) => setActive(item))
        .catch(() => {
          push('Could not find that inquiry — it may have been deleted.', 'error');
          navigate('/inquiries', { replace: true });
        });
    }
  }, [urlId, items, loading, navigate, push]);

  function openInquiry(item) {
    navigate(`/inquiries/${item.id}`);
  }
  function closeInquiry() {
    navigate('/inquiries');
  }

  async function changeStatus(id, status) {
    setSavingId(id);
    const prev = items;
    setItems((list) => list.map((i) => (i.id === id ? { ...i, meta: { ...i.meta, _dahim_inquiry_status: status } } : i)));
    try {
      await updateItem('inquiries', id, { meta: { _dahim_inquiry_status: status } });
      push('Status updated.', 'success');
    } catch (err) {
      setItems(prev);
      push(err instanceof ApiError ? err.message : 'Could not update status.', 'error');
    } finally {
      setSavingId(null);
    }
  }

  const departments = [...new Set(items.map((i) => i.meta?._dahim_inquiry_department).filter(Boolean))];
  const filtered = deptFilter ? items.filter((i) => i.meta?._dahim_inquiry_department === deptFilter) : items;

  const StatusDropdown = ({ item }) => (
    <select
      className="status-select"
      style={{ borderColor: STATUS_COLORS[item.meta?._dahim_inquiry_status] || '#C79B3C', color: STATUS_COLORS[item.meta?._dahim_inquiry_status] || '#C79B3C' }}
      value={item.meta?._dahim_inquiry_status || 'new'}
      disabled={savingId === item.id}
      onChange={(e) => changeStatus(item.id, e.target.value)}
      onClick={(e) => e.stopPropagation()}
    >
      {Object.entries(STATUSES).map(([val, label]) => <option key={val} value={val}>{label}</option>)}
    </select>
  );

  return (
    <div>
      <div className="page-head">
        <div>
          <h1>Inquiries</h1>
          <p>Every contact form submission, from every desk.</p>
        </div>
        {departments.length > 0 && (
          <select className="field" style={{ margin: 0, width: 'auto', border: '1.5px solid var(--line)', borderRadius: 8, padding: '9px 12px' }} value={deptFilter} onChange={(e) => setDeptFilter(e.target.value)}>
            <option value="">All Departments</option>
            {departments.map((d) => <option key={d} value={d}>{d}</option>)}
          </select>
        )}
      </div>

      {loading ? (
        <div className="center-loading"><div className="spinner" /></div>
      ) : filtered.length === 0 ? (
        <div className="empty-state"><h3>No inquiries yet</h3><p>Submissions from the contact form will show up here.</p></div>
      ) : (
        <>
          <div className="table-wrap">
            <table className="data-table">
              <thead>
                <tr><th>Name</th><th>Department</th><th>Email</th><th>Date</th><th>Status</th></tr>
              </thead>
              <tbody>
                {filtered.map((item) => (
                  <tr key={item.id} onClick={() => openInquiry(item)} style={{ cursor: 'pointer' }}>
                    <td>{item.meta?._dahim_inquiry_name || '—'}</td>
                    <td>{item.meta?._dahim_inquiry_department || '—'}</td>
                    <td>{item.meta?._dahim_inquiry_email || '—'}</td>
                    <td className="mono">{new Date(item.date).toLocaleDateString()}</td>
                    <td><StatusDropdown item={item} /></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <div className="row-cards">
            {filtered.map((item) => (
              <div key={item.id} className="row-card" onClick={() => openInquiry(item)}>
                <div className="row-card-top">
                  <div className="row-card-title">{item.meta?._dahim_inquiry_name || '—'}</div>
                  <StatusDropdown item={item} />
                </div>
                <div className="row-card-meta">
                  <span>{item.meta?._dahim_inquiry_department}</span>
                  <span>{new Date(item.date).toLocaleDateString()}</span>
                </div>
              </div>
            ))}
          </div>
        </>
      )}

      {active && (
        <Modal title={active.meta?._dahim_inquiry_name || 'Inquiry'} onClose={closeInquiry}>
          <DetailRow label="Department" value={active.meta?._dahim_inquiry_department} />
          <DetailRow label="Company" value={active.meta?._dahim_inquiry_company} />
          <DetailRow label="Email" value={active.meta?._dahim_inquiry_email} />
          <DetailRow label="Phone" value={active.meta?._dahim_inquiry_phone} />
          <DetailRow label="Service" value={active.meta?._dahim_inquiry_service} />
          <DetailRow label="Role Applied For" value={active.meta?._dahim_inquiry_role} />
          <DetailRow label="CV / Portfolio" value={active.meta?._dahim_inquiry_cv_link} link />
          <DetailRow label="Message" value={active.meta?._dahim_inquiry_message} block />
          <div className="field" style={{ marginTop: 18 }}>
            <label>Status</label>
            <select
              className="field"
              style={{ margin: 0 }}
              value={active.meta?._dahim_inquiry_status || 'new'}
              onChange={(e) => {
                changeStatus(active.id, e.target.value);
                setActive({ ...active, meta: { ...active.meta, _dahim_inquiry_status: e.target.value } });
              }}
            >
              {Object.entries(STATUSES).map(([val, label]) => <option key={val} value={val}>{label}</option>)}
            </select>
          </div>
        </Modal>
      )}
    </div>
  );
}

function DetailRow({ label, value, link, block }) {
  if (!value) return null;
  return (
    <div style={{ marginBottom: 14 }}>
      <div style={{ fontFamily: "'IBM Plex Mono',monospace", fontSize: 11, letterSpacing: '0.05em', textTransform: 'uppercase', color: 'var(--steel)', marginBottom: 4 }}>{label}</div>
      {link ? (
        <a href={value} target="_blank" rel="noreferrer" style={{ color: 'var(--amber-deep)', fontSize: 13.5, wordBreak: 'break-all' }}>{value}</a>
      ) : (
        <div style={{ fontSize: 13.5, whiteSpace: block ? 'pre-wrap' : 'normal' }}>{value}</div>
      )}
    </div>
  );
}
