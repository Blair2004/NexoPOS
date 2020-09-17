<?php
use App\Services\Helper;

return [
    'label'     =>  __( 'SMS' ),
    'fields'    =>  [
        [
            'label'     =>  __( 'SMS Provider' ),
            'name'      =>  'ns_providers_sms',
            'value'     =>  $options->get( 'ns_providers_sms' ),
            'type'      =>  'select',
            'options'       =>  Helper::kvToJsOptions([
                'twilio'    =>  __( 'Twilio' ),
            ]),
            'description'   =>  __( 'Select the sms provider used on the system.' )
        ]
    ]
];