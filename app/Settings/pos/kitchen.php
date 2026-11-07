<?php

/**
 * Kitchen Display System (KDS) — MVP settings.
 * Zones are product categories: dishes are grouped on the kitchen
 * screen by the category of their product, so no zone mapping
 * is configured here yet.
 */

use App\Classes\FormInput;

return [
    'label' => __( 'Kitchen Display' ),
    'fields' => [
        FormInput::switch(
            label: __( 'Kitchen Display Enabled' ),
            name: 'ns_kds_enabled',
            options: [
                ['label' => __( 'Yes' ), 'value' => 'yes'],
                ['label' => __( 'No' ), 'value' => 'no'],
            ],
            value: ns()->option->get( 'ns_kds_enabled', 'yes' ),
            description: __( 'Show the Kitchen Display menu and allow the kitchen screen to poll new orders.' )
        ),

        FormInput::select(
            label: __( 'Refresh Interval' ),
            name: 'ns_kds_refresh_interval',
            options: [
                ['label' => __( '10 Seconds' ), 'value' => '10'],
                ['label' => __( '15 Seconds' ), 'value' => '15'],
                ['label' => __( '30 Seconds' ), 'value' => '30'],
                ['label' => __( '60 Seconds' ), 'value' => '60'],
            ],
            value: ns()->option->get( 'ns_kds_refresh_interval', '15' ),
            description: __( 'How often the kitchen screen checks for new orders. The screen uses polling, not realtime.' )
        ),

        FormInput::select(
            label: __( 'Late Order After' ),
            name: 'ns_kds_late_after_minutes',
            options: [
                ['label' => __( '10 Minutes' ), 'value' => '10'],
                ['label' => __( '15 Minutes' ), 'value' => '15'],
                ['label' => __( '20 Minutes' ), 'value' => '20'],
                ['label' => __( '30 Minutes' ), 'value' => '30'],
            ],
            value: ns()->option->get( 'ns_kds_late_after_minutes', '15' ),
            description: __( 'Orders waiting longer than this are highlighted as late on the kitchen screen.' )
        ),

        FormInput::switch(
            label: __( 'Auto Print Kitchen Ticket' ),
            name: 'ns_kds_auto_print',
            options: [
                ['label' => __( 'Yes' ), 'value' => 'yes'],
                ['label' => __( 'No' ), 'value' => 'no'],
            ],
            value: ns()->option->get( 'ns_kds_auto_print', 'no' ),
            description: __( 'When enabled, the kitchen screen automatically opens the print dialog for each new order. The browser print dialog still has to be confirmed on that terminal.' )
        ),
    ],
];
