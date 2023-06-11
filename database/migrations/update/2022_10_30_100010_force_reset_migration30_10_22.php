<?php

use App\Models\Migration as ModelsMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( ns()->option->get( 'reset_migration_30_10', false ) === false ) {
            ModelsMigration::truncate();

            /**
             * let's avoid a loop hole.
             * This will make sure that runs only once.
             */
            ns()->option->set( 'reset_migration_30_10', true );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
