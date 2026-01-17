<?php

/**
 * Table Migration
 * Add position column to categories and products tables for custom ordering
 **/

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add position column to categories table
        if ( Schema::hasTable( 'nexopos_products_categories' ) ) {
            if ( ! Schema::hasColumn( 'nexopos_products_categories', 'position' ) ) {
                Schema::table( 'nexopos_products_categories', function ( Blueprint $table ) {
                    $table->integer( 'position' )->default( 0 )->after( 'description' );
                } );

                // Set initial position values based on created_at
                DB::statement( '
                    UPDATE nexopos_products_categories 
                    SET position = (
                        SELECT COUNT(*) 
                        FROM (SELECT * FROM nexopos_products_categories) AS t 
                        WHERE t.created_at < nexopos_products_categories.created_at 
                        AND (t.parent_id = nexopos_products_categories.parent_id OR (t.parent_id IS NULL AND nexopos_products_categories.parent_id IS NULL))
                    )
                ' );
            }
        }

        // Add position column to products table
        if ( Schema::hasTable( 'nexopos_products' ) ) {
            if ( ! Schema::hasColumn( 'nexopos_products', 'position' ) ) {
                Schema::table( 'nexopos_products', function ( Blueprint $table ) {
                    $table->integer( 'position' )->default( 0 )->after( 'searchable' );
                } );

                // Set initial position values based on created_at
                DB::statement( '
                    UPDATE nexopos_products 
                    SET position = (
                        SELECT COUNT(*) 
                        FROM (SELECT * FROM nexopos_products) AS t 
                        WHERE t.created_at < nexopos_products.created_at 
                        AND t.category_id = nexopos_products.category_id
                    )
                ' );
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_products_categories' ) ) {
            if ( Schema::hasColumn( 'nexopos_products_categories', 'position' ) ) {
                Schema::table( 'nexopos_products_categories', function ( Blueprint $table ) {
                    $table->dropColumn( 'position' );
                } );
            }
        }

        if ( Schema::hasTable( 'nexopos_products' ) ) {
            if ( Schema::hasColumn( 'nexopos_products', 'position' ) ) {
                Schema::table( 'nexopos_products', function ( Blueprint $table ) {
                    $table->dropColumn( 'position' );
                } );
            }
        }
    }
};
