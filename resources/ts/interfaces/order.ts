import { Address } from "cluster";
import { Customer } from "./customer";
import { OrderType } from "./order-type";
import { Payment } from "./payment";
import { Product } from "./product";

export interface Order {
    discount_type: 'flat' | 'percentage';
    discount: number;
    discount_percentage: number;
    subtotal: number;
    total: number;
    paid: number;
    change: number;
    total_products: number;
    customer: Customer | undefined;
    type: OrderType,
    customer_id: number;
    tax_value: number;
    shipping: number;
    shipping_rate: number;
    shipping_type: 'flat' | 'percentage';
    products: Product[], 
    payments: Payment[],
    addresses: {
        shipping: Address,
        billing: Address,
    }
}
