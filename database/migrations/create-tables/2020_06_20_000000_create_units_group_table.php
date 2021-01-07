<?php
/**
 * Table Migration
 * @package  5.0
**/

use App\Classes\Hook;
use App\Classes\Schema;;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUnitsGroupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_units_groups' ) ) {
            Schema::createIfMissing( 'nexopos_units_groups', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
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
        if ( Schema::hasTable( 'nexopos_units_groups' ) ) {
            Schema::dropIfExists( 'nexopos_units_groups' );
        }
    }
}

