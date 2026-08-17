// WordPress's REST API deliberately returns title/content "rendered" fields
// (and anything built from a post title server-side, like our Inquiry
// "{name} — {department}" titles) with HTML entities intact — e.g. "&#038;"
// for "&" — since that's meant to be inserted into real HTML, where a
// browser decodes it automatically. React text nodes don't do that
// (by design, to prevent XSS), so anywhere a rendered/title string is shown
// as plain text, run it through this first or entities show up literally.
export function decodeHtmlEntities(str) {
  if (!str) return str;
  const el = document.createElement('textarea');
  el.innerHTML = str;
  return el.value;
}
