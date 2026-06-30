<?php

namespace App\Settings;

use App\Classes\FormInput;
use App\Classes\SettingForm;
use App\Services\SettingsPage;

class MediaSettings extends SettingsPage
{
    const IDENTIFIER = 'media';

    const AUTOLOAD = true;

    public function __construct()
    {
        $this->form = SettingForm::form(
            title: __( 'Media Settings' ),
            description: __( 'Configure the media manager interface.' ),
            tabs: SettingForm::tabs(
                SettingForm::tab(
                    label: __( 'General' ),
                    identifier: 'general',
                    fields: SettingForm::fields(
                        FormInput::select(
                            label: __( 'Media Manager Layout' ),
                            name: 'ns_media_library_layout',
                            options: [
                                [
                                    'label' => __( 'Modern Library' ),
                                    'value' => 'modern',
                                ],
                                [
                                    'label' => __( 'Legacy Tabs' ),
                                    'value' => 'legacy',
                                ],
                            ],
                            value: ns()->option->get( 'ns_media_library_layout', 'modern' ),
                            description: __( 'Choose which media manager interface should be used on the dashboard media page.' ),
                        ),
                    )
                ),
            )
        );
    }
}
