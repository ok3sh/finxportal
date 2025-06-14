import axios from 'axios';

// Using Vite proxy - no need for absolute baseURL
// All /api and /auth requests will be proxied to Laravel backend
axios.defaults.withCredentials = true;

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
