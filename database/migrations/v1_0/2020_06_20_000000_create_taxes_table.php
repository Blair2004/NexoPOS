<?php
/**
 * Table Migration
 * @package  5.0
**/
use Illuminate\Support\Facades\Schema;
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
            Schema::create( 'nexopos_taxes', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
                $table->text( 'description' )->nullable();
                $table->float( 'rate' );
                $table->integer( 'tax_group_id' );
                $table->integer( 'author' );
                $table->string( 'uuid' )->nullable();
                $table->timestamps();
            });
        }

        if ( ! Schema::hasTable( 'nexopos_tax_groups' ) ) {
            Schema::create( 'nexopos_tax_groups', function( Blueprint $table ) {
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
            Schema::drop( 'nexopos_taxes' );
        }

        if ( Schema::hasTable( 'nexopos_tax_groups' ) ) {
            Schema::drop( 'nexopos_tax_groups' );
        }
    }
}

