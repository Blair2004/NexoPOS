<?php
namespace App\Http\Controllers\Dashboard;

use App\Classes\Hook;
use App\Http\Controllers\DashboardController;
use Database\Seeders\FirstDemoSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;


class ResetController extends DashboardController
{
    public function truncateAllTables()
    {
        $tables     =   [
            'nexopos_coupons',
            'nexopos_coupons_products',
            'nexopos_coupons_categories',

            'nexopos_customers',
            'nexopos_customers_account_history',
            'nexopos_customers_addresses',
            'nexopos_customers_coupons',
            'nexopos_customers_groups',
            'nexopos_customers_metas',
            'nexopos_customers_rewards',

            'nexopos_dashboard_days',
            'nexopos_dashboard_weeks',
            'nexopos_dashboard_months',

            'nexopos_expenses',
            'nexopos_expenses_categories',
            'nexopos_expenses_history',

            'nexopos_medias',
            'nexopos_notifications',
            'nexopos_options',
            
            'nexopos_orders',
            'nexopos_orders_addresses',
            'nexopos_orders_count',
            'nexopos_orders_coupons',
            'nexopos_orders_metas',
            'nexopos_orders_payments',
            'nexopos_orders_products',
            'nexopos_orders_products_refunds',
            'nexopos_orders_refunds',
            'nexopos_orders_storage',
            'nexopos_orders_taxes',

            'nexopos_procurements',
            'nexopos_procurements_products',

            'nexopos_products',
            'nexopos_products_categories',
            'nexopos_products_histories',
            'nexopos_products_galleries',
            'nexopos_products_metas',
            'nexopos_products_taxes',
            'nexopos_products_unit_quantities',

            'nexopos_providers',

            'nexopos_registers',
            'nexopos_registers_history',

            'nexopos_rewards_system',
            'nexopos_rewards_system_rules',

            'nexopos_taxes',
            'nexopos_taxes_groups',

            'nexopos_units',
            'nexopos_units_groups',
        ];

        foreach( $tables as $table ) {
            if ( Hook::filter( 'ns-reset-table', $table ) !== false ) {
                DB::table( Hook::filter( 'ns-table-name', $table ) )->truncate();
            }
        }
        
        return [
            'status'    =>  'success',
            'message'   =>  __( 'The table has been truncated.' )
        ];
    }

    public function truncateWithDemo( Request $request )
    {
        $this->truncateAllTables();

        switch( $request->input( 'mode' ) ) {
            case 'wipe_plus_grocery':
                ( new FirstDemoSeeder )->run();
            break;
            case 'wipe_plus_simple':
                ( new FirstDemoSeeder )->run();
            break;
            default:
                // nothing run if a mode is not defined
            break;
        }

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The database has been purged' )
        ];
    }
}