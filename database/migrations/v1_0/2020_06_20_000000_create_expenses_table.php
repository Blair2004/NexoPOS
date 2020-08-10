<?php
/**
 * Table Migration
 * @package  5.0
**/
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'nexopos_expenses' ) ) {
            Schema::create( 'nexopos_expenses', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
                $table->integer( 'category_id' );
                $table->text( 'description' )->nullable();
                $table->integer( 'media_id' )->default(0);
                $table->float( 'value' )->default(0);
                $table->boolean( 'recurring' )->default(false);
                $table->string( 'occurence' )->nullable(); // 1st 15th startOfMonth, endOfMonth
                $table->integer( 'author' );
                $table->string( 'uuid' )->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_expenses' ) ) {
            Schema::drop( 'nexopos_expenses' );
        }
    }
}

