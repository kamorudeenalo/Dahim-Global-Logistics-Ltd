import { useCallback, useEffect, useMemo, useState } from 'react';
import {
  listItems,
  listItemsPaged,
  getItem,
  createItem,
  updateItem,
  deleteItem,
  listCategories,
  listTags,
  createCategory,
  createTag,
  uploadMedia,
  getSiteSettings,
  ApiError,
} from '../api.js';
import { useToast } from '../ToastContext.jsx';
import { IconPlus, IconEdit, IconTrash } from '../icons.jsx';
import RichTextEditor from '../RichTextEditor.jsx';
import './PostsCustom.css';

const EMPTY_FORM = {
  title: '', content: '', excerpt: '', status: 'draft', slug: '', date: '',
  categories: [], tags: [], featured_media: 0, author: '',
  focus_keyword: '', seo_title: '', meta_description: '',
};

const TABS = [
  ['all', 'All Insights'], ['publish', 'Published'], ['draft', 'Drafts'],
  ['future', 'Scheduled'], ['trash', 'Bin'],
];

export default function Posts() {
  const { push } = useToast();
  const [items, setItems] = useState([]);
  const [categories, setCategories] = useState([]);
  const [tags, setTags] = useState([]);
  const [authors, setAuthors] = useState([]);
  const [loading, setLoading] = useState(true);
  const [tab, setTab] = useState('all');
  const [search, setSearch] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('');
  const [authorFilter, setAuthorFilter] = useState('');
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [counts, setCounts] = useState({ all: 0, publish: 0, draft: 0, future: 0, trash: 0 });
  const [selected, setSelected] = useState(new Set());
  const [bulkAction, setBulkAction] = useState('');
  const [editing, setEditing] = useState(null);
  const [saving, setSaving] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [newCategory, setNewCategory] = useState('');
  const [newTag, setNewTag] = useState('');
  const [siteTimezone, setSiteTimezone] = useState('Africa/Lagos');
  const [publicInsightsUrl, setPublicInsightsUrl] = useState('');

  useEffect(() => {
    getSiteSettings().then(async settings => {
      if (settings?.timezone_string) setSiteTimezone(settings.timezone_string);
      if (settings?.page_for_posts) {
        try {
          const page = await getItem('pages', settings.page_for_posts);
          if (page?.link) setPublicInsightsUrl(page.link);
        } catch {}
      }
    }).catch(() => {});
  }, []);

  const buildFilters = useCallback(() => {
    const params = { per_page: 20, page, orderby: 'date', order: 'desc' };
    params.status = tab === 'all' ? 'any' : tab;
    if (search.trim()) params.search = search.trim();
    if (categoryFilter) params.categories = Number(categoryFilter);
    if (authorFilter) params.author = Number(authorFilter);
    return params;
  }, [tab, page, search, categoryFilter, authorFilter]);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const statusRequests = ['publish', 'draft', 'future'].map(status => listItemsPaged('posts', { status, per_page: 1 }).catch(() => ({ total: 0 })));
      const [result, cats, tagItems, users, ...statusResults] = await Promise.all([
        listItemsPaged('posts', buildFilters()), listCategories(), listTags(),
        listItems('users', { per_page: 100, context: 'view' }).catch(() => []),
        ...statusRequests,
        listItemsPaged('posts', { status: 'trash', per_page: 1 }).catch(() => ({ total: 0 })),
      ]);
      setItems(result.items); setTotalPages(Math.max(1, result.totalPages)); setCategories(cats); setTags(tagItems); setAuthors(users);
      const [published, drafts, scheduled, trash] = statusResults;
      setCounts({ all: published.total + drafts.total + scheduled.total, publish: published.total, draft: drafts.total, future: scheduled.total, trash: trash.total });
    } catch (err) {
      push(err instanceof ApiError ? err.message : 'Could not load Insights.', 'error');
    } finally { setLoading(false); }
  }, [buildFilters, push]);

  useEffect(() => { load(); }, [load]);
  useEffect(() => { setPage(1); setSelected(new Set()); }, [tab, search, categoryFilter, authorFilter]);

  function openNew() {
    setNewCategory(''); setNewTag('');
    setEditing({ id: null, slugManual: false, dirty: false, form: { ...EMPTY_FORM, date: toSiteDateTime(new Date(), siteTimezone) } });
  }

  async function openEdit(item) {
    try {
      const full = await getItem('posts', item.id);
      setEditing({
        id: item.id, slugManual: true, dirty: false,
        form: {
          ...EMPTY_FORM,
          title: decode(full.title?.raw ?? full.title?.rendered ?? ''),
          content: full.content?.raw ?? full.content?.rendered ?? '',
          excerpt: full.excerpt?.raw ?? stripHtml(full.excerpt?.rendered || ''),
          status: full.status || 'draft', slug: full.slug || '', date: toSiteDateTime(new Date(full.date), siteTimezone),
          categories: Array.isArray(full.categories) ? full.categories : [], tags: Array.isArray(full.tags) ? full.tags : [],
          featured_media: Number(full.featured_media || 0), author: Number(full.author || 0) || '',
          focus_keyword: full.meta?.dahim_focus_keyword || '', seo_title: full.meta?.dahim_seo_title || '', meta_description: full.meta?.dahim_meta_description || '',
        },
      });
      setNewCategory(''); setNewTag('');
    } catch (err) { push(err instanceof ApiError ? err.message : 'Could not open this Insight.', 'error'); }
  }

  function closeEditor() {
    if (editing?.dirty && !window.confirm('You have unsaved changes. Leave without saving?')) return;
    setEditing(null);
  }

  function setField(key, value) { setEditing(prev => ({ ...prev, dirty: true, form: { ...prev.form, [key]: value } })); }
  function setTitle(value) { setEditing(prev => ({ ...prev, dirty: true, form: { ...prev.form, title: value, slug: prev.slugManual ? prev.form.slug : slugify(value) } })); }
  function setSlug(value) { setEditing(prev => ({ ...prev, dirty: true, slugManual: true, form: { ...prev.form, slug: slugify(value) } })); }

  const savePost = async (intent = 'draft') => {
    if (!editing) return;
    const { form } = editing;
    if (!form.title.trim()) { push('Add a title before saving the Insight.', 'error'); return; }
    if (intent === 'schedule' && !isFutureDate(form.date, siteTimezone)) { push('Choose a future publication date before scheduling.', 'error'); return; }
    setSaving(true);
    try {
      const status = intent === 'publish' ? 'publish' : intent === 'schedule' ? 'future' : 'draft';
      const payload = {
        title: form.title.trim(), content: form.content, excerpt: form.excerpt, status,
        slug: form.slug || undefined, categories: form.categories, tags: form.tags, featured_media: Number(form.featured_media || 0),
        meta: { dahim_focus_keyword: form.focus_keyword.trim(), dahim_seo_title: form.seo_title.trim(), dahim_meta_description: form.meta_description.trim() },
      };
      if (form.author) payload.author = Number(form.author);
      if (form.date) payload.date = localSiteDateTimeToUtc(form.date, siteTimezone);
      if (editing.id) await updateItem('posts', editing.id, payload); else await createItem('posts', payload);
      setEditing(null); setSelected(new Set()); await load();
      push(intent === 'publish' ? 'Insight published successfully.' : intent === 'schedule' ? 'Insight scheduled successfully.' : 'Draft saved successfully.', 'success');
    } catch (err) { push(err instanceof ApiError ? err.message : 'Could not save this Insight.', 'error'); }
    finally { setSaving(false); }
  };

  async function handleFeaturedUpload(e) {
    const file = e.target.files?.[0]; e.target.value = ''; if (!file) return;
    if (!file.type.startsWith('image/')) { push('Please select an image file.', 'error'); return; }
    setUploading(true);
    try {
      const media = await uploadMedia(file, { title: editing.form.title || file.name.replace(/\.[^.]+$/, ''), alt_text: editing.form.title || '' });
      setField('featured_media', Number(media.id)); push('Featured image uploaded.', 'success');
    } catch (err) { push(err instanceof ApiError ? err.message : 'Could not upload featured image.', 'error'); }
    finally { setUploading(false); }
  }

  async function addCategory() {
    const name = newCategory.trim(); if (!name) return;
    try { const cat = await createCategory(name); setCategories(prev => [...prev, cat].sort((a, b) => a.name.localeCompare(b.name))); setField('categories', [...editing.form.categories, Number(cat.id)]); setNewCategory(''); push('Category added.', 'success'); }
    catch (err) { push(err instanceof ApiError ? err.message : 'Could not create category.', 'error'); }
  }

  async function addTag() {
    const name = newTag.trim(); if (!name) return;
    try { const tag = await createTag(name); setTags(prev => [...prev, tag].sort((a, b) => a.name.localeCompare(b.name))); setField('tags', [...editing.form.tags, Number(tag.id)]); setNewTag(''); push('Tag added.', 'success'); }
    catch (err) { push(err instanceof ApiError ? err.message : 'Could not create tag.', 'error'); }
  }

  async function bulk(action, idsOverride = null) {
    const ids = idsOverride ? new Set(idsOverride) : selected;
    if (!ids.size) { push('Select at least one Insight.', 'error'); return; }
    const message = action === 'delete' ? 'Delete the selected Insights permanently?' : action === 'trash' ? 'Move the selected Insights to the Bin?' : action === 'publish' ? 'Publish the selected Insights?' : 'Move the selected Insights to Drafts?';
    if (!window.confirm(message)) return;
    let ok = 0; let failed = 0;
    for (const id of ids) {
      try { if (action === 'delete') await deleteItem('posts', id); else await updateItem('posts', id, { status: action === 'trash' ? 'trash' : action === 'restore' ? 'draft' : action }); ok++; }
      catch { failed++; }
    }
    setSelected(new Set()); await load();
    push(`${ok} Insight${ok === 1 ? '' : 's'} processed${failed ? `; ${failed} failed` : ''}.`, failed ? 'error' : 'success');
  }

  const allVisibleSelected = items.length > 0 && items.every(item => selected.has(item.id));

  if (editing) return <InsightEditor editing={editing} categories={categories} tags={tags} authors={authors} saving={saving} uploading={uploading} siteTimezone={siteTimezone} publicInsightsUrl={publicInsightsUrl} newCategory={newCategory} setNewCategory={setNewCategory} newTag={newTag} setNewTag={setNewTag} onClose={closeEditor} onSave={savePost} onFeaturedUpload={handleFeaturedUpload} setField={setField} setTitle={setTitle} setSlug={setSlug} addCategory={addCategory} addTag={addTag} push={push} />;

  return <div className="insights-page">
    <div className="insights-head"><div><div className="eyebrow">CONTENT</div><h1>Insights</h1><p>Write, optimize and publish articles for the Dahim website.</p></div><button className="btn btn-primary insights-new-btn" onClick={openNew}><IconPlus /> New Insight</button></div>
    <div className="insights-tabs">{TABS.map(([key, label]) => <button key={key} className={tab === key ? 'active' : ''} onClick={() => setTab(key)}>{label}<span>{counts[key] || 0}</span></button>)}</div>
    <div className="insights-toolbar"><div className="insights-search"><span>⌕</span><input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search Insights" /></div><select value={categoryFilter} onChange={e => setCategoryFilter(e.target.value)}><option value="">All categories</option>{categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}</select><select value={authorFilter} onChange={e => setAuthorFilter(e.target.value)}><option value="">All authors</option>{authors.map(a => <option key={a.id} value={a.id}>{a.name || a.slug}</option>)}</select><div className="insights-bulk"><select value={bulkAction} onChange={e => setBulkAction(e.target.value)}><option value="">Bulk actions</option>{tab === 'trash' ? <><option value="restore">Restore</option><option value="delete">Delete permanently</option></> : <><option value="trash">Move to Bin</option><option value="publish">Publish</option><option value="draft">Move to Drafts</option></>}</select><button className="btn btn-outline btn-sm" onClick={() => { if (bulkAction) { bulk(bulkAction); setBulkAction(''); } }}>Apply</button></div></div>
    {loading ? <div className="center-loading"><div className="spinner" /></div> : !items.length ? <div className="insights-empty"><div className="insights-empty-icon">✦</div><h3>{tab === 'trash' ? 'Your Bin is empty' : 'No Insights found'}</h3><p>Start publishing useful information for Dahim customers and partners.</p>{tab !== 'trash' && <button className="btn btn-primary" onClick={openNew}><IconPlus /> Create your first Insight</button>}</div> : <><div className="insights-table-card"><table className="insights-table"><thead><tr><th className="check-col"><input type="checkbox" checked={allVisibleSelected} onChange={e => setSelected(prev => { const next = new Set(prev); items.forEach(item => e.target.checked ? next.add(item.id) : next.delete(item.id)); return next; })} /></th><th>Insight</th><th>Author</th><th>Category</th><th>SEO</th><th>Status</th><th>Date</th><th /></tr></thead><tbody>{items.map(item => <InsightRow key={item.id} item={item} categories={categories} authors={authors} selected={selected.has(item.id)} tab={tab} onSelect={checked => setSelected(prev => { const next = new Set(prev); checked ? next.add(item.id) : next.delete(item.id); return next; })} onEdit={() => openEdit(item)} onTrash={() => bulk(tab === 'trash' ? 'delete' : 'trash', [item.id])} onRestore={() => bulk('restore', [item.id])} />)}</tbody></table></div><div className="insights-pagination"><span>Page {page} of {totalPages}</span><div><button className="btn btn-outline btn-sm" disabled={page <= 1} onClick={() => setPage(p => p - 1)}>Previous</button><button className="btn btn-outline btn-sm" disabled={page >= totalPages} onClick={() => setPage(p => p + 1)}>Next</button></div></div></>}
  </div>;
}

