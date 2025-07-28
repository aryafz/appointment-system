import { useEffect, useState } from 'react';
import { setSelectedTenant } from '../../utils/tenant';
import { Select, MenuItem } from '@mui/material';

const TenantSelector = () => {
    const [siteId, setSiteId] = useState<string | ''>('');

    useEffect(() => {
        setSelectedTenant(siteId || null);
    }, [siteId]);

    return (
        <Select
            size="small"
            value={siteId}
            onChange={(e) => setSiteId(e.target.value)}
            displayEmpty
        >
            <MenuItem value="">No Site</MenuItem>
            {/* In real app populate from /sites */}
            <MenuItem value="1">Site 1</MenuItem>
            <MenuItem value="2">Site 2</MenuItem>
        </Select>
    );
};

export default TenantSelector;
