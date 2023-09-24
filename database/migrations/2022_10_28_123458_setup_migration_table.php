<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( Schema::hasTable( 'migrations' ) ) {
            Schema::table( 'migrations', function ( Blueprint $table ) {
                if ( ! Schema::hasColumn( 'migrations', 'type' ) ) {
                    $table->string( 'type' )->default( 'core' );
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
        if ( Schema::hasTable( 'migrations' ) ) {
            Schema::table( 'migrations', function ( Blueprint $table ) {
                if ( Schema::hasColumn( 'migrations', 'type' ) ) {
                    $table->dropColumn( 'type' );
                }
            });
        }
    }
};
