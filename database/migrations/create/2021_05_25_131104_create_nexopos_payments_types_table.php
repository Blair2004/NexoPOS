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
        Schema::createIfMissing( 'nexopos_payments_types', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'label' );
            $table->string( 'identifier' );
            $table->integer( 'priority' )->default( 0 );
            $table->text( 'description' )->nullable();
            $table->integer( 'author' );
            $table->boolean( 'active' )->default( true );
            $table->boolean( 'readonly' )->default( false );
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
        Schema::dropIfExists( 'nexopos_payments_types' );
    }
};
