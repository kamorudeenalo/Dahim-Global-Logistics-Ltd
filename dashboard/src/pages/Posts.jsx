import { useCallback, useEffect, useMemo, useState } from 'react';
import {
  listItems, listItemsPaged, getItem, createItem, updateItem, deleteItem,
  listCategories, listTags, createCategory, createTag, uploadMedia, getSiteSettings, ApiError
} from '../api.js';
import { useToast } from '../ToastContext.jsx';
import Modal from '../Modal.jsx';
import { IconPlus, IconEdit, IconTrash } from '../icons.jsx';
import { decodeHtmlEntities } from '../utils.js';
import RichTextEditor from '../RichTextEditor.jsx';

const EMPTY_FORM = {
  title: '', content: '', excerpt: '', status: 'draft', slug: '', date: '',
  categories: [], tags: [], featured_media: 0, author: '',
  visibility: 'public', password: '', passwordSet: false, comment_status: 'open', ping_status: 'open',
};

const TABS = [
  ['all', 'All Posts'], ['publish', 'Published'], ['pending', 'Pending'],
  ['draft', 'Drafts'], ['future', 'Scheduled'], ['trash', 'Bin'],
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
  const [dateFilter, setDateFilter] = useState('');
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [counts, setCounts] = useState({ all: 0, publish: 0, pending: 0, draft: 0, future: 0, trash: 0 });
  const [selected, setSelected] = useState(new Set());
  const [bulkAction, setBulkAction] = useState('');
  const [editing, setEditing] = useState(null);
  const [saving, setSaving] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [newCategory, setNewCategory] = useState('');
  const [newTag, setNewTag] = useState('');
  const [siteTimezone, setSiteTimezone] = useState('Africa/Lagos');

  useEffect(() => {
    getSiteSettings().then(settings => {
      if (settings?.timezone_string) setSiteTimezone(settings.timezone_string);
    }).catch(() => {});
  }, []);

  const buildFilters = useCallback(() => {
    const params = { per_page: 20, page, orderby: 'date', order: 'desc' };
    params.status = tab === 'all' ? 'any' : tab;
    if (search.trim()) params.search = search.trim();
    if (categoryFilter) params.categories = Number(categoryFilter);
    if (authorFilter) params.author = Number(authorFilter);
    if (dateFilter) {
      const start = new Date(`${dateFilter}T00:00:00`);
      const next = new Date(start); next.setDate(next.getDate() + 1);
      params.after = start.toISOString();
      params.before = next.toISOString();
    }
    return params;
  }, [tab, page, search, categoryFilter, authorFilter, dateFilter]);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const activeStatusParams = ['publish', 'pending', 'draft', 'future', 'private'].map(status => ({ status, per_page: 1 }));
      const [result, cats, tagItems, users, ...statusResults] = await Promise.all([
        listItemsPaged('posts', buildFilters()),
        listCategories(),
        listTags(),
        listItems('users', { per_page: 100, context: 'view' }).catch(() => []),
        ...activeStatusParams.map(params => listItemsPaged('posts', params).catch(() => ({ total: 0 }))),
        listItemsPaged('posts', { status: 'trash', per_page: 1 }).catch(() => ({ total: 0 })),
      ]);
      setItems(result.items);
      setTotalPages(Math.max(1, result.totalPages));
      setCategories(cats);
      setTags(tagItems);
      setAuthors(users);
      const [publish, pending, draft, future, privatePosts, trash] = statusResults;
      setCounts({ all: publish.total + pending.total + draft.total + future.total + privatePosts.total, publish: publish.total, pending: pending.total, draft: draft.total, future: future.total, trash: trash.total });
    } catch (err) {
      push(err instanceof ApiError ? err.message : 'Could not load posts.', 'error');
    } finally { setLoading(false); }
  }, [buildFilters, push]);

  useEffect(() => { load(); }, [load]);
  useEffect(() => { setPage(1); setSelected(new Set()); }, [tab, search, categoryFilter, authorFilter, dateFilter]);

  function openNew() {
    setNewCategory(''); setNewTag('');
    setEditing({ id: null, slugManual: false, form: { ...EMPTY_FORM, date: toSiteDateTime(new Date(), siteTimezone) } });
  }

  async function openEdit(item) {
    try {
      const full = await getItem('posts', item.id);
      const isPrivate = full.status === 'private';
      const passwordSet = Boolean(full.password);
      setEditing({
        id: item.id, slugManual: true,
        form: {
          ...EMPTY_FORM,
          title: decodeHtmlEntities(full.title?.raw ?? full.title?.rendered ?? ''),
          content: full.content?.raw ?? full.content?.rendered ?? '',
          excerpt: full.excerpt?.raw ?? stripHtml(full.excerpt?.rendered || ''),
          status: isPrivate ? 'publish' : (full.status || 'draft'), slug: full.slug || '', date: toSiteDateTime(new Date(full.date), siteTimezone),
          categories: Array.isArray(full.categories) ? full.categories : [], tags: Array.isArray(full.tags) ? full.tags : [],
          featured_media: Number(full.featured_media || 0), author: Number(full.author || 0) || '',
          visibility: isPrivate ? 'private' : (passwordSet ? 'password' : 'public'), password: '', passwordSet,
          comment_status: full.comment_status || 'open', ping_status: full.ping_status || 'open',
        }
      });
      setNewCategory(''); setNewTag('');
    } catch (err) { push(err instanceof ApiError ? err.message : 'Could not open this post.', 'error'); }
  }

  function setField(key, value) { setEditing(prev => ({ ...prev, form: { ...prev.form, [key]: value } })); }

  async function handleSave(e) {
    e.preventDefault(); if (!editing) return;
    const { form } = editing;
    if (form.status === 'future' && !isFutureDate(form.date, siteTimezone)) {
      push('Scheduled posts must have a future publication date.', 'error'); return;
    }
    if (form.status === 'publish' && form.visibility === 'public' && editing.id === null && !form.title.trim()) return;
    setSaving(true);
    try {
      const payload = {
        title: form.title.trim(), content: form.content, excerpt: form.excerpt,
        status: form.visibility === 'private' ? 'private' : form.status,
        slug: form.slug || undefined, categories: form.categories, tags: form.tags,
        featured_media: Number(form.featured_media || 0), comment_status: form.comment_status, ping_status: form.ping_status,
      };
      if (form.visibility === 'password') {
        if (form.password.trim()) payload.password = form.password.trim();
        else if (!editing.id) { push('Enter a password for a password-protected post.', 'error'); setSaving(false); return; }
      } else if (editing.id && form.passwordSet) payload.password = '';
      if (form.author) payload.author = Number(form.author);
      if (form.date) payload.date = localSiteDateTimeToUtc(form.date, siteTimezone);
      if (editing.id) { await updateItem('posts', editing.id, payload); push('Post updated.', 'success'); }
      else { await createItem('posts', payload); push(payload.status === 'publish' ? 'Post published.' : payload.status === 'future' ? 'Post scheduled.' : 'Post saved.', 'success'); }
      setEditing(null); setSelected(new Set()); await load();
    } catch (err) { push(err instanceof ApiError ? err.message : 'Could not save post.', 'error'); }
    finally { setSaving(false); }
  }

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
    try { const cat = await createCategory(name); setCategories(prev => [...prev, cat].sort((a,b)=>a.name.localeCompare(b.name))); setField('categories', [...editing.form.categories, Number(cat.id)]); setNewCategory(''); push('Category created.', 'success'); }
    catch (err) { push(err instanceof ApiError ? err.message : 'Could not create category.', 'error'); }
  }
  async function addTag() {
    const name = newTag.trim(); if (!name) return;
    try { const tag = await createTag(name); setNewTag(''); setEditing(prev => ({ ...prev, form: { ...prev.form, tags: [...prev.form.tags, Number(tag.id)] } })); push('Tag created.', 'success'); }
    catch (err) { push(err instanceof ApiError ? err.message : 'Could not create tag.', 'error'); }
  }

  async function bulk(action) {
    if (!selected.size) { push('Select at least one post.', 'error'); return; }
    const isPermanent = action === 'delete';
    const message = isPermanent ? 'Delete the selected posts permanently?' : action === 'trash' ? 'Move the selected posts to the Bin?' : action === 'publish' ? 'Publish the selected posts?' : 'Move the selected posts to Drafts?';
    if (!window.confirm(message)) return;
    const ids = [...selected]; let ok = 0, failed = 0;
    for (const id of ids) {
      try {
        if (isPermanent) await deleteItem('posts', id);
        else await updateItem('posts', id, { status: action === 'trash' ? 'trash' : action === 'restore' ? 'draft' : action });
        ok++;
      } catch { failed++; }
    }
    setSelected(new Set()); await load();
    push(`${ok} post${ok === 1 ? '' : 's'} processed${failed ? `; ${failed} failed` : ''}.`, failed ? 'error' : 'success');
  }

  async function bulkSingle(id, action) {
    setSelected(new Set([id]));
    await bulk(action);
  }

  const allVisibleSelected = items.length > 0 && items.every(p => selected.has(p.id));
  const toggleAll = checked => setSelected(prev => { const next = new Set(prev); items.forEach(p => checked ? next.add(p.id) : next.delete(p.id)); return next; });
  const tabItems = TABS.map(([key, label]) => [key, label, counts[key] || 0]);

  return <div>
    <div className="page-head">
      <div><h1>Insights</h1><p>Blog posts shown on the public Insights page.</p></div>
      <button className="btn btn-primary" onClick={openNew}><IconPlus /> New Post</button>
    </div>

    <div className="post-tabs">{tabItems.map(([key,label,count]) => <button key={key} className={tab===key?'active':''} onClick={()=>setTab(key)}>{label} <span>{count}</span></button>)}</div>
    <div className="post-tools">
      <div className="bulk-group"><select value={bulkAction} onChange={e=>setBulkAction(e.target.value)}><option value="">Bulk actions</option>{tab==='trash'?<><option value="restore">Restore</option><option value="delete">Delete permanently</option></>:<><option value="trash">Move to Bin</option><option value="publish">Publish</option><option value="draft">Move to Drafts</option></>}</select><button className="btn btn-outline btn-sm" onClick={()=>{if(bulkAction){bulk(bulkAction);setBulkAction('');}}}>Apply</button></div>
      <select value={categoryFilter} onChange={e=>setCategoryFilter(e.target.value)}><option value="">All Categories</option>{categories.map(c=><option key={c.id} value={c.id}>{c.name}</option>)}</select>
      <select value={authorFilter} onChange={e=>setAuthorFilter(e.target.value)}><option value="">All Authors</option>{authors.map(a=><option key={a.id} value={a.id}>{a.name||a.slug}</option>)}</select>
      <input type="date" value={dateFilter} onChange={e=>setDateFilter(e.target.value)} title="Filter by date" />
      <div className="post-search"><input value={search} onChange={e=>setSearch(e.target.value)} placeholder="Search Posts" /></div>
    </div>

    {loading ? <div className="center-loading"><div className="spinner" /></div> : !items.length ? <div className="empty-state"><h3>{tab==='trash'?'Bin is empty':'No posts found'}</h3><p>Try another filter or create a new post.</p></div> : <>
      <div className="table-wrap"><table className="data-table posts-table"><thead><tr><th className="check-cell"><input type="checkbox" checked={allVisibleSelected} onChange={e=>toggleAll(e.target.checked)} /></th><th>Post Title</th><th>Category</th><th>Author</th><th>Date</th><th className="post-actions">Actions</th></tr></thead><tbody>{items.map(item=><PostRow key={item.id} item={item} categories={categories} authors={authors} tab={tab} selected={selected.has(item.id)} onSelect={checked=>setSelected(prev=>{const n=new Set(prev);checked?n.add(item.id):n.delete(item.id);return n;})} onEdit={()=>openEdit(item)} onTrash={()=>bulkSingle(item.id, tab==='trash'?'delete':'trash')} onRestore={()=>bulkSingle(item.id,'restore')} />)}</tbody></table></div>
      <div className="posts-pagination"><button className="btn btn-outline btn-sm" disabled={page<=1} onClick={()=>setPage(p=>p-1)}>Previous</button><span>Page {page} of {totalPages}</span><button className="btn btn-outline btn-sm" disabled={page>=totalPages} onClick={()=>setPage(p=>p+1)}>Next</button></div>
    </>}

    {editing && <PostEditorModal editing={editing} setEditing={setEditing} categories={categories} tags={tags} authors={authors} saving={saving} uploading={uploading} onSave={handleSave} onFeaturedUpload={handleFeaturedUpload} newCategory={newCategory} setNewCategory={setNewCategory} newTag={newTag} setNewTag={setNewTag} addCategory={addCategory} addTag={addTag} setField={setField} push={push} />}
  </div>;
}

