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
        Schema::table( 'nexopos_units', function (Blueprint $table) {
            if ( ! Schema::hasColumn( 'nexopos_units', 'preview_url' ) ) {
                $table->string( 'preview_url' )->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table( 'nexopos_units', function (Blueprint $table) {
            if ( Schema::hasColumn( 'nexopos_units', 'preview_url' ) ) {
                $table->dropColumn( 'preview_url' );
            }
        });
    }
};
