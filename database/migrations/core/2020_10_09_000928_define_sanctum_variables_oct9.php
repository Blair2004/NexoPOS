<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

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
        DotenvEditor::setKey( 'SANCTUM_STATEFUL_DOMAINS', $domain );
        DotenvEditor::setKey( 'SESSION_DOMAIN', $domain );
        DotenvEditor::save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DotenvEditor::deleteKey( 'SANCTUM_STATEFUL_DOMAINS' );
        DotenvEditor::deleteKey( 'SESSION_DOMAIN' );
        DotenvEditor::save();
    }
};
