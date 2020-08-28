<?php
/**
 * Table Migration
 * @package  5.0
**/
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_stores' ) ) {
            Schema::create( 'nexopos_stores', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
                $table->string( 'status' )->default( 'enabled' ); // enabled, disabled
                $table->text( 'description' )->nullable();
                $table->integer( 'author' );
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
        if ( Schema::hasTable( 'nexopos_stores' ) ) {
            Schema::drop( 'nexopos_stores' );
        }
    }
}

