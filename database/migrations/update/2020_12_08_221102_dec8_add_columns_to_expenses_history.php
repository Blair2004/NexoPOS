<?php

use App\Classes\Schema;
use App\Models\CashFlow;
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
        if ( Schema::hasTable( 'nexopos_expenses_history' ) ) {
            Schema::table('nexopos_expenses_history', function (Blueprint $table) {
                if ( ! Schema::hasColumn( 'nexopos_expenses_history', 'status' ) ) {
                    $table->string( 'status' )->default( CashFlow::STATUS_ACTIVE );
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
        //
    }
};
