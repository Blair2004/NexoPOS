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
        if ( ! Schema::hasTable( 'nexopos_providers' ) ) {
            Schema::createIfMissing( 'nexopos_providers', function ( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'first_name' );
                $table->string( 'last_name' )->nullable();
                $table->string( 'email' )
                    ->unique()
                    ->nullable();
                $table->string( 'phone' )->nullable();
                $table->string( 'address_1' )->nullable();
                $table->string( 'address_2' )->nullable();
                $table->integer( 'author' );
                $table->text( 'description' )->nullable();
                $table->float( 'amount_due', 18, 5 )->default( 0 );
                $table->float( 'amount_paid', 18, 5 )->default( 0 );
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
        if ( Schema::hasTable( 'nexopos_providers' ) ) {
            Schema::dropIfExists( 'nexopos_providers' );
        }
    }
};
