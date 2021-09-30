<?php
/**
 * Table Migration
 * @package  5.0
**/

use App\Classes\Hook;
use App\Classes\Schema;;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRewardsSystemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_rewards_system' ) ) {
            Schema::createIfMissing( 'nexopos_rewards_system', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'author' );
                $table->string( 'name' );
                $table->float( 'target', 18, 5 )->default(0);
                $table->text( 'description' )->nullable();
                $table->integer( 'coupon_id' )->nullable();
                $table->string( 'uuid' )->nullable();
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
        if ( Schema::hasTable( 'nexopos_rewards_system' ) ) {
            Schema::dropIfExists( 'nexopos_rewards_system' );
        }
    }
}

