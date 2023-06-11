export interface Address {
    name: string;
    surname: string;
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