function PostRow({ item, categories, authors, tab, selected, onSelect, onEdit, onTrash, onRestore }) {
  const catNames=(item.categories||[]).map(id=>categories.find(c=>Number(c.id)===Number(id))?.name).filter(Boolean);
  const author=authors.find(a=>Number(a.id)===Number(item.author))?.name || item._embedded?.author?.[0]?.name || '—';
  return <tr>
    <td className="check-cell"><input type="checkbox" checked={selected} onChange={e=>onSelect(e.target.checked)} /></td>
    <td><button className="post-title-link" onClick={onEdit}>{decodeHtmlEntities(item.title?.rendered||'(no title)')}</button><div className="post-row-actions"><button onClick={onEdit}>Edit</button>{tab!=='trash'&&<button onClick={onTrash}>Move to Bin</button>}{tab==='trash'&&<><button onClick={onRestore}>Restore</button><button onClick={onTrash}>Delete permanently</button></>}</div></td>
    <td>{catNames.length?catNames.join(', '):'—'}</td><td>{decodeHtmlEntities(author)}</td><td className="mono">{formatPostDate(item)}</td>
    <td className="post-actions"><button className="icon-btn" onClick={onEdit} aria-label="Edit"><IconEdit/></button>{tab==='trash'&&<button className="icon-btn" onClick={onRestore} aria-label="Restore">↶</button>}<button className="icon-btn" onClick={onTrash} aria-label={tab==='trash'?'Delete permanently':'Move to Bin'}><IconTrash/></button></td>
  </tr>;
}

