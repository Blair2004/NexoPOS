<?php
/**
 * Table Migration
 * @package  5.0
**/

use App\Classes\Hook;
use App\Classes\Schema;;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomersMetasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_customers_metas' ) ) {
            Schema::createIfMissing( 'nexopos_customers_metas', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->integer( 'customer_id' );
                $table->string( 'key' );
                $table->text( 'value' );
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
        if ( Schema::hasTable( 'nexopos_customers_metas' ) ) { 
            Schema::drop( 'nexopos_customers_metas' );
        }
    }
}

