<?php
namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;


class ResetController extends DashboardController
{
    public function truncateAllTables()
    {
        DB::table( 'nexopos_customers_groups' )->truncate();
        DB::table( 'nexopos_customers_metas' )->truncate();
        DB::table( 'nexopos_customers_coupons' )->truncate();
        DB::table( 'nexopos_customers_coupons_products' )->truncate();
        DB::table( 'nexopos_customers_coupons_categories' )->truncate();
        DB::table( 'nexopos_customers' )->truncate();
        DB::table( 'nexopos_customers_addresses' )->truncate();
        DB::table( 'nexopos_expenses_categories' )->truncate();
        DB::table( 'nexopos_expenses' )->truncate();
        DB::table( 'nexopos_orders_coupons' )->truncate();
        DB::table( 'nexopos_orders_metas' )->truncate();
        DB::table( 'nexopos_orders_payments' )->truncate();
        DB::table( 'nexopos_orders_products' )->truncate();
        DB::table( 'nexopos_orders_addresses' )->truncate();
        DB::table( 'nexopos_orders' )->truncate();
        DB::table( 'nexopos_procurements_products' )->truncate();
        DB::table( 'nexopos_procurements' )->truncate();
        DB::table( 'nexopos_products_categories' )->truncate();
        DB::table( 'nexopos_products_histories' )->truncate();
        DB::table( 'nexopos_products_galleries' )->truncate();
        DB::table( 'nexopos_products_metas' )->truncate();
        DB::table( 'nexopos_products' )->truncate();
        DB::table( 'nexopos_products_taxes' )->truncate();
        DB::table( 'nexopos_products_unit_quantities' )->truncate();
        DB::table( 'nexopos_providers' )->truncate();
        DB::table( 'nexopos_registers_history' )->truncate();
        DB::table( 'nexopos_registers' )->truncate();
        DB::table( 'nexopos_rewards_system_rules' )->truncate();
        DB::table( 'nexopos_rewards_system' )->truncate();
        DB::table( 'nexopos_stores' )->truncate();
        DB::table( 'nexopos_taxes' )->truncate();
        DB::table( 'nexopos_taxes_groups' )->truncate();
        DB::table( 'nexopos_transfers_products' )->truncate();
        DB::table( 'nexopos_transfers' )->truncate();
        DB::table( 'nexopos_units_groups' )->truncate();
        DB::table( 'nexopos_units' )->truncate();
        DB::table( 'nexopos_medias' )->truncate();
        DB::table( 'nexopos_options' )->truncate();
        // DB::table( 'nexopos_trucks' )->truncate();
        
        return [
            'status'    =>  'success',
            'message'   =>  __( 'The table has been truncated.' )
        ];
    }

    public function truncateWithDemo( Request $request )
    {
        if ( $request->input( 'mode' ) === 'wipe_plus_grocery' ) {
            Artisan::call( 'db:seed --class=FirstDemoSeeder' );
        } else {
            Artisan::call( 'db:seed' );
        }

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The database has been purged' )
        ];
    }
}