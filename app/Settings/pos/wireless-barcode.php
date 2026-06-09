<?php

use App\Classes\Form;
use App\Classes\FormInput;
use App\Classes\SettingForm;

return SettingForm::tab(
    identifier: 'wireless-barcode',
    label: __( 'Wireless Barcode Scanner' ),
    component: 'NsWireLessBarcodeSettingsTab',
    fields: Form::fields(
        FormInput::text(
            label: __( 'Websocket Server URL' ),
            name: 'ns_pos_websocket_domain',
            value: ns()->option->get( 'ns_pos_websocket_domain', 'localhost:8080' ),
            description: __( 'Define the URL of the websocket server that will receive the scanned barcodes from ScanMate.' )
        ),
        FormInput::password(
            label: __( 'Websocket Server Password' ),
            name: 'ns_pos_websocket_server_password',
            value: ns()->option->get( 'ns_pos_websocket_server_password', '' ),
            description: __( 'Define the password for authenticating with the websocket server to ensure secure communication.' )
        )
    )
);