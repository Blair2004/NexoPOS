<?php

use App\Services\Helper;

$audios = Helper::kvToJsOptions( [
    '' => __( 'Disabled' ),
    url( '/audio/bubble.mp3' ) => __( 'Bubble' ),
    url( '/audio/ding.mp3' ) => __( 'Ding' ),
    url( '/audio/pop.mp3' ) => __( 'Pop' ),
    url( '/audio/cash-sound.mp3' ) => __( 'Cash Sound' ),
] );

return [
    'label' => __( 'Layout' ),
    'fields' => [
        [
            'name' => 'ns_pos_layout',
            'value' => ns()->option->get( 'ns_pos_layout' ),
            'options' => Helper::kvToJsOptions( [
                'grocery_shop' => __( 'Retail Layout' ),
                'clothing_shop' => __( 'Clothing Shop' ),
            ] ),
            'label' => __( 'POS Layout' ),
            'type' => 'select',
            'description' => __( 'Change the layout of the POS.' ),
        ], [
            'name' => 'ns_pos_complete_sale_audio',
            'value' => ns()->option->get( 'ns_pos_complete_sale_audio' ),
            'options' => $audios,
            'label' => __( 'Sale Complete Sound' ),
            'type' => 'select-audio',
            'description' => __( 'Change the layout of the POS.' ),
        ], [
            'name' => 'ns_pos_new_item_audio',
            'value' => ns()->option->get( 'ns_pos_new_item_audio' ),
            'options' => $audios,
            'label' => __( 'New Item Audio' ),
            'type' => 'select-audio',
            'description' => __( 'The sound that plays when an item is added to the cart.' ),
        ],
    ],
];
