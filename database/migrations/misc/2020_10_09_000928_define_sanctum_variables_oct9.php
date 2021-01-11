<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;
use Illuminate\Support\Str;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

class DefineSanctumVariablesOct9 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $domain     =   Str::replaceFirst( 'http://', '', url( '/' ) );
        $domain     =   Str::replaceFirst( 'https://', '', $domain );
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
}
