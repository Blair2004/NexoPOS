import { Product } from "./product";
import { ProductUnitQuantity } from "./product-unit-quantity";

export interface OrderProduct extends Product {
    tax_value: number;
    tax_group_id: number;
    tax_type: string | undefined;
    unit_id: number;
    unit_name: string | undefined;
    unit_price: number;    
    total_price: number;
    quantity: number;
    product?: Product;
    $quantities?: () => ProductUnitQuantity
}