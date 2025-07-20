<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ( ! Schema::hasTable( 'nexopos_permissions_access' ) ) {
            Schema::create('nexopos_permissions_access', function (Blueprint $table) {
                $table->id();
                $table->integer( 'requester_id' )->unsigned()->index();
                $table->integer( 'granter_id' )->unsigned()->index();
                $table->string( 'permission' )->index();
                $table->string( 'url' )->nullable();
                $table->enum( 'status', [ 'granted', 'denied', 'pending', 'expired', 'used' ] )->default( 'pending' );
                $table->datetime( 'expired_at' )->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_permissions_access');
    }
};
