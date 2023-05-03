<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

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
        $domain = Str::replaceFirst( 'http://', '', url( '/' ) );
        $domain = Str::replaceFirst( 'https://', '', $domain );
        ns()->envEditor->set( 'SANCTUM_STATEFUL_DOMAINS', $domain );
        ns()->envEditor->set( 'SESSION_DOMAIN', $domain );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        ns()->envEditor->delete( 'SANCTUM_STATEFUL_DOMAINS' );
        ns()->envEditor->delete( 'SESSION_DOMAIN' );
    }
};
