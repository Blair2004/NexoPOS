<?php
namespace App\Fields;

use App\Classes\Form;
use App\Classes\FormInput;
use App\Services\FieldsService;
use App\Services\Helper;

class DriverStatusFields extends FieldsService
{
    /**
     * The unique identifier of the form
     */
    const IDENTIFIER = 'driver-status-fields';

    /**
     * This will ensure the fields are automatically loaded.
     */
    const AUTOLOAD = true;

    public function get() 
    {
        return Form::fields(
            FormInput::select(
                label: __( 'Status' ),
                name: 'status',
                options: Helper::kvToJsOptions([
                    'available' => __( 'Available' ),
                    'busy' => __( 'Busy' ),
                    'offline' => __( 'Offline' ),
                    'disabled' => __( 'Disabled' ),
                ]),
                description: __( 'Select the status of the driver.' ),
                validation: 'required'
            )
        );
    }
}