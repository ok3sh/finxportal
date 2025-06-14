import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import fs from 'fs';

export default defineConfig({
    plugins: [
        react(),
    ],
    server: {
        https: {
            key: fs.readFileSync('./certs/localhost+2-key.pem'),
            cert: fs.readFileSync('./certs/localhost+2.pem'),
        },
        port: 3000,
        host: 'localhost',
        cors: true,
        proxy: {
            // Proxy all /api requests to Laravel backend
            '/api': {
                target: 'http://localhost:8000',
                changeOrigin: true,
                secure: false, // Allow HTTP backend from HTTPS frontend
                configure: (proxy, options) => {
                    proxy.on('error', (err, req, res) => {
                        console.log('Proxy error:', err);
                    });
                }
            },
            // Proxy authentication requests
            '/auth': {
                target: 'http://localhost:8000',
                changeOrigin: true,
                secure: false,
            }
        }
    },
});
