import { useEffect, useRef, useState } from 'react';
import { uploadMedia, listItems, ApiError } from './api.js';

const BLOCKS = [
  ['p', 'Paragraph'], ['h2', 'Heading 2'], ['h3', 'Heading 3'], ['h4', 'Heading 4'], ['blockquote', 'Quote'],
];

export default function RichTextEditor({ value, onChange, push }) {
  const editorRef = useRef(null);
  const fileRef = useRef(null);
  const savedRange = useRef(null);
  const selectedImage = useRef(null);
  const [block, setBlock] = useState('p');
  const [mediaOpen, setMediaOpen] = useState(false);
  const [media, setMedia] = useState([]);
  const [mediaLoading, setMediaLoading] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [imageSelected, setImageSelected] = useState(false);
  const [imageWidth, setImageWidth] = useState(100);
  const [imageAlt, setImageAlt] = useState('');
  const [linkOpen, setLinkOpen] = useState(false);
  const [linkUrl, setLinkUrl] = useState('');
  const [linkNewTab, setLinkNewTab] = useState(true);
  const [ctaOpen, setCtaOpen] = useState(false);
  const [ctaText, setCtaText] = useState('Learn More');
  const [ctaUrl, setCtaUrl] = useState('');
  const [ctaNewTab, setCtaNewTab] = useState(true);

  useEffect(() => {
    if (!editorRef.current) return;
    if (value !== editorRef.current.innerHTML) editorRef.current.innerHTML = value || '';
  }, [value]);

  useEffect(() => {
    const updateBlock = () => {
      const editor = editorRef.current;
      const sel = window.getSelection();
      if (!editor || !sel?.rangeCount || !editor.contains(sel.anchorNode)) return;
      let node = sel.anchorNode?.nodeType === 3 ? sel.anchorNode.parentElement : sel.anchorNode;
      const blockNode = node?.closest?.('h2,h3,h4,blockquote,p,div,li');
      const tag = blockNode?.tagName?.toLowerCase();
      setBlock(BLOCKS.some(([v]) => v === tag) ? tag : 'p');
    };
    document.addEventListener('selectionchange', updateBlock);
    return () => document.removeEventListener('selectionchange', updateBlock);
  }, []);

  const saveSelection = () => {
    const sel = window.getSelection();
    if (!sel?.rangeCount || !editorRef.current?.contains(sel.anchorNode)) return;
    savedRange.current = sel.getRangeAt(0).cloneRange();
  };

  const restoreSelection = () => {
    const editor = editorRef.current;
    if (!editor) return;
    editor.focus();
    if (!savedRange.current) return;
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(savedRange.current);
  };

  const emit = () => onChange(editorRef.current?.innerHTML || '');

  const command = (name, arg = null) => {
    restoreSelection();
    document.execCommand(name, false, arg);
    saveSelection();
    emit();
  };

  const applyBlock = (tag) => {
    restoreSelection();
    document.execCommand('formatBlock', false, tag);
    saveSelection();
    emit();
    setBlock(tag);
  };

  const openLink = () => {
    saveSelection();
    const sel = window.getSelection();
    const anchor = sel?.anchorNode?.parentElement?.closest?.('a');
    setLinkUrl(anchor?.getAttribute('href') || '');
    setLinkNewTab(anchor?.getAttribute('target') === '_blank');
    setLinkOpen(true);
  };

  const insertLink = () => {
    const url = normalizeUrl(linkUrl);
    if (!url) return;
    restoreSelection();
    document.execCommand('createLink', false, url);
    const sel = window.getSelection();
    const anchor = sel?.anchorNode?.parentElement?.closest?.('a');
    if (anchor) {
      if (linkNewTab) { anchor.target = '_blank'; anchor.rel = 'noopener'; }
      else { anchor.removeAttribute('target'); anchor.removeAttribute('rel'); }
    }
    saveSelection();
    emit();
    setLinkOpen(false);
  };

  const openCta = () => { saveSelection(); setCtaOpen(true); };

  const insertCta = () => {
    const url = normalizeUrl(ctaUrl);
    const text = ctaText.trim();
    if (!url || !text) return;
    restoreSelection();
    const target = ctaNewTab ? ' target="_blank" rel="noopener"' : '';
    document.execCommand('insertHTML', false, `<p><a href="${esc(url)}" class="dahim-cta-button"${target}>${esc(text)}</a></p>`);
    saveSelection();
    emit();
    setCtaOpen(false);
  };

  const insertMedia = async (item) => {
    restoreSelection();
    const src = item.source_url || item.guid?.rendered;
    if (!src) return;
    const alt = item.alt_text || strip(item.caption?.rendered || '') || strip(item.title?.rendered || '');
    const width = item.media_details?.width || 1200;
    const height = item.media_details?.height || '';
    const html = `<p class="dahim-image-wrap aligncenter"><img src="${esc(src)}" alt="${esc(alt)}" class="dahim-editor-image aligncenter" data-attachment-id="${Number(item.id)}" data-width="100" width="${Number(width)}"${height ? ` height="${Number(height)}"` : ''} style="width:100%;height:auto;"></p>`;
    document.execCommand('insertHTML', false, html);
    saveSelection();
    emit();
    setMediaOpen(false);
  };

  const uploadAndInsert = async (file) => {
    if (!file) return;
    if (!file.type.startsWith('image/')) { push?.('Please select an image file.', 'error'); return; }
    setUploading(true);
    try {
      const item = await uploadMedia(file, { title: file.name.replace(/\.[^.]+$/, ''), alt_text: '' });
      await insertMedia(item);
      push?.('Image inserted into the article.', 'success');
    } catch (err) { push?.(err instanceof ApiError ? err.message : 'Could not insert image.', 'error'); }
    finally { setUploading(false); if (fileRef.current) fileRef.current.value = ''; }
  };

  const openMedia = async () => {
    saveSelection();
    setMediaOpen(true);
    setMediaLoading(true);
    try { setMedia(await listItems('media', { per_page: 60, media_type: 'image', orderby: 'date', order: 'desc' })); }
    catch (err) { push?.(err instanceof ApiError ? err.message : 'Could not load Media Library.', 'error'); }
    finally { setMediaLoading(false); }
  };

  const selectImage = (img) => {
    selectedImage.current = img;
    setImageSelected(Boolean(img));
    setImageWidth(Number(img.dataset.width || parseInt(img.style.width, 10) || 100));
    setImageAlt(img.getAttribute('alt') || '');
  };

  const updateImage = () => {
    const img = selectedImage.current;
    if (!img) return;
    const width = Math.max(10, Math.min(100, Number(imageWidth || 100)));
    const alt = imageAlt.trim();
    img.dataset.width = String(width);
    img.style.width = `${width}%`;
    img.style.maxWidth = '100%';
    img.style.height = 'auto';
    img.setAttribute('alt', alt);
    const wrapper = img.closest('.dahim-image-wrap');
    const align = wrapper?.classList.contains('alignleft') ? 'left' : wrapper?.classList.contains('alignright') ? 'right' : 'center';
    setImageAlignment(align, false);
    emit();
  };

  const setImageAlignment = (align, shouldEmit = true) => {
    const img = selectedImage.current;
    if (!img) return;
    const wrapper = img.closest('.dahim-image-wrap');
    if (wrapper) {
      wrapper.classList.remove('alignleft', 'aligncenter', 'alignright');
      wrapper.classList.add(`align${align}`);
    }
    img.classList.remove('alignleft', 'aligncenter', 'alignright');
    img.classList.add(`align${align}`);
    if (shouldEmit) emit();
  };

  const removeImage = () => {
    const img = selectedImage.current;
    if (!img) return;
    const wrapper = img.closest('.dahim-image-wrap');
    if (wrapper && wrapper.children.length === 1) wrapper.remove();
    else img.remove();
    selectedImage.current = null;
    setImageSelected(false);
    emit();
  };

  const toolbarButton = (label, action) => (
    <button type="button" title={label} onMouseDown={() => saveSelection()} onClick={action}>{label}</button>
  );

  return <div className="rich-editor">
    <div className="rich-toolbar" role="toolbar" aria-label="Article editor toolbar">
      <select value={block} onMouseDown={saveSelection} onChange={e => applyBlock(e.target.value)} aria-label="Text style">
        {BLOCKS.map(([v, l]) => <option key={v} value={v}>{l}</option>)}
      </select>
      <span className="rich-divider" />
      {toolbarButton('Bold', () => command('bold'))}
      {toolbarButton('Italic', () => command('italic'))}
      {toolbarButton('Underline', () => command('underline'))}
      {toolbarButton('Strikethrough', () => command('strikeThrough'))}
      <span className="rich-divider" />
      {toolbarButton('Bulleted list', () => command('insertUnorderedList'))}
      {toolbarButton('Numbered list', () => command('insertOrderedList'))}
      {toolbarButton('Outdent', () => command('outdent'))}
      {toolbarButton('Indent', () => command('indent'))}
      {toolbarButton('Horizontal rule', () => command('insertHorizontalRule'))}
      <span className="rich-divider" />
      {toolbarButton('Align left', () => command('justifyLeft'))}
      {toolbarButton('Align center', () => command('justifyCenter'))}
      {toolbarButton('Align right', () => command('justifyRight'))}
      <span className="rich-divider" />
      {toolbarButton('Link', openLink)}
      {toolbarButton('Unlink', () => command('unlink'))}
      <button type="button" title="Insert image" onMouseDown={() => saveSelection()} onClick={openMedia}>Image</button>
      {toolbarButton('Call to action', openCta)}
      {toolbarButton('Text color', () => { saveSelection(); const c = window.prompt('Text color (hex)', '#1E2A44'); if (c) command('foreColor', c); })}
      <span className="rich-divider" />
      {toolbarButton('Undo', () => command('undo'))}
      {toolbarButton('Redo', () => command('redo'))}
      {toolbarButton('Clear formatting', () => command('removeFormat'))}
      <input ref={fileRef} type="file" accept="image/*" hidden onChange={e => uploadAndInsert(e.target.files?.[0])} />
    </div>

    {mediaOpen && <div className="rich-media-panel">
      <div className="rich-media-head"><strong>Insert from Media Library</strong><div><button type="button" className="btn btn-outline btn-sm" onClick={() => fileRef.current?.click()} disabled={uploading}>{uploading ? 'Uploading…' : 'Upload New'}</button><button type="button" className="btn btn-outline btn-sm" onClick={() => setMediaOpen(false)}>Close</button></div></div>
      {mediaLoading ? <div className="center-loading"><div className="spinner" /></div> : <div className="rich-media-grid">{media.map(item => <button type="button" key={item.id} className="rich-media-item" onClick={() => insertMedia(item)}><img src={item.media_details?.sizes?.medium?.source_url || item.media_details?.sizes?.thumbnail?.source_url || item.source_url} alt="" /><span>{strip(item.title?.rendered || 'Image')}</span></button>)}</div>}
    </div>}

    {imageSelected && <div className="rich-image-tools" aria-label="Selected image controls">
      <strong>Image</strong>
      <label>Width <input type="number" min="10" max="100" value={imageWidth} onChange={e => setImageWidth(e.target.value)} onBlur={updateImage} /> %</label>
      <label>Alt <input className="image-alt-input" value={imageAlt} onChange={e => setImageAlt(e.target.value)} onBlur={updateImage} placeholder="Describe the image" /></label>
      <button type="button" onClick={() => setImageAlignment('left')}>Left</button>
      <button type="button" onClick={() => setImageAlignment('center')}>Center</button>
      <button type="button" onClick={() => setImageAlignment('right')}>Right</button>
      <button type="button" className="btn-danger-lite" onClick={removeImage}>Remove</button>
    </div>}

    <div ref={editorRef} className="rich-editor-content" contentEditable suppressContentEditableWarning onInput={emit} onKeyUp={saveSelection} onMouseUp={saveSelection} onBlur={saveSelection} onClick={e => { const img = e.target.closest?.('img'); selectImage(img || null); }} onKeyDown={e => { if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); openLink(); } }} aria-label="Article content" />
    <div className="rich-editor-footer"><span>WordPress-compatible rich HTML</span><span>Use headings, formatting, lists, links, images and CTA buttons.</span></div>

    {linkOpen && <div className="rich-dialog-overlay" onClick={() => setLinkOpen(false)}><div className="rich-dialog" onClick={e => e.stopPropagation()}><h3>Insert Link</h3><label>URL<input autoFocus value={linkUrl} onChange={e => setLinkUrl(e.target.value)} onKeyDown={e => e.key === 'Enter' && insertLink()} placeholder="https://example.com" /></label><label className="checkbox-field"><input type="checkbox" checked={linkNewTab} onChange={e => setLinkNewTab(e.target.checked)} /> Open in new tab</label><div className="rich-dialog-actions"><button type="button" className="btn btn-outline" onClick={() => setLinkOpen(false)}>Cancel</button><button type="button" className="btn btn-primary" onClick={insertLink}>Insert Link</button></div></div></div>}
    {ctaOpen && <div className="rich-dialog-overlay" onClick={() => setCtaOpen(false)}><div className="rich-dialog" onClick={e => e.stopPropagation()}><h3>Insert Call to Action</h3><label>Button text<input autoFocus value={ctaText} onChange={e => setCtaText(e.target.value)} /></label><label>Button URL<input value={ctaUrl} onChange={e => setCtaUrl(e.target.value)} placeholder="https://example.com" /></label><label className="checkbox-field"><input type="checkbox" checked={ctaNewTab} onChange={e => setCtaNewTab(e.target.checked)} /> Open in new tab</label><div className="rich-dialog-actions"><button type="button" className="btn btn-outline" onClick={() => setCtaOpen(false)}>Cancel</button><button type="button" className="btn btn-primary" onClick={insertCta}>Insert CTA</button></div></div></div>}
  </div>;
}

function strip(html) { const d = document.createElement('div'); d.innerHTML = html; return d.textContent || ''; }
function esc(value) { return String(value || '').replace(/[&<>"']/g, c => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' }[c])); }
function normalizeUrl(value) { const v = String(value || '').trim(); if (!v) return ''; return /^(https?:|mailto:|tel:|#)/i.test(v) ? v : `https://${v}`; }
