<?php
/**
 * Table Migration
 * @package  5.0
**/
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRewardsSystemRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_rewards_system_rules' ) ) {
            Schema::create( 'nexopos_rewards_system_rules', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->float( 'from' );
                $table->float( 'to' );
                $table->float( 'reward' );
                $table->integer( 'reward_id' );
                $table->string( 'uuid' )->nullable();
                $table->integer( 'author' );
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_rewards_system_rules' ) ) {
            Schema::drop( 'nexopos_rewards_system_rules' );
        }
    }
}

