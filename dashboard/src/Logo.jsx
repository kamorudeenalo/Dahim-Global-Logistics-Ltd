import { useState } from 'react';

/**
 * The real Dahim logo — since the dashboard now lives on the same domain
 * as the WordPress site, it's referenced directly from the theme's own
 * assets (same-origin, no CORS concerns). Falls back to the styled text
 * wordmark if the image ever fails to load, so branding never just
 * disappears.
 */
export default function Logo({ height = 30 }) {
  const [failed, setFailed] = useState(false);
  const src = `${window.location.origin}/wp-content/themes/dahim/assets/images/dahim-logo.webp`;

  if (failed) {
    return (
      <span className="brand" style={{ padding: 0 }}>
        <span className="dot" /> DAHIM
      </span>
    );
  }

  return (
    <img
      src={src}
      alt="Dahim Global Logistics"
      style={{ height, width: 'auto', display: 'block' }}
      onError={() => setFailed(true)}
    />
  );
}
