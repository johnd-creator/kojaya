export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type PaginationMeta = {
    current_page: number;
    from: number | null;
    last_page: number;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
    links: PaginationLink[];
};

export type PaginationUrls = {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
};

export type PaginatedResource<T> = {
    data: T[];
    links: PaginationUrls;
    meta: PaginationMeta;
};
