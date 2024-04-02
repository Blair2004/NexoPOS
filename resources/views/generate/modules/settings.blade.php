<?php
use Illuminate\Support\Str;
?>
<{{ '?php' }}
/**
 * {{ $module[ 'name' ] }} Settings
 * @since {{ $module[ 'version' ] }}
**/
namespace Modules\{{ $module[ 'namespace' ] }}\Settings;

use App\Services\SettingsPage;
use App\Services\ModulesService;
use App\Services\Helper;

class {{ ucwords( $name ) }} extends SettingsPage
{
    protected $form;
    const identifier      =   '{{ Str::slug( $name ) }}';

    public function __construct()
    {
        /**
         * @var ModulesService
         */
        $module     =   app()->make( ModulesService::class );

        /**
         * Settings Form definition.
         */
        $this->form     =   [
            'title'         =>  __m( 'Settings', '{{ $module[ 'namespace' ] }}'' ),
            'description'   =>  __m( 'No description has been provided.', '{{ $module[ 'namespace' ] }}'' )
            'tabs'      =>  [
                'general'   =>  [
                    'label'     =>  __m( 'General Settings', '{{ $module[ 'namespace' ] }}' ),
                    'fields'    =>  [
                        // ...
                    ]
                ]
            ]
        ];
    }
}