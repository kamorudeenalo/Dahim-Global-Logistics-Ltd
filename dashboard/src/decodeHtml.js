// WordPress's REST API returns "rendered" title/content strings already
// HTML-entity-encoded (e.g. "Sales &#038; Shipping Quotes"). React treats
// plain string children as literal text, not HTML, so entities show up
// as-is instead of decoding to the character they represent. This decodes
// them back to plain text for safe display, and — just as importantly —
// for safe reuse as an editable form value (feeding an already-encoded
// string back into a title field and saving it again double-encodes it
// a little more each time).
export function decodeHtml(str) {
  if (!str) return str;
  const el = document.createElement('textarea');
  el.innerHTML = str;
  return el.value;
}
