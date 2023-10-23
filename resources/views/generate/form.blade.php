<?php
use Illuminate\Support\Str;

$simplifiedSlug = str_replace( '-settings', '', Str::snake( $className, '-' ) );
$identifier     =   $module ? 'module-' .  $simplifiedSlug . '-settings' : 'ns-' .  $simplifiedSlug . '-settings';
$routeName      =   $identifier . '-route';
?>
<{{ '?php' }}

@if( $module )
namespace Modules\{{ $module[ 'namespace' ] }}\Forms;
@else
namespace App\Forms;
@endif

use App\Services\SettingsPage;

class {{ $className }} extends SettingsPage
{
    /**
     * The form will be automatically loaded.
     * You might prevent this by setting "autoload" to false.
     */
    const AUTOLOAD  =   true;

    /**
     * A unique identifier provided to the form, 
     * that helps NexoPOS distinguish it among other forms.
     */
    protected $identifier = '{{ $identifier }}';

    /**
     * A unique slug that makes the form available
     * through GET requests.
     */
    protected $slug     =   'settings/{{ $simplifiedSlug }}';

    /**
     * Route name that is used to identify the form route
     * On laravel routing configuration.
     */
    protected $routeName    =   '{{ $routeName }}';

    protected $labels;

    public function __construct()
    {
        /**
         * Put your labels here to provide 
         * futher details to your settings page.
         */
        $this->labels   =   [
            'title' =>  __( 'Unamed Settings Page' ),
            'description'   =>  __( 'Description of unamed setting page' )
        ];

        $this->form = [
            'tabs' => [
                'general' => [
                    'label' =>  __( 'General' ),
                    'fields'    =>  [
                        [
                            'type'  =>  'text',
                            'name'  =>  'sample',
                            'label' =>  __( 'Text Field' ),
                            'description'   =>  __( 'This is a sample text field.' )
                        ]
                    ]
                ]
            ],
        ];
    }
}
