<?php

return [
    'label' => __( 'General' ),
    'fields' => [
        [
            'label' => __( 'Public Name' ),
            'name' => 'public_name',
            'value' => ns()->option->get( 'public_name' ),
            'type' => 'text',
            'description' => __( 'Define what is the user public name. If not provided, the username is used instead.' ),
        ],
    ],
];
