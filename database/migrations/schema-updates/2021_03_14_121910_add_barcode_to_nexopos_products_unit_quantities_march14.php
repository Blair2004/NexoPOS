<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;
use App\Models\ProductUnitQuantity;

class AddBarcodeToNexoposProductsUnitQuantitiesMarch14 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nexopos_products_unit_quantities', function (Blueprint $table) {
            if ( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'barcode' ) ) {
                $table->string( 'barcode' )->nullable();
            }
        });

        ProductUnitQuantity::get()->each( function( ProductUnitQuantity $unitQuantity ) {
            $barcode                =   $unitQuantity->product->barcode;
            $unitQuantity->barcode  =   $barcode . '-' . $unitQuantity->id;
            $unitQuantity->save();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('nexopos_products_unit_quantities', function (Blueprint $table) {
            if ( Schema::hasColumn( 'nexopos_products_unit_quantities', 'barcode' ) ) {
                $table->dropColumn( 'barcode' );
            }
        });
    }
}
