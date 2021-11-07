<?php
/**
 * Table Migration
 * @package  5.0
**/

use App\Classes\Hook;
use App\Classes\Schema;;
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
            Schema::createIfMissing( 'nexopos_expenses', function( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'name' );
                $table->integer( 'category_id' );
                $table->text( 'description' )->nullable();
                $table->integer( 'media_id' )->default(0);
                $table->float( 'value', 18, 5 )->default(0);
                $table->boolean( 'recurring' )->default(false);
                $table->boolean( 'active' )->default(false);
                $table->integer( 'group_id' )->nullable();
                $table->string( 'occurence' )->nullable(); // 1st 15th startOfMonth, endOfMonth
                $table->string( 'occurence_value' )->nullable(); // 1st 15th startOfMonth, endOfMonth
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
        Schema::dropIfExists( 'nexopos_expenses' );
    }
}

