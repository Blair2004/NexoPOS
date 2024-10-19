import { Address } from "cluster";
import { Customer } from "./customer";
import { OrderProduct } from "./order-product";
import { OrderType } from "./order-type";
import { Payment } from "./payment";

export interface Order {
    id?: number;
    discount_type: 'flat' | 'percentage';
    discount: number;
    discount_percentage: number;
    register_id: number | undefined;
    total: number;
    tendered: number;
    payment_status: 'hold' | 'paid' | 'unpaid' | 'partially_paid' | 'layaway';
    change: number;
    total_products: number;
    customer: Customer | undefined;
    coupons: {
        code?: string;
        type?: 'percentage_discount' | 'flat_discount';
        name: string;
        customer_coupon_id?: number;
        limit_usage?: number;
        minimum_cart_value?: number;
        maximum_cart_value?: number;
        customer_id?: number;
        value?: number;
        created_at?: string;
        usage?: number;
        active?: number;
        author?: number;
        products?: {
            product_id?: number;
            coupon_id?: number;
            product?: {
                name?: string;
                id?: number;
            }
        }[];
        categories?: {
            category_id?: number;
            coupon_id?: number;
            category?: {
                name?: string;
                id?: number;
            }
        }[]
        discount_value?: number;
    }[];
    total_coupons: number;
    type: OrderType,
    customer_id: number;
    products: OrderProduct[], 
    payments: Payment[],
    instalments?: { date: string, amount: number, paid?: boolean }[],
    note: string;
    note_visibility: 'hidden' | 'visible';
    tax_group_id: number,
    tax_type: 'inclusive' | 'exclusive'
    taxes: any[],
    final_payment_date?: string;
    total_instalments?: number;
    addresses: {
        shipping: Address,
        billing: Address,
    };
    tax_value: number;
    products_tax_value: number;
    total_tax_value: number;
    tax_groups: any[],
    shipping: number;
    shipping_rate: number;
    shipping_type: 'flat' | 'percentage';
    subtotal: number;
    title: string;
}
