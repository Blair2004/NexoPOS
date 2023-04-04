<?php
/**
 * Table Migration
**/

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
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
                $table->string( 'occurrence' )->nullable(); // 1st 15th startOfMonth, endOfMonth
                $table->string( 'occurrence_value' )->nullable(); // 1st 15th startOfMonth, endOfMonth
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
};
