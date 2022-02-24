<?php
namespace App\Services;

use App\Classes\Hook;
use App\Classes\Schema;
use App\Events\OnCustomResetModeEvent;
use App\Models\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

class ResetService
{
    public function softReset()
    {
        $tables     =   Hook::filter( 'ns-wipeable-tables', [
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
            'nexopos_cash_flow',

            'nexopos_medias',
            'nexopos_notifications',
            
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
        ]);

        foreach( $tables as $table ) {
            if ( Hook::filter( 'ns-reset-table', $table ) !== false && Schema::hasTable( Hook::filter( 'ns-reset-table', $table ) ) ) {
                DB::table( Hook::filter( 'ns-table-name', $table ) )->truncate();
            }
        }

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The table has been truncated.' )
        ];
    }

    /**
     * Will completely wipe the database
     * forcing a new installation to be made
     * @return void
     */
    public function hardReset()
    {
        Artisan::call( 'migrate:reset --path=/database/migrations/default' );
        Artisan::call( 'migrate:reset --path=/database/migrations/create-tables' );
        
        DotenvEditor::load();
        DotenvEditor::deleteKey( 'NS_VERSION' );
        DotenvEditor::deleteKey( 'NS_AUTHORIZATION' );
        DotenvEditor::save();

        Migration::truncate();

        Artisan::call( 'key:generate' );

        exec( 'rm -rf public/storage' );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The database has been hard reset.' )
        ];
    }

    public function handleCustom( $data )
    {
        /**
         * @var string $mode
         * @var boolean $create_sales
         * @var boolean $create_procurements
         */
        extract( $data );

        return Hook::filter( 'ns-handle-custom-reset', [
            'status'    =>  'failed',
            'message'   =>  __( 'No custom handler for the reset "' . $mode . '"' )
        ], $data );
    }
}