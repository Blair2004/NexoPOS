import { Customer } from "./customer";
import { OrderType } from "./order-type";
import { Product } from "./product";

export interface Order {
    discount_type: 'flat' | 'percentage';
    discount_amount: number;
    discount_percentage: number;
    subtotal: number;
    total: number;
    total_products: number;
    customer: Customer | undefined;
    type: OrderType,
    products: Product[]
}
