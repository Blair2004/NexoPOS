export interface Register {
    id?: number;
    name?: string;
    status?: 'opened' | 'closed' | 'disabled' | 'in-use';
    description?: string;
    used_by?: number;
    author?: string;
    balance?: number;
    status_label?: string,
    opening_balance?: number,
    total_sale_amount?: number,
}
