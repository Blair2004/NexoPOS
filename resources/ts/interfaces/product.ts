export interface Product {
    id?: number;
    name?: string;
    stock_management?: 'enabled' | 'disabled';
    sale_price?: number;
    excl_tax_sale_price?: number;
    incl_tax_sale_price?: number;
    wholesale_price?: number;
    excl_wholesale_price?: number;
    incl_wholesale_price?: number;
    quantity?: number;
    unit_id?: number;
    product_id?: number;
    discount_type?: string;
    discount_amount?: number;
    discount_percentage?: number;
    total_price?: number;
    mode: 'normal' | 'wholesale';
    $original?: any;
}