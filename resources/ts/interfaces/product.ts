export interface Product {
    id?: number;
    name?: string;
    stock_management?: 'enabled' | 'disabled';
    unit_id?: number;
    product_id?: number;
    discount_type?: string;
    discount?: number;
    discount_percentage?: number;
    total_price?: number;
    mode: 'normal' | 'wholesale' | 'custom';
    $original?: any;
    unit_quantity_id?: number;
    unit_quantities?: any
}