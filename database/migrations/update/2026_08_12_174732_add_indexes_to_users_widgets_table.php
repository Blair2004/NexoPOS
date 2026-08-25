<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if ( ! Schema::hasTable( 'nexopos_users_widgets' ) ) {
            return;
        }

        Schema::table( 'nexopos_users_widgets', function ( Blueprint $table ): void {
            $table->unique( [ 'user_id', 'identifier' ], 'users_widgets_user_identifier_unique' );
            $table->index( [ 'user_id', 'position' ], 'users_widgets_user_position_index' );
        } );
    }

    public function down(): void
    {
        if ( ! Schema::hasTable( 'nexopos_users_widgets' ) ) {
            return;
        }

        Schema::table( 'nexopos_users_widgets', function ( Blueprint $table ): void {
            $table->dropUnique( 'users_widgets_user_identifier_unique' );
            $table->dropIndex( 'users_widgets_user_position_index' );
        } );
    }
};
