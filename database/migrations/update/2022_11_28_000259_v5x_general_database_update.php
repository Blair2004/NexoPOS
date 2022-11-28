<?php

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
        Schema::table( 'nexopos_roles', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_roles', 'dashid' ) ) {
                $table->removeColumn( 'dashid' );
            }
        });

        /**
         * let's create a constant which will allow the creation,
         * since these files are included as migration file
         */
        if ( ! defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
            define( 'NEXO_CREATE_PERMISSIONS', true );
        }

        /**
         * let's include the files that will create permissions
         * for all the declared widgets.
         */
        include( dirname( __FILE__ ) . '/../../permissions/widgets.php' );
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
