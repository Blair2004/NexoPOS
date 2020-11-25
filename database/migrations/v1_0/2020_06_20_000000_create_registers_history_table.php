<?php
/**
 * Table Migration
 * @package  5.0
**/

use App\Classes\Hook;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRegistersHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( Hook::filter( 'ns-table-prefix', 'nexopos_registers_history' ) ) ) {
            Schema::create( Hook::filter( 'ns-table-prefix', 'nexopos_registers_history' ), function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'register_id' );
                $table->string( 'action' );
                $table->integer( 'author' );
                $table->float( 'value' );
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
        if ( Schema::hasTable( Hook::filter( 'ns-table-prefix', 'nexopos_registers_history' ) ) ) {
            Schema::dropIfExists( Hook::filter( 'ns-table-prefix', 'nexopos_registers_history' ) );
        }
    }
}

