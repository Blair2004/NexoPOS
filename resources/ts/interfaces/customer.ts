export interface Customer {
    first_name: string;
    id: number;
    owed_amount?: number;
    purchases_amount?: number;
    account_amount?: number;
    email?: string;
    phone?: string;
    gender?: string;
    description?: string;
    last_name?: string;
    pobox?: string;
    group_id?: number;
    coupons?: {
        name: string;
        discount_type: 'percentage_discount' | 'flat_discount';
        discount_value: number;
    }[]
    group?: {
        name?: string;
        id?: number;
        minimal_credit_payment?: number;
        reward_system_id?: number;
        description?: string;
    };
}