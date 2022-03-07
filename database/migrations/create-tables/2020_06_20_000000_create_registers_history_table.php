<?php
/**
 * Table Migration
 * @package  5.0
**/

use App\Classes\Hook;
use App\Classes\Schema;
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
        if ( ! Schema::hasTable( 'nexopos_registers_history' ) ) {
            Schema::createIfMissing( 'nexopos_registers_history', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'register_id' );
                $table->string( 'action' );
                $table->integer( 'author' );
                $table->float( 'value', 18, 5 )->default(0);
                $table->text( 'description' )->nullable();
                $table->string( 'uuid' )->nullable();
                $table->string( 'diff_type' )->nullable(); // can be "negative", "positive".
                $table->float( 'diff_amount' )->default(0);
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
        if ( Schema::hasTable( 'nexopos_registers_history' ) ) {
            Schema::dropIfExists( 'nexopos_registers_history' );
        }
    }
}

