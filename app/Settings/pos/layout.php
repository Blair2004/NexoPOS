<?php

use App\Services\Helper;

return [
    'label' =>  __( 'Layout' ),
    'fields'    =>  [
        [
            'name'              =>  'ns_pos_layout',
            'value'             =>  $options->get( 'ns_pos_layout' ),
            'options'           =>  Helper::kvToJsOptions([
                'grocery_shop'      =>  __( 'Retail Layout' ),
                'clothing_shop'     =>  __( 'Clothing Shop' ),
            ]),
            'label'         =>  __( 'POS Layout' ), 
            'type'          =>  'select',
            'description'   =>  __( 'Change the layout of the POS.' ),
        ], 
    ]
];