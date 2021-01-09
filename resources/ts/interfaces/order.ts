import { Address } from "cluster";
import { Customer } from "./customer";
import { OrderProduct } from "./order-product";
import { OrderType } from "./order-type";
import { Payment } from "./payment";
import { Product } from "./product";

export interface Order {
    id?: number;
    discount_type: 'flat' | 'percentage';
    discount: number;
    title: string;
    discount_percentage: number;
    subtotal: number;
    register_id: number | undefined;
    total: number;
    tendered: number;
    payment_status: 'hold' | 'paid' | 'unpaid' | 'partially_paid' | 'layaway';
    change: number;
    total_products: number;
    customer: Customer | undefined;
    type: OrderType,
    customer_id: number;
    tax_value: number;
    shipping: number;
    shipping_rate: number;
    shipping_type: 'flat' | 'percentage';
    products: OrderProduct[], 
    payments: Payment[],
    expected_payment_date?: string;
    total_installments?: number;
    addresses: {
        shipping: Address,
        billing: Address,
    }
}
