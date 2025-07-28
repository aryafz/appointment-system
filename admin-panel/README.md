# Admin Panel

This is a minimal React Admin panel scaffold using Vite, React, TypeScript and react-admin.

## Setup

```bash
npm ci
npm run dev
```

Configure the following environment variables in a `.env` file:

```
VITE_API_URL=<backend-url>
VITE_TOKEN_KEY=access_token
VITE_AUTH_STORAGE_KEY=saas_admin_token
VITE_TENANT_HEADER_NAME=x-tenant-id
```

## Structure

- `src/auth` - authentication provider
- `src/data` - axios client and data provider
- `src/resources` - resource definitions (e.g. Users)
- `src/components/TenantSelector` - simple tenant picker

This project currently implements the **Users** resource as an example.
