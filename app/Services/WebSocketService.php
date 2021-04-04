<?php
namespace App\Services;

use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use Illuminate\Support\Str;

class WebSocketService 
{
    public function generateFakeCredentials()
    {
        DotenvEditor::load();
        DotenvEditor::setKey( 'PUSHER_APP_ID', Str::random(30) );
        DotenvEditor::setKey( 'PUSHER_APP_KEY', Str::random(30) );
        DotenvEditor::setKey( 'NS_SOCKET_PORT', 6001 );
        DotenvEditor::setKey( 'PUSHER_APP_SECRET', Str::random(30) );
        DotenvEditor::save();
    }
}