function InsightRow({ item, categories, authors, selected, tab, onSelect, onEdit, onTrash, onRestore }) {
  const categoryNames = (item.categories || []).map(id => categories.find(c => Number(c.id) === Number(id))?.name).filter(Boolean);
  const author = authors.find(a => Number(a.id) === Number(item.author))?.name || item._embedded?.author?.[0]?.name || '—';
  const seo = calculateSeo({ title: decode(item.title?.rendered || ''), excerpt: stripHtml(item.excerpt?.rendered || ''), content: stripHtml(item.content?.rendered || ''), slug: item.slug || '', focus_keyword: item.meta?.dahim_focus_keyword || '', seo_title: item.meta?.dahim_seo_title || '', meta_description: item.meta?.dahim_meta_description || '' });
  return <tr><td className="check-col"><input type="checkbox" checked={selected} onChange={e => onSelect(e.target.checked)} /></td><td><button className="insight-title" onClick={onEdit}>{decode(item.title?.rendered || '(Untitled Insight)')}</button><div className="row-actions"><button onClick={onEdit}><IconEdit /> Edit</button>{tab !== 'trash' && <button onClick={onTrash}><IconTrash /> Bin</button>}{tab === 'trash' && <><button onClick={onRestore}>Restore</button><button onClick={onTrash}>Delete</button></>}</div></td><td>{decode(author)}</td><td>{categoryNames.length ? categoryNames.join(', ') : '—'}</td><td><span className={`seo-pill seo-${seo.score >= 80 ? 'good' : seo.score >= 50 ? 'mid' : 'low'}`}>{seo.score}/100</span></td><td><span className={`status-pill status-${item.status}`}>{item.status === 'publish' ? 'Published' : item.status === 'future' ? 'Scheduled' : item.status === 'trash' ? 'Bin' : 'Draft'}</span></td><td className="mono">{formatPostDate(item)}</td><td><button className="table-icon" onClick={onEdit} aria-label="Edit"><IconEdit /></button></td></tr>;
}

