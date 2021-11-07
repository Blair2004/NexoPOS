<?php
/**
 * Table Migration
 * @package  5.0
**/

use App\Classes\Hook;
use App\Classes\Schema;;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRegistersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_registers' ) ) {
            Schema::createIfMissing( 'nexopos_registers', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
                $table->string( 'status' )->default( 'closed' ); // open, closed, disabled
                $table->text( 'description' )->nullable();
                $table->integer( 'used_by' )->nullable();
                $table->integer( 'author' );
                $table->float( 'balance', 18, 5 )->default(0);
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
        if ( Schema::hasTable( 'nexopos_registers' ) ) {
            Schema::dropIfExists( 'nexopos_registers' );
        }
    }
}

