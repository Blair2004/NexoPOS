<?php
/**
 * Table Migration
**/

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
        if ( ! Schema::hasTable( 'nexopos_rewards_system_rules' ) ) {
            Schema::createIfMissing( 'nexopos_rewards_system_rules', function ( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->float( 'from', 18, 5 );
                $table->float( 'to', 18, 5 );
                $table->float( 'reward', 18, 5 );
                $table->integer( 'reward_id' );
                $table->string( 'uuid' )->nullable();
                $table->integer( 'author' );
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
        if ( Schema::hasTable( 'nexopos_rewards_system_rules' ) ) {
            Schema::dropIfExists( 'nexopos_rewards_system_rules' );
        }
    }
};
