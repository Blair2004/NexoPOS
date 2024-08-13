<?php

namespace App\Services;

use App\Classes\Hook;
use App\Classes\Schema;
use App\Events\AfterHardResetEvent;
use App\Events\BeforeHardResetEvent;
use App\Models\Customer;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ResetService
{
    public function softReset()
    {
        $tables = Hook::filter( 'ns-wipeable-tables', [
            'nexopos_coupons',
            'nexopos_coupons_products',
            'nexopos_coupons_categories',
            'nexopos_coupons_customers',
            'nexopos_coupons_customers_groups',

            'nexopos_customers_account_history',
            'nexopos_customers_addresses',
            'nexopos_customers_coupons',
            'nexopos_customers_groups',
            'nexopos_customers_rewards',

            'nexopos_dashboard_days',
            'nexopos_dashboard_weeks',
            'nexopos_dashboard_months',

            'nexopos_transactions',
            'nexopos_transactions_accounts',
            'nexopos_transactions_histories',

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
            'nexopos_products_histories_combined',
            'nexopos_products_galleries',
            'nexopos_products_metas',
            'nexopos_products_taxes',
            'nexopos_products_unit_quantities',

            'nexopos_payments_types',

            'nexopos_providers',

            'nexopos_registers',
            'nexopos_registers_history',

            'nexopos_rewards_system',
            'nexopos_rewards_system_rules',

            'nexopos_taxes',
            'nexopos_taxes_groups',

            'nexopos_units',
            'nexopos_units_groups',
        ] );

        foreach ( $tables as $table ) {
            if ( Hook::filter( 'ns-reset-table', $table ) !== false && Schema::hasTable( Hook::filter( 'ns-reset-table', $table ) ) ) {
                DB::table( Hook::filter( 'ns-table-name', $table ) )->truncate();
            }
        }

        /**
         * Customers stills needs to be cleared
         * so we'll remove them manually.
         */
        Customer::get()->each( fn( $customer ) => app()->make( CustomerService::class )->delete( $customer ) );

        return [
            'status' => 'success',
            'message' => __( 'The table has been truncated.' ),
        ];
    }

    /**
     * Will completely wipe the database
     * forcing a new installation to be made
     */
    public function hardReset(): array
    {
        BeforeHardResetEvent::dispatch();

        /**
         * this will only apply clearing all tables
         * when we're not using sqlite.
         */
        if ( env( 'DB_CONNECTION' ) !== 'sqlite' ) {
            $tables = DB::select( 'SHOW TABLES' );

            foreach ( $tables as $table ) {
                $table_name = array_values( (array) $table )[0];
                DB::statement( 'SET FOREIGN_KEY_CHECKS = 0' );
                DB::statement( "DROP TABLE `$table_name`" );
                DB::statement( 'SET FOREIGN_KEY_CHECKS = 1' );
            }
        } else {
            file_put_contents( database_path( 'database.sqlite' ), '' );
        }

        Artisan::call( 'key:generate', [ '--force' => true ] );
        Artisan::call( 'ns:cookie generate' );

        exec( 'rm -rf public/storage' );

        AfterHardResetEvent::dispatch();

        return [
            'status' => 'success',
            'message' => __( 'The database has been wiped out.' ),
        ];
    }

    public function handleCustom( $data )
    {
        /**
         * @var string $mode
         * @var bool   $create_sales
         * @var bool   $create_procurements
         */
        extract( $data );

        return Hook::filter( 'ns-handle-custom-reset', [
            'status' => 'error',
            'message' => __( 'No custom handler for the reset "' . $mode . '"' ),
        ], $data );
    }
}
