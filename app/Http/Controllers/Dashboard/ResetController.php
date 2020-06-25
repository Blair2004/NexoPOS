<?php
namespace App\Http\Controllers\Dashboard;

use Illuminate\Support\Facades\DB;


class ResetController extends Controller
{
    public function truncateAllTables()
    {
        DB::statement( 'TRUNCATE `nexopos_customers_groups`' );
        DB::statement( 'TRUNCATE `nexopos_customers_metas`' );
        DB::statement( 'TRUNCATE `nexopos_customers`' );
        DB::statement( 'TRUNCATE `nexopos_customers_addresses`' );
        DB::statement( 'TRUNCATE `nexopos_expenses_categories`' );
        DB::statement( 'TRUNCATE `nexopos_expenses`' );
        DB::statement( 'TRUNCATE `nexopos_orders_coupons`' );
        DB::statement( 'TRUNCATE `nexopos_orders_metas`' );
        DB::statement( 'TRUNCATE `nexopos_orders_payments`' );
        DB::statement( 'TRUNCATE `nexopos_orders_products`' );
        DB::statement( 'TRUNCATE `nexopos_orders`' );
        DB::statement( 'TRUNCATE `nexopos_procurements_products`' );
        DB::statement( 'TRUNCATE `nexopos_procurements`' );
        DB::statement( 'TRUNCATE `nexopos_products_categories`' );
        DB::statement( 'TRUNCATE `nexopos_products_history`' );
        DB::statement( 'TRUNCATE `nexopos_products_metas`' );
        DB::statement( 'TRUNCATE `nexopos_products`' );
        DB::statement( 'TRUNCATE `nexopos_products_taxes`' );
        // DB::statement( 'TRUNCATE `nexopos_products_variations`' );
        DB::statement( 'TRUNCATE `nexopos_providers`' );
        DB::statement( 'TRUNCATE `nexopos_registers_history`' );
        DB::statement( 'TRUNCATE `nexopos_registers`' );
        DB::statement( 'TRUNCATE `nexopos_rewards_system_rules`' );
        DB::statement( 'TRUNCATE `nexopos_rewards_system`' );
        DB::statement( 'TRUNCATE `nexopos_stores`' );
        DB::statement( 'TRUNCATE `nexopos_taxes`' );
        DB::statement( 'TRUNCATE `nexopos_transfers_products`' );
        DB::statement( 'TRUNCATE `nexopos_transfers`' );
        DB::statement( 'TRUNCATE `nexopos_units`' );
        DB::statement( 'TRUNCATE `nexopos_units_group`' );
        
        return [
            'status'    =>  'success',
            'message'   =>  __( 'The table has been truncated.' )
        ];
    }
}