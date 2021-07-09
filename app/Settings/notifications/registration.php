<?php

use App\Services\Helper;

return [
    'label' =>  __( 'Registration' ),
    'fields'    =>  [
            [
            'type'          =>  'switch',
            'name'          =>  'ns_notifications_registrations_notify_administrators',
            'label'         =>  __( 'Notify Administrators' ),
            'options'       =>  Helper::kvToJsOptions([ __( 'No' ), __( 'Yes' ) ]),
            'value'         =>  intval( $options->get( 'ns_notifications_registrations_notify_administrators' ) ),
            'description'   =>  __( 'Will notify administrator everytime a new user is registrated.' )
        ], [
            'type'          =>  'text',
            'name'          =>  'ns_notifications_registrations_administrator_email_title',
            'label'         =>  __( 'Administrator Notification Title' ),
            'value'         =>  $options->get( 'ns_notifications_registrations_administrator_email_title' ),
            'description'   =>  __( 'Determine the title of the email send to the administrator.' )
        ], [
            'type'          =>  'textarea',
            'name'          =>  'ns_notifications_registrations_administrator_email_body',
            'label'         =>  __( 'Administrator Notification Content' ),
            'value'         =>  $options->get( 'ns_notifications_registrations_administrator_email_body' ),
            'description'   =>  __( 'Determine what is the message that will be send to the administrator.' )
        ], [
            'type'          =>  'switch',
            'name'          =>  'ns_notifications_registrations_notify_user',
            'label'         =>  __( 'Notify User' ),
            'options'       =>  Helper::kvToJsOptions([ __( 'No' ), __( 'Yes' ) ]),
            'value'         =>  intval( $options->get( 'ns_notifications_registrations_notify_user' ) ),
            'description'   =>  __( 'Notify a user when his account is successfully created.' )
        ], [
            'type'          =>  'text',
            'name'          =>  'ns_notifications_registrations_user_email_title',
            'label'         =>  __( 'User Registration Title' ),
            'value'         =>  $options->get( 'ns_notifications_registrations_user_email_title' ),
            'description'   =>  __( 'Determine the title of the mail send to the user when his account is created and active.' )
        ], [
            'type'          =>  'textarea',
            'name'          =>  'ns_notifications_registrations_user_email_body',
            'label'         =>  __( 'User Registration Content' ),
            'value'         =>  $options->get( 'ns_notifications_registrations_user_email_body' ),
            'description'   =>  __( 'Determine the body of the mail send to the user when his account is created and active.' )
        ], [
            'type'          =>  'text',
            'name'          =>  'ns_notifications_registrations_user_activate_title',
            'label'         =>  __( 'User Activate Title' ),
            'value'         =>  $options->get( 'ns_notifications_registrations_user_activate_title' ),
            'description'   =>  __( 'Determine the title of the mail send to the user.' )
        ], [
            'type'          =>  'textarea',
            'name'          =>  'ns_notifications_registrations_user_activate_body',
            'label'         =>  __( 'User Activate Content' ),
            'value'         =>  $options->get( 'ns_notifications_registrations_user_activate_body' ),
            'description'   =>  __( 'Determine the mail that will be send to the use when his account requires an activation.' )
        ],
    ]
];