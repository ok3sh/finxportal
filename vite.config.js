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
    },
});
