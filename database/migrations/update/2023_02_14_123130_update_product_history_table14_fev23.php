<?php

use App\Models\Migration as ModelsMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_products_histories', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_products_histories', 'order_product_id' ) ) {
                $table->integer( 'order_product_id' )->nullable();
            }
        });

        if ( ns()->option->get( 'reset_migration_2023_02_14', false ) === false ) {
            ModelsMigration::truncate();

            /**
             * let's avoid a loop hole.
             * This will make sure that runs only once.
             */
            ns()->option->set( 'reset_migration_2023_02_14', true );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
