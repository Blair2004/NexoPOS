<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @property int user_id
 * @property string identifier
 * @property int title
 * @property string description
 * @property string url
 * @property int source
 * @property bool dismissable
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_notifications' ) ) {
            Schema::createIfMissing( 'nexopos_notifications', function ( Blueprint $table ) {
                $table->id();
                $table->integer( 'user_id' );
                $table->string( 'identifier' );
                $table->string( 'title' );
                $table->text( 'description' );
                $table->string( 'url' )->default( '#' );
                $table->string( 'source' )->default( 'system' );
                $table->boolean( 'dismissable' )->default( true );
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
        Schema::dropIfExists( 'nexopos_notifications' );
    }
};
