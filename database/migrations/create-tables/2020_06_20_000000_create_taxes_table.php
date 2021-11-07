<?php
/**
 * Table Migration
 * @package  5.0
**/

use App\Classes\Hook;
use App\Classes\Schema;;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_taxes' ) ) {
            Schema::createIfMissing( 'nexopos_taxes', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
                $table->text( 'description' )->nullable();
                $table->float( 'rate', 18, 5 );
                $table->integer( 'tax_group_id' );
                $table->integer( 'author' );
                $table->string( 'uuid' )->nullable();
                $table->timestamps();
            });
        }

        if ( ! Schema::hasTable( 'nexopos_taxes_groups' ) ) {
            Schema::createIfMissing( 'nexopos_taxes_groups', function( Blueprint $table ) {
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
        if ( Schema::hasTable( 'nexopos_taxes' ) ) {
            Schema::dropIfExists( 'nexopos_taxes' );
        }

        if ( Schema::hasTable( 'nexopos_taxes_groups' ) ) {
            Schema::dropIfExists( 'nexopos_taxes_groups' );
        }
    }
}

