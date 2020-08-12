<?php
use App\Services\Helper;

return [
    'label'     =>  __( 'Email' ),
    'fields'    =>  [
        [
            'label'         =>  __( 'Email Provider' ),
            'type'          =>  'select',
            'name'          =>  'ns_providers_email',
            'options'       =>  Helper::kvToJsOptions([
                'mailgun'   =>  __( 'Mailgun' ),
            ]),
            'description'   =>  __( 'Select the email provided used on the system.' )
        ]
    ]
];