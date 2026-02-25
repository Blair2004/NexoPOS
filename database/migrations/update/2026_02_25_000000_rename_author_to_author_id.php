<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that have an `author` column which should be renamed to `author_id`.
     */
    protected array $tables = [
        'nexopos_coupons',
        'nexopos_customers_addresses',
        'nexopos_customers_coupons',
        'nexopos_customers_groups',
        'nexopos_orders',
        'nexopos_orders_addresses',
        'nexopos_orders_coupons',
        'nexopos_orders_metas',
        'nexopos_orders_payments',
        'nexopos_orders_products',
        'nexopos_orders_products_refunds',
        'nexopos_payments_types',
        'nexopos_procurements',
        'nexopos_procurements_products',
        'nexopos_products',
        'nexopos_products_categories',
        'nexopos_products_galleries',
        'nexopos_products_histories',
        'nexopos_products_metas',
        'nexopos_products_subitems',
        'nexopos_products_taxes',
        'nexopos_providers',
        'nexopos_registers',
        'nexopos_registers_history',
        'nexopos_rewards_system',
        'nexopos_rewards_system_rules',
        'nexopos_roles',
        'nexopos_scale_ranges',
        'nexopos_taxes',
        'nexopos_taxes_groups',
        'nexopos_transactions',
        'nexopos_transactions_accounts',
        'nexopos_transactions_histories',
        'nexopos_units',
        'nexopos_units_groups',
        'nexopos_users',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ( $this->tables as $table ) {
            if ( Schema::hasTable( $table ) && Schema::hasColumn( $table, 'author' ) && ! Schema::hasColumn( $table, 'author_id' ) ) {
                Schema::table( $table, function ( Blueprint $table ) {
                    $table->renameColumn( 'author', 'author_id' );
                } );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ( $this->tables as $table ) {
            if ( Schema::hasTable( $table ) && Schema::hasColumn( $table, 'author_id' ) && ! Schema::hasColumn( $table, 'author' ) ) {
                Schema::table( $table, function ( Blueprint $table ) {
                    $table->renameColumn( 'author_id', 'author' );
                } );
            }
        }
    }
};
