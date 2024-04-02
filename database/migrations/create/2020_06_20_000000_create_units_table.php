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
        if ( ! Schema::hasTable( 'nexopos_units' ) ) {
            Schema::createIfMissing( 'nexopos_units', function ( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
                $table->string( 'identifier' )->unique();
                $table->text( 'description' )->nullable();
                $table->integer( 'author' );
                $table->integer( 'group_id' );
                $table->float( 'value', 18, 5 );
                $table->string( 'preview_url' )->nullable();
                $table->boolean( 'base_unit' ); // 0, 1
                $table->string( 'uuid' )->nullable();
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
        if ( Schema::hasTable( 'nexopos_units' ) ) {
            Schema::dropIfExists( 'nexopos_units' );
        }
    }
};
