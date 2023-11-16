<?php

use App\Models\User;
use App\Services\Users;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Determine whether the migration
     * should execute when we're accessing
     * a multistore instance.
     */
    public function runOnMultiStore()
    {
        return false;
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * @var Users
         */
        $userService = app()->make( Users::class );

        User::get()->each( fn( $user ) => $userService->createAttribute( $user ) );

        Artisan::call( 'ns:translate --symlink' );
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
