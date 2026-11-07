<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
     * Lưu mã người dùng Zalo (user_id) của khách để gửi thông báo qua
     * Zalo OA. Zalo chỉ cho nhắn cho người đã theo dõi OA, và tin nhắn CS
     * cần user_id của Zalo chứ không gửi được bằng số điện thoại.
     *
     * @return void
     */
    public function up()
    {
        if ( Schema::hasTable( 'nexopos_users' ) && ! Schema::hasColumn( 'nexopos_users', 'zalo_user_id' ) ) {
            Schema::table( 'nexopos_users', function ( Blueprint $table ) {
                $table->string( 'zalo_user_id' )->nullable()->after( 'phone' );
            } );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_users' ) && Schema::hasColumn( 'nexopos_users', 'zalo_user_id' ) ) {
            Schema::table( 'nexopos_users', function ( Blueprint $table ) {
                $table->dropColumn( 'zalo_user_id' );
            } );
        }
    }
};
