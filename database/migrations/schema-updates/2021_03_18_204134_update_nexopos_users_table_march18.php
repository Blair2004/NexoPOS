<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateNexoposUsersTableMarch18 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_users', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_users', 'activation_token' ) ) {
                $table->string( 'activation_token' )->nullable();
            }

            if ( ! Schema::hasColumn( 'nexopos_users', 'activation_expiration' ) ) {
                $table->datetime( 'activation_expiration' )->nullable();
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
        if ( Schema::hasTable( 'nexopos_users' ) ) {
            Schema::table( 'nexopos_users', function( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_users', 'activation_token' ) ) {
                    $table->dropColumn( 'activation_token' );
                }
    
                if ( Schema::hasColumn( 'nexopos_users', 'activation_expiration' ) ) {
                    $table->dropColumn( 'activation_expiration' );
                }
            });
        }
    }
}
