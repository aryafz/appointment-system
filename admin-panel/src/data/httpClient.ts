import axios from 'axios';
import { getSelectedTenant } from '../utils/tenant';

const apiUrl = import.meta.env.VITE_API_URL;
const tenantHeader = import.meta.env.VITE_TENANT_HEADER_NAME || 'x-tenant-id';

const http = axios.create({ baseURL: apiUrl });

http.interceptors.request.use(config => {
    const token = localStorage.getItem(import.meta.env.VITE_AUTH_STORAGE_KEY || 'saas_admin_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    const tenant = getSelectedTenant();
    if (tenant) {
        config.headers[tenantHeader] = tenant;
    }
    return config;
});

export default http;
