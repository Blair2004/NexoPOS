<?php

use App\Classes\Schema;
use App\Models\Procurement;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class UpdateProcurementsTable240322 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_procurements', function( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_procurements', 'invoice_date' ) ) {
                $table->datetime( 'invoice_date' )->nullable();
            }
        });

        Procurement::get()->each( function( $procurement ) {
            $procurement->invoice_date  =   $procurement->created_at;
            $procurement->save();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table( 'nexopos_procurements', function( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_procurements', 'invoice_date' ) ) {
                $table->dropColumn( 'invoice_date' );
            }
        });
    }
}
