<?php
function CrudColumn( $label, $identifier, $sort = true, $direction = '', $attributes = [] ) {
    return [
        'label' =>  $label,
        'identifier' => $identifier,
        '$sort' =>  $sort,
        '$direction' => $direction,
        '$attributes' => $attributes
    ];
}