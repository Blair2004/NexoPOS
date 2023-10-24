<?php
use Illuminate\Support\Str;

$simplifiedSlug = str_replace( '-settings', '', Str::snake( $className, '-' ) );
$identifier     =   isset( $module ) ? 'module-' .  $simplifiedSlug . '-settings' : 'ns-' .  $simplifiedSlug . '-settings';
$routeName      =   $identifier . '-route';
?>
<{{ '?php' }}

@if( isset( $module ) )
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
    const IDENTIFIER = '{{ $identifier }}';

    public function __construct()
    {
        $this->form = [
            'title' =>  __( 'Unamed Settings Page' ),
            'description'   =>  __( 'Description of unamed setting page' ),
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
