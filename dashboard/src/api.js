/**
 * Dahim Dashboard — WordPress REST API client.
 *
 * Auth model: standard WordPress cookie + nonce. The dashboard is hosted on
 * a separate subdomain, so requests are explicitly sent to the WordPress
 * site's origin with credentials included. The browser's WordPress cookie
 * remains the session; the REST nonce lives only in memory.
 */

let currentNonce = null;
let currentUser = null;

class ApiError extends Error {
  constructor(message, status) {
    super(message);
    this.status = status;
  }
}

// WordPress owns the API. Keep the URL configurable for production while
// providing the staging URL as the safe default for the current deployment.
const WORDPRESS_ORIGIN = (
  import.meta.env.VITE_WP_API_URL ||
  'https://staging.technophilesdigital.com'
).replace(/\/$/, '');

function siteBaseUrl() {
  return WORDPRESS_ORIGIN;
}

/**
 * Core request helper. `path` is relative to /wp-json/, e.g. "wp/v2/shipments".
 */
async function requestDetailed(path, { method = 'GET', body, skipAuth = false } = {}) {
  if (!skipAuth && !currentNonce) throw new ApiError('Not signed in.', 401);
  const url = `${siteBaseUrl()}/wp-json/${path}`;
  const headers = {};
  if (body !== undefined) headers['Content-Type'] = 'application/json';
  if (!skipAuth) headers['X-WP-Nonce'] = currentNonce;
  let res;
  try {
    res = await fetch(url, {
      method,
      headers,
      // The dashboard and WordPress are different origins but the same site.
      // `include` is required for the browser to send/receive the WordPress
      // authentication cookie across the dashboard subdomain boundary.
      credentials: 'include',
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
  } catch {
    throw new ApiError('Could not reach the site. Check your connection.', 0);
  }
  if (!skipAuth && res.status === 401) {
    currentNonce = null;
    currentUser = null;
    window.dispatchEvent(new Event('dahim:unauthorized'));
    throw new ApiError('Your session has expired. Please sign in again.', 401);
  }
  if (!skipAuth && res.status === 403) {
    throw new ApiError("You don't have permission to do this. This may need an Administrator account.", 403);
  }
  const rawText = await res.text();
  let data = null;
  let parseFailed = false;
  try {
    data = rawText ? JSON.parse(rawText) : null;
  } catch {
    parseFailed = true;
  }
  if (!res.ok) {
    throw new ApiError((data && data.message) || `Request failed (${res.status}).`, res.status);
  }
  if (parseFailed) {
    console.error('Dashboard: non-JSON response from', url, '\n', rawText.slice(0, 500));
    throw new ApiError('The site returned an unexpected response. Check the browser console or PHP error log.', res.status);
  }
  return {
    data,
    total: Number(res.headers.get('X-WP-Total') || 0),
    totalPages: Number(res.headers.get('X-WP-TotalPages') || 0),
  };
}

async function request(path, options = {}) {
  const result = await requestDetailed(path, options);
  return result.data;
}

// Signs in with a real WordPress username/password — sets the actual
// WordPress login cookie (via wp_signon() server-side), and then obtains a
// fresh REST nonce using a genuinely separate request.
export async function login(username, password) {
  await request('dahim/v1/auth/login', {
    method: 'POST',
    body: { username, password },
    skipAuth: true,
  });

  // Do not use the nonce returned by the login response. A separate request
  // is required so PHP sees the newly established authentication cookie.
  const result = await request('dahim/v1/auth/me', { skipAuth: true });
  currentNonce = result.nonce;
  currentUser = result.user;
  return result.user;
}

// Called once when the app first loads to find out whether the browser's
// existing WordPress cookie (from a previous visit) is still valid, and to
// get a fresh nonce for this session. Throws if not signed in.
export async function restoreSession() {
  const result = await request('dahim/v1/auth/me', { skipAuth: true });
  currentNonce = result.nonce;
  currentUser = result.user;
  return result.user;
}

export function getCurrentUser() {
  return currentUser;
}

export async function logout() {
  try {
    // A valid REST nonce makes logout a same-session action, preventing a
    // third-party page from silently signing a dashboard user out.
    await request('dahim/v1/auth/logout', { method: 'POST' });
  } catch {
    // Best-effort — local state clears regardless.
  }
  currentNonce = null;
  currentUser = null;
}

// "Forgot password" — sends a branded reset link (WordPress's own secure
// reset-key mechanism under the hood) to the account's email.
export async function forgotPassword(usernameOrEmail) {
  return request('dahim/v1/auth/forgot-password', {
    method: 'POST',
    body: { username: usernameOrEmail },
    skipAuth: true,
  });
}

// Completes the reset — username/key come from the link in the reset email.
export async function resetPassword(username, key, newPassword) {
  return request('dahim/v1/auth/reset-password', {
    method: 'POST',
    body: { username, key, password: newPassword },
    skipAuth: true,
  });
}

// Self-registration — creates a new account with no dashboard access
// until an admin manually assigns it a role from wp-admin.
export async function registerAccount(username, email, password) {
  return request('dahim/v1/auth/register', {
    method: 'POST',
    body: { username, email, password },
    skipAuth: true,
  });
}

// --- Generic post-type CRUD (Shipments, Inquiries, Trade Lanes, Departments, Jobs, Posts) ---

export async function listItems(restBase, params = {}) {
  const result = await listItemsPaged(restBase, params);
  return result.items;
}

export async function listItemsPaged(restBase, params = {}) {
  const merged = { per_page: '20', orderby: 'date', order: 'desc', ...params };
  const qs = new URLSearchParams();
  Object.entries(merged).forEach(([key, value]) => {
    if (Array.isArray(value)) value.forEach(v => qs.append(`${key}[]`, v));
    else if (value !== undefined && value !== null && value !== '') qs.append(key, value);
  });
  const result = await requestDetailed(`wp/v2/${restBase}?${qs.toString()}`);
  if (!Array.isArray(result.data)) throw new ApiError(`Expected a list of ${restBase}.`, 0);
  return {
    items: result.data,
    total: result.total || result.data.length,
    totalPages: result.totalPages || 1,
  };
}

export async function getSiteSettings() {
  return request('wp/v2/settings?context=edit');
}

export function getItem(restBase, id) {
  return request(`wp/v2/${restBase}/${id}?context=edit`);
}

export function createItem(restBase, payload) {
  return request(`wp/v2/${restBase}`, { method: 'POST', body: payload });
}

export function updateItem(restBase, id, payload) {
  return request(`wp/v2/${restBase}/${id}`, { method: 'POST', body: payload });
}

export function deleteItem(restBase, id) {
  return request(`wp/v2/${restBase}/${id}?force=true`, { method: 'DELETE' });
}

// Upload an image to the WordPress Media Library. The REST media endpoint
// expects multipart/form-data, so this intentionally does not use the JSON
// request helper above (and must not set Content-Type manually).
export async function uploadMedia(file, { title = '', alt_text = '' } = {}) {
  if (!currentNonce) throw new ApiError('Not signed in.', 401);
  const form = new FormData();
  form.append('file', file);
  if (title) form.append('title', title);
  if (alt_text) form.append('alt_text', alt_text);

  const url = `${siteBaseUrl()}/wp-json/wp/v2/media`;
  let res;
  try {
    res = await fetch(url, {
      method: 'POST',
      headers: { 'X-WP-Nonce': currentNonce },
      credentials: 'include',
      body: form,
    });
  } catch {
    throw new ApiError('Could not upload the image. Check your connection.', 0);
  }

  if (res.status === 401) {
    currentNonce = null;
    currentUser = null;
    window.dispatchEvent(new Event('dahim:unauthorized'));
    throw new ApiError('Your session has expired. Please sign in again.', 401);
  }
  if (res.status === 403) {
    throw new ApiError("You don't have permission to upload media. This may need an Administrator account.", 403);
  }

  const rawText = await res.text();
  let data = null;
  try {
    data = rawText ? JSON.parse(rawText) : null;
  } catch {}
  if (!res.ok) {
    throw new ApiError((data && data.message) || `Image upload failed (${res.status}).`, res.status);
  }
  return data;
}

export function listCategories(params = {}) {
  return request(`wp/v2/categories?${new URLSearchParams({ per_page: '100', hide_empty: 'false', orderby: 'name', order: 'asc', ...params }).toString()}`);
}

export function listTags(params = {}) {
  return request(`wp/v2/tags?${new URLSearchParams({ per_page: '100', hide_empty: 'false', orderby: 'name', order: 'asc', ...params }).toString()}`);
}

export function createCategory(name) {
  return createItem('categories', { name });
}

export function createTag(name) {
  return createItem('tags', { name });
}

// --- Contact settings (custom endpoint, not a post type) ---

export function getContactSettings() {
  return request('dahim/v1/contact-settings');
}

export function updateContactSettings(payload) {
  return request('dahim/v1/contact-settings', { method: 'POST', body: payload });
}

export { ApiError };
