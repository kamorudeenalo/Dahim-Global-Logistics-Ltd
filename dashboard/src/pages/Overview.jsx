import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { listItems, ApiError } from '../api.js';
import { useToast } from '../ToastContext.jsx';
import { decodeHtmlEntities } from '../utils.js';

const INQUIRY_STATUS_LABELS = {
  new: 'New',
  contacted: 'Contacted',
  in_progress: 'In Progress',
  closed: 'Closed',
  abandoned: 'Abandoned'
};

const INQUIRY_STATUS_COLORS = {
  new: '#C79B3C',
  contacted: '#4C5A78',
  in_progress: '#2E6F9E',
  closed: '#008751',
  abandoned: '#8A8F98'
};

export default function Overview() {
  const { push } = useToast();
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const [inquiries, shipments] = await Promise.all([
          listItems('inquiries', { per_page: 100 }),
          listItems('shipments', { per_page: 100 }),
        ]);
        if (cancelled) return;

        const inquiryStatus = (i) => String(i.meta?._dahim_inquiry_status || 'new').toLowerCase();
        const shipmentStage = (s) => String(s.meta?._dahim_ship_stage || '1');

        const newInquiries = inquiries.filter((i) => inquiryStatus(i) === 'new').length;
        const contactedInquiries = inquiries.filter((i) => inquiryStatus(i) === 'contacted').length;
        const closedInquiries = inquiries.filter((i) => inquiryStatus(i) === 'closed').length;

        const booked = shipments.filter((s) => shipmentStage(s) === '1').length;
        const cleared = shipments.filter((s) => shipmentStage(s) === '2').length;
        const inTransit = shipments.filter((s) => shipmentStage(s) === '3').length;
        const outForDelivery = shipments.filter((s) => shipmentStage(s) === '4').length;
        const delivered = shipments.filter((s) => shipmentStage(s) === '5').length;

        setStats({
          totalInquiries: inquiries.length,
          newInquiries,
          contactedInquiries,
          closedInquiries,
          totalShipments: shipments.length,
          booked,
          cleared,
          inTransit,
          outForDelivery,
          delivered,
          recentInquiries: inquiries.slice(0, 5),
        });
      } catch (err) {
        if (!cancelled) {
          const message = err instanceof ApiError ? err.message : 'Could not load overview data.';
          setError(message);
          push(message, 'error');
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => { cancelled = true; };
  }, [push]);

  if (loading) {
    return <div className="center-loading"><div className="spinner" /></div>;
  }

  if (error || !stats) {
    return (
      <div className="empty-state">
        <h3>Couldn't load the overview</h3>
        <p>{error || 'Something went wrong loading this page.'}</p>
        <button className="btn btn-outline" style={{ marginTop: 14 }} onClick={() => window.location.reload()}>Try Again</button>
      </div>
    );
  }

  const StatCard = ({ to, value, label }) => (
    <Link to={to} className="stat-card" style={{ textDecoration: 'none' }}>
      <div className="num">{value}</div>
      <div className="label">{label}</div>
    </Link>
  );

  return (
    <div>
      <div className="page-head">
        <div>
          <h1>Overview</h1>
          <p>What's happening across the site right now.</p>
        </div>
      </div>

      <section className="stats-section">
        <div className="stats-section-head">
          <h2>Inquiries</h2>
          <Link to="/inquiries">View all</Link>
        </div>
        <div className="stat-grid">
          <StatCard to="/inquiries" value={stats.totalInquiries} label="Total Inquiries" />
          <StatCard to="/inquiries" value={stats.newInquiries} label="New Inquiries" />
          <StatCard to="/inquiries" value={stats.contactedInquiries} label="Contacted" />
          <StatCard to="/inquiries" value={stats.closedInquiries} label="Closed / Converted" />
        </div>
      </section>

      <section className="stats-section">
        <div className="stats-section-head">
          <h2>Shipments</h2>
          <Link to="/shipments">View all</Link>
        </div>
        <div className="stat-grid">
          <StatCard to="/shipments" value={stats.totalShipments} label="Total Shipments" />
          <StatCard to="/shipments" value={stats.booked} label="Booked" />
          <StatCard to="/shipments" value={stats.cleared} label="Cleared / Picked Up" />
          <StatCard to="/shipments" value={stats.inTransit} label="In Transit" />
          <StatCard to="/shipments" value={stats.outForDelivery} label="Out for Delivery" />
          <StatCard to="/shipments" value={stats.delivered} label="Delivered" />
        </div>
      </section>

      <div className="card">
        <h3 style={{ marginBottom: 14, fontSize: 15 }}>Recent Inquiries</h3>
        {stats.recentInquiries.length === 0 ? (
          <p style={{ color: 'var(--steel)', fontSize: 13.5 }}>No inquiries yet.</p>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
            {stats.recentInquiries.map((inq) => {
              const status = inquiryStatusValue(inq);
              return (
                <div key={inq.id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', paddingBottom: 10, borderBottom: '1px solid var(--line)' }}>
                  <div>
                    <div style={{ fontWeight: 600, fontSize: 13.5 }}>{decodeHtmlEntities(inq.meta?._dahim_inquiry_name) || decodeHtmlEntities(inq.title?.rendered)}</div>
                    <div style={{ fontSize: 12, color: 'var(--steel)' }}>{inq.meta?._dahim_inquiry_department}</div>
                  </div>
                  <span className="status-pill" style={{ borderColor: INQUIRY_STATUS_COLORS[status] || '#C79B3C', color: INQUIRY_STATUS_COLORS[status] || '#C79B3C' }}>
                    {INQUIRY_STATUS_LABELS[status] || 'New'}
                  </span>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}

function inquiryStatusValue(inquiry) {
  return String(inquiry.meta?._dahim_inquiry_status || 'new').toLowerCase();
}
