// resources/js/types/organization.ts
export interface Organization {
    id: string;
    parent_id: string | null;
    code: string;
    name: string;
    level: 'L0' | 'L1' | 'L2' | 'L3';
    type: 'HEAD_OFFICE' | 'REGIONAL' | 'BRANCH' | 'SITE';
    address: string | null;
    phone: string | null;
    email: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    parent?: Organization;
    children?: Organization[];
}
