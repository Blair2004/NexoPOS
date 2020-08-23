<?php
return [
    'label'     =>      __( 'Security' ),
    'fields'    =>      [
        [
            'label'         =>  __( 'Public Name' ),
            'name'          =>  'public_name',
            'type'          =>  'text',
            'description'   =>  __( 'Define what is the user public name. If not provided, the username is used instead.' ),
        ], [
            'label'         =>  __( 'Password' ),
            'name'          =>  'password',
            'type'          =>  'password',
            'description'   =>  __( 'Change your password with a better stronger password.' ),
            'validation'    =>  'min:6',
        ], [
            'label'         =>  __( 'Password Confirmation' ),
            'name'          =>  'password_confirm',
            'type'          =>  'password',
            'description'   =>  __( 'Change your password with a better stronger password.' ),
            'validation'    =>  'min:6|same:security_password',
        ], 
    ]
];