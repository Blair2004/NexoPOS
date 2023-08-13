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
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_customers_metas' ) ) {
            Schema::createIfMissing( 'nexopos_customers_metas', function ( Blueprint $table ) {
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
};
