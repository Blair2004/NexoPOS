export interface Customer {
    name: string;
    id: number;
    owed_amount?: number;
    purchases_amount?: number;
    account_amount?: number;
    email?: string;
    phone?: string;
    gender?: string;
    description?: string;
    surname?: string;
    pobox?: string;
    group_id?: number;
    group?: {
        name?: string;
        id?: number;
        minimal_credit_payment?: number;
        reward_system_id?: number;
        description?: string;
    };
}