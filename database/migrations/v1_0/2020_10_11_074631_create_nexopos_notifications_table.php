<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNexoposNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_notifications' ) ) {
            Schema::create('nexopos_notifications', function (Blueprint $table) {
                $table->id();
                $table->integer( 'user_id' );
                $table->string( 'identifier' );
                $table->string( 'title' );
                $table->string( 'description' );
                $table->string( 'url' )->default( '#' );
                $table->string( 'source' )->default( 'system' );
                $table->boolean( 'dismissable' )->default( true );
                $table->timestamps();
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
        Schema::dropIfExists('nexopos_notifications');
    }
}
