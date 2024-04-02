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
        Schema::createIfMissing( 'nexopos_customers_rewards', function ( Blueprint $table ) {
            $table->id();
            $table->integer( 'customer_id' );
            $table->integer( 'reward_id' );
            $table->string( 'reward_name' );
            $table->float( 'points', 18, 5 );
            $table->float( 'target', 18, 5 );
            $table->timestamps();
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( 'nexopos_customers_rewards' );
    }
};
