<?php

namespace App\Events;

use App\Fields\ProcurementFields;
use App\Fields\UnitsFields;
use App\Fields\UnitsGroupsFields;
use App\Services\Validation;

class ValidationEvent
{
    public function __construct( public Validation $validation )
    {
        // ...
    }

    /**
     * Extract the unit validation
     * fields
     *
     * @param void
     * @return array of validation
     */
    public function unitsGroups()
    {
        return $this->validation->from( UnitsGroupsFields::class )
            ->extract( 'get' );
    }

    public function unitValidation()
    {
        return $this->validation->from( UnitsFields::class )
            ->extract( 'get' );
    }

    public function procurementValidation()
    {
        return $this->validation->from( ProcurementFields::class )
            ->extract( 'get' );
    }
}
