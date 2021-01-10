<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateNexoposExpensesTableOct30 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( Schema::hasTable( 'nexopos_expenses' ) ) {
            Schema::table( 'nexopos_expenses', function( Blueprint $table ) {
                if ( ! Schema::hasColumn( 'nexopos_expenses', 'active' ) ) {
                    $table->boolean( 'active' )->default(true);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_expenses' ) ) {
            Schema::table( 'nexopos_expenses', function( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_expenses', 'active' ) ) {
                    $table->dropColumn( 'active' );
                }
            });
        }
    }
}
