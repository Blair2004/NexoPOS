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
        Schema::createIfMissing( 'nexopos_medias', function ( Blueprint $table ) {
            $table->increments( 'id' );
            $table->string( 'name' )->unique();
            $table->string( 'extension' );
            $table->string( 'slug' );
            $table->integer( 'user_id' );
            $table->timestamps();
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( 'nexopos_medias' );
    }
};
