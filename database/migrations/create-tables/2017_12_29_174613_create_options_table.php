<?php

use App\Classes\Hook;
use App\Classes\Schema;;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::createIfMissing( 'nexopos_options', function (Blueprint $table) {
            $table->increments('id');
            $table->integer( 'user_id' )->nullable();
            $table->string( 'key' );
            $table->text( 'value' )->nullable();
            $table->datetime( 'expire_on' )->nullable();
            $table->boolean( 'array' ); // this will avoid some option to be saved as options
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( 'nexopos_options' );
    }
}
