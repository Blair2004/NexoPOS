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
namespace App\Settings;
@endif

use App\Services\SettingsPage;
use App\Classes\SettingForm;
use App\Classes\FormInput;

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
        /**
         * Settings Form definition.
         */
        $this->form     =   SettingForm::form(
            title: __( 'Settings' ),
            description: __( 'No description has been provided.' ),
            tabs: SettingForm::tabs(
                SettingForm::tab(
                    label: __( 'General Settings' ),
                    identifier: 'general',
                    fields: SettingForm::fields(
                        FormInput::text(
                            label: __( 'Sample Text' ),
                            name: 'sample',
                            description: __( 'Field description' ),
                            value: ns()->option->get( 'sample' )
                        )
                    )
                )
            )
        );
    }
}
