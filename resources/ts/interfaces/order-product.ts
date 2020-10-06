import { Product } from "./product";

export interface OrderProduct extends Product {
    tax_value: number;
    tax_group_id: number;
}