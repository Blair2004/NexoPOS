<?php
namespace App\Events;

use App\Fields\UnitsFields;
use App\Services\Validation;
use App\Fields\ProcurementFields;
use App\Fields\UnitsGroupsFields;

class ValidationEvent 
{
    protected $validation;

    public function __construct( Validation $validation )
    {
        $this->validation  =   $validation;
    }

    /**
     * Extract the unit validation
     * fields
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