function InsightEditor({ editing, categories, tags, authors, saving, uploading, siteTimezone, publicInsightsUrl, newCategory, setNewCategory, newTag, setNewTag, onClose, onSave, onFeaturedUpload, setField, setTitle, setSlug, addCategory, addTag, push }) {
  const { form } = editing;
  const [featuredPreview, setFeaturedPreview] = useState('');
  const [mediaOpen, setMediaOpen] = useState(false);
  const [mediaItems, setMediaItems] = useState([]);
  const [mediaLoading, setMediaLoading] = useState(false);
  const seo = useMemo(() => calculateSeo({ title: form.title, excerpt: form.excerpt, content: stripHtml(form.content), slug: form.slug, focus_keyword: form.focus_keyword, seo_title: form.seo_title, meta_description: form.meta_description }), [form]);
  const publicBase = (import.meta.env.VITE_WP_API_URL || window.location.origin).replace(/\/dashboard\/?$/, '').replace(/\/$/, '');
  const previewUrl = publicInsightsUrl || `${publicBase}/insights/`;
  const googleTitle = form.seo_title.trim() || form.title.trim() || 'Your Insight title';
  const googleDescription = form.meta_description.trim() || form.excerpt.trim() || 'Add a meta description to control what appears in search results.';

  useEffect(() => {
    let active = true;
    if (!form.featured_media) { setFeaturedPreview(''); return undefined; }
    getItem('media', form.featured_media).then(media => { if (active) setFeaturedPreview(media.source_url || media.media_details?.sizes?.medium?.source_url || ''); }).catch(() => setFeaturedPreview(''));
    return () => { active = false; };
  }, [form.featured_media]);

  async function openMediaLibrary() {
    setMediaOpen(true); setMediaLoading(true);
    try { setMediaItems(await listItems('media', { per_page: 60, media_type: 'image', orderby: 'date', order: 'desc' })); }
    catch (err) { push(err instanceof ApiError ? err.message : 'Could not load Media Library.', 'error'); }
    finally { setMediaLoading(false); }
  }

  return <div className="insight-editor-page"><div className="insight-editor-header"><div><button className="back-link" onClick={onClose}>← All Insights</button><div className="eyebrow">INSIGHT EDITOR</div><h1>{editing.id ? 'Edit Insight' : 'Write a New Insight'}</h1></div><div className="editor-header-actions"><span className={`status-pill status-${form.status}`}>{form.status === 'publish' ? 'Published' : form.status === 'future' ? 'Scheduled' : 'Draft'}</span><button className="btn btn-outline" onClick={() => onSave('draft')} disabled={saving || uploading}>{saving ? 'Saving…' : 'Save Draft'}</button>{form.status === 'future' ? <button className="btn btn-primary" onClick={() => onSave('schedule')} disabled={saving || uploading}>Schedule Insight</button> : <button className="btn btn-primary" onClick={() => onSave('publish')} disabled={saving || uploading}>{editing.id && form.status === 'publish' ? 'Update Insight' : 'Publish Insight'}</button>}</div></div>
    <div className="insight-editor-grid"><main className="insight-editor-main"><section className="editor-card"><div className="field"><label>Title</label><input className="title-input" value={form.title} onChange={e => setTitle(e.target.value)} placeholder="Write a clear, useful title…" autoFocus /></div><div className="field"><div className="label-row"><label>Excerpt</label><span>{form.excerpt.length}/160</span></div><textarea value={form.excerpt} maxLength={160} rows={4} onChange={e => setField('excerpt', e.target.value)} placeholder="A short summary shown on the Insights listing…" /></div><div className="field"><label>Article</label><RichTextEditor value={form.content} onChange={html => setField('content', html)} push={push}/><div className="editor-word-count">{countWords(stripHtml(form.content))} words</div></div>
      <div className="editor-section"><div className="editor-section-title">Publishing details</div><div className="editor-field-grid"><div className="field"><label>Author</label><select value={form.author} onChange={e => setField('author', e.target.value)}><option value="">Current user</option>{authors.map(author => <option key={author.id} value={author.id}>{author.name || author.slug}</option>)}</select></div><div className="field"><label>Publish date</label><input type="datetime-local" value={form.date} onChange={e => setField('date', e.target.value)} /></div><div className="field"><label>Status</label><select value={form.status} onChange={e => setField('status', e.target.value)}><option value="draft">Draft</option><option value="future">Scheduled</option><option value="publish">Published</option></select></div></div><div className="field"><label>URL slug</label><input value={form.slug} onChange={e => setSlug(e.target.value)} placeholder="your-insight-slug"/><span className="field-hint">{editing.slugManual ? 'Manual slug. Changing it may change the live URL.' : 'Generated from the title until you edit it.'}</span></div><div className="editor-field-grid"><TaxonomyBox label="Category" items={categories} selected={form.categories} onChange={ids => setField('categories', ids)} newValue={newCategory} setNewValue={setNewCategory} onAdd={addCategory}/><TaxonomyBox label="Tags" items={tags} selected={form.tags} onChange={ids => setField('tags', ids)} newValue={newTag} setNewValue={setNewTag} onAdd={addTag}/></div></div>
      <div className="editor-section"><div className="editor-section-title">Featured image</div>{featuredPreview ? <div className="featured-preview-custom"><img src={featuredPreview} alt=""/><div><button type="button" className="btn btn-outline btn-sm" onClick={openMediaLibrary}>Choose another</button><label className="btn btn-outline btn-sm upload-button">Upload new<input type="file" accept="image/*" hidden onChange={onFeaturedUpload}/></label><button type="button" className="btn btn-danger btn-sm" onClick={() => setField('featured_media', 0)}>Remove</button></div></div> : <div className="featured-dropzone"><div>▧</div><strong>No featured image selected</strong><span>Use a strong landscape image for the Insights listing.</span><div><button type="button" className="btn btn-outline" onClick={openMediaLibrary}>Choose from Media</button><label className="btn btn-primary upload-button">Upload image<input type="file" accept="image/*" hidden onChange={onFeaturedUpload} disabled={uploading}/></label></div></div>}{mediaOpen && <div className="media-picker-custom"><div className="media-picker-head"><strong>Choose an image</strong><button type="button" className="btn btn-outline btn-sm" onClick={() => setMediaOpen(false)}>Close</button></div>{mediaLoading ? <div className="center-loading"><div className="spinner"/></div> : <div className="media-grid-custom">{mediaItems.map(item => <button type="button" key={item.id} onClick={() => { setField('featured_media', Number(item.id)); setMediaOpen(false); }}><img src={item.media_details?.sizes?.medium?.source_url || item.media_details?.sizes?.thumbnail?.source_url || item.source_url} alt=""/><span>{decode(item.title?.rendered || 'Image')}</span></button>)}</div>}</div>}</div></section></main>
    <aside className="insight-editor-side"><section className="editor-card seo-card"><div className="card-kicker">SEARCH OPTIMIZATION</div><h2>SEO</h2><p className="side-intro">Improve how this Insight can appear in search without blocking publication.</p><div className="field"><div className="label-row"><label>Focus keyword</label><span>{form.focus_keyword.length}/80</span></div><input value={form.focus_keyword} maxLength={80} onChange={e => setField('focus_keyword', e.target.value)} placeholder="e.g. freight forwarding in Nigeria"/></div><div className="field"><div className="label-row"><label>SEO title</label><span>{form.seo_title.length}/60</span></div><input value={form.seo_title} maxLength={60} onChange={e => setField('seo_title', e.target.value)} placeholder={form.title || 'Falls back to Insight title'}/></div><div className="field"><div className="label-row"><label>Meta description</label><span>{form.meta_description.length}/160</span></div><textarea value={form.meta_description} maxLength={160} rows={5} onChange={e => setField('meta_description', e.target.value)} placeholder={form.excerpt || 'Falls back to excerpt'}/></div><SeoScoreCard seo={seo}/></section><section className="editor-card google-card"><div className="card-kicker">SEARCH PREVIEW</div><h2>Google preview</h2><div className="google-preview"><div className="google-url">{previewUrl}</div><div className="google-title">{googleTitle}</div><div className="google-description">{googleDescription}</div></div></section><section className="editor-card publishing-card"><div className="card-kicker">PUBLISHING</div><h2>Ready to publish?</h2><p>{form.status === 'publish' ? 'Changes will update the live Insight.' : form.status === 'future' ? 'This Insight is set to publish at the selected date.' : 'Save as a draft while the article is being prepared.'}</p><div className="publishing-actions"><button className="btn btn-outline" onClick={() => onSave('draft')} disabled={saving || uploading}>Save draft</button><button className="btn btn-primary" onClick={() => onSave(form.status === 'future' ? 'schedule' : 'publish')} disabled={saving || uploading}>{form.status === 'future' ? 'Schedule' : form.status === 'publish' ? 'Update live Insight' : 'Publish now'}</button></div></section></aside></div>
  </div>;
}

