export interface Address {
    first_name: string;
    last_name: string;
    address_1: string;
    address_2: string;
    email: string;
    city: string;
    country: string;
    company: string;
    pobox: string;
    phone: string;
    type?: 'billing' | 'shipping'
}