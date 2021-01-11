<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateNexoposProductsTableOct17 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_products', function( Blueprint $table ) {
            foreach([
                'selling_unit_ids',
                'purchase_unit_ids',
                'transfer_unit_ids',
            ] as $column ) {
                if ( Schema::hasColumn( 'nexopos_products', $column ) ) {
                    $table->dropColumn( $column );
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_products' ) ) {
            Schema::table( 'nexopos_products', function( Blueprint $table ) {
                foreach([
                    'selling_unit_ids',
                    'purchase_unit_ids',
                    'transfer_unit_ids',
                ] as $column ) {
                    if ( Schema::hasColumn( 'nexopos_products', $column ) ) {
                        $table->integer( $column )->nullable();
                    }
                }
            });
        }
    }
}
