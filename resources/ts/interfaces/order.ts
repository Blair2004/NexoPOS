import { Customer } from "./customer";
import { OrderType } from "./order-type";
import { Payment } from "./payment";
import { Product } from "./product";

export interface Order {
    discount_type: 'flat' | 'percentage';
    discount_amount: number;
    discount_percentage: number;
    subtotal: number;
    total: number;
    paid: number;
    change: number;
    total_products: number;
    customer: Customer | undefined;
    type: OrderType,
    products: Product[], 
    payments: Payment[],
}
