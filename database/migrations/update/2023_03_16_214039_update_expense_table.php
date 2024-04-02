<?php

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
        if ( Schema::hasTable( 'nexopos_expenses' ) ) {
            Schema::table( 'nexopos_expenses', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_expenses', 'occurence' ) ) {
                    $table->renameColumn( 'occurence', 'occurrence' );
                }
                if ( Schema::hasColumn( 'nexopos_expenses', 'occurence_value' ) ) {
                    $table->renameColumn( 'occurence_value', 'occurrence_value' );
                }
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
        //
    }
};