function TaxonomyBox({ label, items, selected, onChange, newValue, setNewValue, onAdd }) { function toggle(id) { const next = new Set((selected || []).map(Number)); const numeric = Number(id); next.has(numeric) ? next.delete(numeric) : next.add(numeric); onChange([...next]); } return <div className="field"><label>{label}</label><div className="taxonomy-box">{items.length ? items.map(item => <label key={item.id}><input type="checkbox" checked={(selected || []).map(Number).includes(Number(item.id))} onChange={() => toggle(item.id)}/><span>{item.name}</span></label>) : <span className="field-hint">No {label.toLowerCase()}s yet.</span>}</div><div className="taxonomy-add"><input value={newValue} onChange={e => setNewValue(e.target.value)} onKeyDown={e => e.key === 'Enter' && (e.preventDefault(), onAdd())} placeholder={`New ${label.toLowerCase()}`}/><button type="button" className="btn btn-outline btn-sm" onClick={onAdd}>Add</button></div></div>; }
function SeoScoreCard({ seo }) { return <div className="seo-score-card"><div className={`seo-score-circle seo-${seo.score >= 80 ? 'good' : seo.score >= 50 ? 'mid' : 'low'}`}>{seo.score}</div><div><strong>{seo.score >= 80 ? 'Strong SEO foundation' : seo.score >= 50 ? 'Good start — room to improve' : 'SEO needs attention'}</strong><div className="seo-checks">{seo.checks.map(check => <span key={check.label} className={check.ok ? 'ok' : 'warn'}>{check.ok ? '✓' : '•'} {check.label}</span>)}</div></div></div>; }
function calculateSeo({ title, excerpt, content, slug, focus_keyword, seo_title, meta_description }) { const keyword = String(focus_keyword || '').trim().toLowerCase(); const cleanTitle = String(title || '').trim(); const cleanSeoTitle = String(seo_title || '').trim(); const cleanDescription = String(meta_description || '').trim(); const effectiveTitle = cleanSeoTitle || cleanTitle; const effectiveDescription = cleanDescription || String(excerpt || '').trim(); const normalizedSlug = String(slug || '').toLowerCase(); const body = String(content || '').toLowerCase(); const wordCount = countWords(content); if (!keyword) return { score: 0, checks: [{ ok: false, label: 'Enter a focus keyword to begin' }, { ok: Boolean(cleanTitle), label: 'Insight title' }, { ok: wordCount >= 300, label: 'Content length' }] }; let score = 0; const checks = []; const titleOk = cleanTitle.toLowerCase().includes(keyword); if (titleOk) score += 20; checks.push({ ok: titleOk, label: 'Keyword appears in the title' }); const seoTitleOk = effectiveTitle.length >= 30 && effectiveTitle.length <= 60; if (seoTitleOk) score += 15; checks.push({ ok: seoTitleOk, label: 'SEO title is 30–60 characters' }); const descriptionOk = effectiveDescription.length >= 120 && effectiveDescription.length <= 160; if (descriptionOk) score += 15; checks.push({ ok: descriptionOk, label: 'Meta description is 120–160 characters' }); const slugOk = normalizedSlug.includes(keyword.replace(/\s+/g, '-')); if (slugOk) score += 10; checks.push({ ok: slugOk, label: 'Keyword appears in the URL slug' }); const introOk = body.slice(0, 500).includes(keyword); if (introOk) score += 15; checks.push({ ok: introOk, label: 'Keyword appears near the beginning' }); const lengthOk = wordCount >= 300; if (lengthOk) score += 15; checks.push({ ok: lengthOk, label: 'Article has at least 300 words' }); const excerptOk = String(excerpt || '').trim().length >= 80; if (excerptOk) score += 10; checks.push({ ok: excerptOk, label: 'Excerpt is useful and descriptive' }); return { score, checks }; }
function countWords(text) { const value = String(text || '').replace(/<[^>]*>/g, ' ').trim(); return value ? value.split(/\s+/).length : 0; }
function decode(value) { const textarea = document.createElement('textarea'); textarea.innerHTML = String(value || ''); return textarea.value; }
function stripHtml(html) { const div = document.createElement('div'); div.innerHTML = html || ''; return div.textContent || ''; }
function slugify(value) { return String(value || '').toLowerCase().trim().normalize('NFKD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, ''); }
function toSiteDateTime(date, timeZone) { if (!date || Number.isNaN(date.getTime())) return ''; const parts = new Intl.DateTimeFormat('en-CA', { timeZone, year:'numeric', month:'2-digit', day:'2-digit', hour:'2-digit', minute:'2-digit', hourCycle:'h23' }).formatToParts(date); const map = Object.fromEntries(parts.filter(p => p.type !== 'literal').map(p => [p.type, p.value])); return `${map.year}-${map.month}-${map.day}T${map.hour}:${map.minute}`; }
function localSiteDateTimeToUtc(value, timeZone) { const desired = Date.parse(`${value}:00Z`); let guess = new Date(desired); for (let i=0;i<3;i++){ const parts = new Intl.DateTimeFormat('en-US',{timeZone,year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',second:'2-digit',hourCycle:'h23'}).formatToParts(guess); const map=Object.fromEntries(parts.filter(p=>p.type!=='literal').map(p=>[p.type,p.value])); const rendered=Date.UTC(Number(map.year),Number(map.month)-1,Number(map.day),Number(map.hour),Number(map.minute),Number(map.second)); guess=new Date(guess.getTime()+(desired-rendered)); } return guess.toISOString(); }
function isFutureDate(value, timeZone) { if (!value) return false; return new Date(localSiteDateTimeToUtc(value, timeZone)).getTime() > Date.now(); }
function formatPostDate(item) { const date = new Date(item.date); if (Number.isNaN(date.getTime())) return '—'; return date.toLocaleDateString(undefined,{day:'2-digit',month:'short',year:'numeric'}); }
