import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
  // The dashboard is deployed inside the WordPress site at /dashboard/.
  base: '/dashboard/',
  plugins: [
    react(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['icons/icon-192.png', 'icons/icon-512.png'],
      manifest: {
        name: 'Dahim Dashboard',
        short_name: 'Dahim',
        description: 'Manage shipments, inquiries, jobs, and content for Dahim Global Logistics.',
        theme_color: '#1E2A44',
        background_color: '#FFFFFF',
        display: 'standalone',
        start_url: '/dashboard/',
        scope: '/dashboard/',
        icons: [
          { src: '/dashboard/icons/icon-192.png', sizes: '192x192', type: 'image/png', purpose: 'any' },
          { src: '/dashboard/icons/icon-512.png', sizes: '512x512', type: 'image/png', purpose: 'any' },
          { src: '/dashboard/icons/icon-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' }
        ]
      },
      workbox: {
        runtimeCaching: [
          {
            urlPattern: ({ url }) => url.pathname.includes('/wp-json/'),
            handler: 'NetworkOnly'
          }
        ]
      }
    })
  ],
  build: {
    outDir: 'dist',
    sourcemap: false
  }
});
