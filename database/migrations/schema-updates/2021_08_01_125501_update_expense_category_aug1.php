<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateExpenseCategoryAug1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( Schema::hasTable( 'nexopos_expenses_categories' ) ) {
            Schema::table( 'nexopos_expenses_categories', function( Blueprint $table ) {
                if ( ! Schema::hasColumn( 'nexopos_expenses_categories', 'operation' ) ) {
                    $table->string( 'operation' )->default( 'debit' ); // "credit" or "debit".
                }
                if ( ! Schema::hasColumn( 'nexopos_expenses_categories', 'account' ) ) {
                    $table->string( 'account' )->default(0); // "credit" or "debit".
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
        if ( Schema::hasTable( 'nexopos_expenses_categories' ) ) {
            Schema::table( 'nexopos_expenses_categories', function( Blueprint $table ) {
                if ( Schema::hasColumn( 'nexopos_expenses_categories', 'operation' ) ) {
                    $table->dropColumn( 'operation' ); // "credit" or "debit".
                }
                if ( Schema::hasColumn( 'nexopos_expenses_categories', 'account' ) ) {
                    $table->dropColumn( 'account' ); // "credit" or "debit".
                }
            });
        }
    }
}
