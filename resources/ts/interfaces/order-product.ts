import { Product } from "./product";
import { ProductUnitQuantity } from "./product-unit-quantity";

export interface OrderProduct extends Product {
    tax_value: number;
    tax_group_id: number;
    unit_price: number;
    total_price: number;
    quantity: number;
    plate?: string;
    product?: Product;
    $quantities?: () => ProductUnitQuantity
}