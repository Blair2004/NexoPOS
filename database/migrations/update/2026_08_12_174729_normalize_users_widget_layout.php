<?php

use App\Classes\Schema;
use App\Models\UserWidget;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalize legacy column layouts into a single responsive dashboard order.
     */
    public function up(): void
    {
        if ( ! Schema::hasTable( 'nexopos_users_widgets' ) ) {
            return;
        }

        $table = ( new UserWidget )->getTable();
        $columnOrder = [
            'first-column' => 0,
            'second-column' => 1,
            'third-column' => 2,
            'dashboard' => 3,
        ];

        DB::table( $table )->select( 'user_id' )->distinct()->pluck( 'user_id' )
            ->each( function ( int $userId ) use ( $table, $columnOrder ): void {
                $seenIdentifiers = [];
                $position = 0;

                DB::table( $table )
                    ->where( 'user_id', $userId )
                    ->get()
                    ->sortBy( fn( object $widget ): array => [
                        $widget->position,
                        $columnOrder[$widget->column] ?? count( $columnOrder ),
                        $widget->id,
                    ] )
                    ->each( function ( object $widget ) use ( $table, &$seenIdentifiers, &$position ): void {
                        if ( in_array( $widget->identifier, $seenIdentifiers, true ) ) {
                            DB::table( $table )->where( 'id', $widget->id )->delete();

                            return;
                        }

                        $seenIdentifiers[] = $widget->identifier;

                        DB::table( $table )->where( 'id', $widget->id )->update( [
                            'column' => 'dashboard',
                            'position' => $position++,
                        ] );
                    } );
            } );
    }

    public function down(): void
    {
        if ( ! Schema::hasTable( 'nexopos_users_widgets' ) ) {
            return;
        }

        $table = ( new UserWidget )->getTable();
        $columns = [ 'first-column', 'second-column', 'third-column' ];

        DB::table( $table )->select( 'user_id' )->distinct()->pluck( 'user_id' )
            ->each( function ( int $userId ) use ( $table, $columns ): void {
                DB::table( $table )
                    ->where( 'user_id', $userId )
                    ->orderBy( 'position' )
                    ->get()
                    ->values()
                    ->each( function ( object $widget, int $index ) use ( $table, $columns ): void {
                        DB::table( $table )->where( 'id', $widget->id )->update( [
                            'column' => $columns[$index % count( $columns )],
                            'position' => intdiv( $index, count( $columns ) ),
                        ] );
                    } );
            } );
    }
};
