<?php

namespace App\Settings;

use App\Classes\FormInput;
use App\Classes\Hook;
use App\Classes\Output;
use App\Classes\SettingForm;
use App\Services\Helper;
use App\Services\SettingsPage;

class ResetSettings extends SettingsPage
{
    const IDENTIFIER = 'reset';

    const AUTOLOAD = true;

    protected $form;

    public function __construct()
    {
        $this->form = SettingForm::form(
            title: __( 'Reset' ),
            description: __( 'Wipes and Reset the database.' ),
            tabs: SettingForm::tabs(
                SettingForm::tab(
                    identifier: 'reset',
                    label: __( 'Reset' ),
                    fields: SettingForm::fields(
                        FormInput::select(
                            name: 'mode',
                            label: __( 'Mode' ),
                            validation: 'required',
                            options: Helper::kvToJsOptions( Hook::filter( 'ns-reset-options', [
                                'wipe_all' => __( 'Wipe All' ),
                                'wipe_plus_grocery' => __( 'Wipe Plus Grocery' ),
                            ] ) ),
                            description: __( 'Choose what mode applies to this demo.' ),
                        ),
                        FormInput::checkbox(
                            name: 'create_sales',
                            label: __( 'Create Sales (needs Procurements)' ),
                            value: 1,
                            description: __( 'Set if the sales should be created.' ),
                        ),
                        FormInput::checkbox(
                            name: 'create_procurements',
                            label: __( 'Create Procurements' ),
                            value: 1,
                            description: __( 'Will create procurements.' ),
                        )
                    )
                )
            )
        );
    }

    public function beforeRenderForm()
    {
        Hook::addAction( 'ns-dashboard-footer', fn( Output $output ) => $output->addView( 'pages.dashboard.settings.reset-footer' ) );
    }
}