function PostEditorModal({ editing, setEditing, categories, tags, authors, saving, uploading, onSave, onFeaturedUpload, newCategory, setNewCategory, newTag, setNewTag, addCategory, addTag, setField, push }) {
  const { form } = editing;
  const [featuredPreview, setFeaturedPreview] = useState('');
  const [mediaOpen, setMediaOpen] = useState(false);
  const [mediaItems, setMediaItems] = useState([]);
  const [mediaLoading, setMediaLoading] = useState(false);
  useEffect(()=>{ let active=true; if(!form.featured_media){setFeaturedPreview('');return;} getItem('media',form.featured_media).then(m=>{if(active)setFeaturedPreview(m.source_url||m.media_details?.sizes?.medium?.source_url||'');}).catch(()=>{}); return ()=>{active=false;}; },[form.featured_media]);
  const statusButton=form.status==='publish'?'Publish Post':form.status==='pending'?'Submit for Review':form.status==='future'?'Schedule Post':editing.id?'Update Post':'Publish Post';
  const selectedCat=new Set(form.categories.map(Number));
  const openMediaLibrary=async()=>{setMediaOpen(true);setMediaLoading(true);try{setMediaItems(await listItems('media',{per_page:60,media_type:'image',orderby:'date',order:'desc'}));}catch(err){push(err instanceof ApiError?err.message:'Could not load Media Library.','error');}finally{setMediaLoading(false);}};
  const slugAuto=editing.slugManual===false;
  return <Modal title={editing.id?'Edit Post':'New Post'} onClose={()=>setEditing(null)} wide>
    <form className="post-editor-form" onSubmit={onSave}>
      <div className="post-editor-topbar"><div className="editor-state">{form.status==='future'?'Scheduled post':form.visibility==='private'?'Private post':form.visibility==='password'?'Password protected':'WordPress Post'}</div><button type="submit" className="btn btn-primary" disabled={saving||uploading}>{saving?'Saving…':statusButton}</button></div>
      <div className="field"><label>Title</label><input value={form.title} onChange={e=>{const title=e.target.value;setField('title',title);if(slugAuto)setEditing(prev=>({...prev,form:{...prev.form,slug:slugify(title)}}));}} required autoFocus /></div>
      <div className="field-row"><div className="field"><label>Slug</label><input value={form.slug} onChange={e=>setEditing(prev=>({...prev,slugManual:true,form:{...prev.form,slug:slugify(e.target.value)}}))} /><div className="hint">{slugAuto?'Automatically generated from the title.':'Existing permalink is preserved. Edit only if you intentionally want to change it.'}</div></div><div className="field"><label>Publish Date</label><input type="datetime-local" value={form.date} onChange={e=>setField('date',e.target.value)} /></div></div>
      <div className="field"><label>Content</label><RichTextEditor value={form.content} onChange={html=>setField('content',html)} push={push}/></div>
      <div className="field"><label>Excerpt</label><textarea value={form.excerpt} onChange={e=>setField('excerpt',e.target.value)} rows={4} placeholder="Short summary shown on the Insights grid"/></div>
      <div className="field-row"><TaxonomyChecklist label="Categories" items={categories} selected={selectedCat} onChange={ids=>setField('categories',ids)} newValue={newCategory} setNewValue={setNewCategory} onAdd={addCategory}/><TagChecklist items={tags} selected={new Set(form.tags.map(Number))} onChange={ids=>setField('tags',ids)} newValue={newTag} setNewValue={setNewTag} onAdd={addTag}/></div>
      <div className="field"><label>Featured Image</label>{featuredPreview?<div className="featured-preview"><img src={featuredPreview} alt="Featured"/><div className="featured-actions"><label className="btn btn-outline btn-sm">Upload New<input type="file" accept="image/*" hidden onChange={onFeaturedUpload}/></label><button type="button" className="btn btn-outline btn-sm" onClick={openMediaLibrary}>Choose from Media Library</button><button type="button" className="btn btn-danger btn-sm" onClick={()=>{if(window.confirm('Remove the featured image?'))setField('featured_media',0)}}>Remove</button></div></div>:<div className="media-actions-row"><label className="upload-box compact-upload"><input type="file" accept="image/*" onChange={onFeaturedUpload} disabled={uploading}/><span>{uploading?'Uploading…':'Upload a new image'}</span><small>Adds it to the WordPress Media Library.</small></label><button type="button" className="btn btn-outline" onClick={openMediaLibrary}>Choose from Media Library</button></div>}{mediaOpen&&<div className="featured-media-picker"><div className="rich-media-head"><strong>Choose Featured Image</strong><button type="button" className="btn btn-outline btn-sm" onClick={()=>setMediaOpen(false)}>Close</button></div>{mediaLoading?<div className="center-loading"><div className="spinner"/></div>:<div className="rich-media-grid">{mediaItems.map(item=><button type="button" key={item.id} className="rich-media-item" onClick={()=>{setField('featured_media',Number(item.id));setMediaOpen(false);}}><img src={item.media_details?.sizes?.medium?.source_url||item.media_details?.sizes?.thumbnail?.source_url||item.source_url} alt=""/><span>{decodeHtmlEntities(item.title?.rendered||'Image')}</span></button>)}</div>}</div>}</div>
      <div className="field-row"><div className="field"><label>Author</label><select value={form.author} onChange={e=>setField('author',e.target.value)}><option value="">Current user</option>{authors.map(a=><option key={a.id} value={a.id}>{a.name||a.slug}</option>)}</select></div><div className="field"><label>Status</label><select value={form.status} onChange={e=>setField('status',e.target.value)}><option value="draft">Draft</option><option value="pending">Pending Review</option><option value="publish">Published</option><option value="future">Scheduled</option></select></div></div>
      <details className="publishing-settings"><summary>Publishing & Discussion</summary><div className="field-row"><div className="field"><label>Visibility</label><select value={form.visibility} onChange={e=>setField('visibility',e.target.value)}><option value="public">Public</option><option value="private">Private</option><option value="password">Password protected</option></select></div><div className="field"><label>Password</label><input type="password" value={form.password} placeholder={form.passwordSet?'Existing password is set. Enter a new one to replace it.':''} disabled={form.visibility!=='password'} onChange={e=>setField('password',e.target.value)}/></div></div><div className="field-row"><div className="field"><label>Comments</label><select value={form.comment_status} onChange={e=>setField('comment_status',e.target.value)}><option value="open">Allow</option><option value="closed">Disallow</option></select></div><div className="field"><label>Pingbacks</label><select value={form.ping_status} onChange={e=>setField('ping_status',e.target.value)}><option value="open">Allow</option><option value="closed">Disallow</option></select></div></div></details>
      <div className="post-editor-bottom"><button className="btn btn-primary" type="submit" disabled={saving||uploading}>{saving?'Saving…':statusButton}</button></div>
    </form>
  </Modal>;
}

