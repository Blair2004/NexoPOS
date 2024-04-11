<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_users_widgets' ) ) {
            Schema::create( 'nexopos_users_widgets', function ( Blueprint $table ) {
                $table->uuid( 'id' )->primary();
                $table->string( 'identifier' );
                $table->string( 'column' );
                $table->string( 'class_name' );
                $table->integer( 'position' );
                $table->integer( 'user_id' );
                $table->timestamps();
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
        Schema::dropIfExists( 'nexopos_users_widgets' );
    }
};
