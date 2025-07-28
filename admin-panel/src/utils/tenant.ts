let tenant: string | null = null;

export function setSelectedTenant(id: string | null) {
    tenant = id;
}

export function getSelectedTenant() {
    return tenant;
}
