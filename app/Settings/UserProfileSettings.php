<?php
namespace App\Settings;

use App\Http\Requests\UserProfileRequest;
use App\Models\Role;
use App\Services\Helper;
use App\Services\Options;
use App\Services\SettingsPage;

class UserProfileSettings extends SettingsPage
{
    public function __construct()
    {
        $options    =   app()->make( Options::class );
        
        $this->form    =   [
            'tabs'  =>  [
                'general'   =>  include( dirname( __FILE__ ) . '/user-profile/general.php' ),
            ]
        ];
    }

    public function saveForm( UserProfileRequest $request )
    {
        
    }
}