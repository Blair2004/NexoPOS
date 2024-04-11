<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Determine whether the migration
     * should execute when we're accessing
     * a multistore instance.
     */
    public function runOnMultiStore()
    {
        return false;
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_users_attributes' ) ) {
            Schema::create( 'nexopos_users_attributes', function ( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'user_id' );
                $table->string( 'avatar_link' )->nullable();
                $table->string( 'theme' )->nullable();
                $table->string( 'language' )->nullable();
            } );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( 'nexopos_users_attributes' );
    }
};