function TaxonomyChecklist({label,items,selected,onChange,newValue,setNewValue,onAdd}){function toggle(id){const n=new Set(selected);n.has(Number(id))?n.delete(Number(id)):n.add(Number(id));onChange([...n]);}return <div className="field"><label>{label}</label><div className="taxonomy-list">{items.map(item=><label key={item.id} className="taxonomy-item"><input type="checkbox" checked={selected.has(Number(item.id))} onChange={()=>toggle(item.id)}/><span>{item.name}</span></label>)}</div><div className="inline-create"><input value={newValue} onChange={e=>setNewValue(e.target.value)} placeholder={`New ${label.toLowerCase().slice(0,-1)}`} onKeyDown={e=>{if(e.key==='Enter'){e.preventDefault();onAdd();}}}/><button type="button" className="btn btn-outline btn-sm" onClick={onAdd}>Add</button></div></div>}
function TagChecklist({items,selected,onChange,newValue,setNewValue,onAdd}){return <TaxonomyChecklist label="Tags" items={items} selected={selected} onChange={onChange} newValue={newValue} setNewValue={setNewValue} onAdd={onAdd}/>}

function formatPostDate(p){const d=new Date(p.date);if(Number.isNaN(d.getTime()))return '—';return `${d.toLocaleDateString()} ${d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}`;}
function stripHtml(html){const div=document.createElement('div');div.innerHTML=html;return div.textContent||'';}
function slugify(value){return String(value||'').toLowerCase().trim().normalize('NFKD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9\s-]/g,'').replace(/\s+/g,'-').replace(/-+/g,'-').replace(/^-|-$/g,'');}
function toSiteDateTime(date,timeZone){if(!date||Number.isNaN(date.getTime()))return '';const parts=new Intl.DateTimeFormat('en-CA',{timeZone,year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hourCycle:'h23'}).formatToParts(date);const map=Object.fromEntries(parts.filter(p=>p.type!=='literal').map(p=>[p.type,p.value]));return `${map.year}-${map.month}-${map.day}T${map.hour}:${map.minute}`;}
function localSiteDateTimeToUtc(value,timeZone){const base=new Date(`${value}:00Z`);let guess=base;for(let i=0;i<2;i++){const parts=new Intl.DateTimeFormat('en-US',{timeZone,year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',second:'2-digit',hourCycle:'h23'}).formatToParts(guess);const map=Object.fromEntries(parts.filter(p=>p.type!=='literal').map(p=>[p.type,p.value]));const rendered=Date.UTC(Number(map.year),Number(map.month)-1,Number(map.day),Number(map.hour),Number(map.minute),Number(map.second));const desired=Date.parse(`${value}:00Z`);guess=new Date(guess.getTime()+(desired-rendered));}return guess.toISOString();}
function isFutureDate(value,timeZone){return new Date(localSiteDateTimeToUtc(value,timeZone)).getTime()>Date.now();}
