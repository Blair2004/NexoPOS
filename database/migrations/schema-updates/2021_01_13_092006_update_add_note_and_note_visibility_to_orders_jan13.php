<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class UpdateAddNoteAndNoteVisibilityToOrdersJan13 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nexopos_orders', function (Blueprint $table) {
            if ( ! Schema::hasColumn( 'nexopos_orders', 'note' ) ) {
                $table->text( 'note' )->nullable();
            }
            
            if ( ! Schema::hasColumn( 'nexopos_orders', 'note_visibility' ) ) {
                $table->string( 'note_visibility' )->nullable();
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
        Schema::table('nexopos_orders', function (Blueprint $table) {
            if ( Schema::hasColumn( 'nexopos_orders', 'note' ) ) {
                $table->dropColumn( 'note' );
            }

            if ( Schema::hasColumn( 'nexopos_orders', 'note_visibility' ) ) {
                $table->dropColumn( 'note_visibility' );
            }
        });
    }
}
