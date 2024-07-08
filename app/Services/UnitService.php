<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\Unit;
use App\Models\UnitGroup;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class UnitService
{
    public function __construct( public CurrencyService $currency )
    {
        // ...
    }

    public function createGroup( $data )
    {
        $unitGroup = new UnitGroup;
        $unitGroup->name = $data[ 'name' ];
        $unitGroup->description = @$data[ 'description' ] ?: '';
        $unitGroup->author = Auth::id();
        $unitGroup->save();

        return [
            'status' => 'success',
            'message' => __( 'The Unit Group has been created.' ),
            'data' => [
                'group' => $unitGroup,
            ],
        ];
    }

    public function updateGroup( $id, $data )
    {
        $unitGroup = UnitGroup::findOrFail( $id );
        $unitGroup->name = $data[ 'name' ];
        $unitGroup->description = @$data[ 'description' ] ?: '';
        $unitGroup->author = Auth::id();
        $unitGroup->save();

        return [
            'status' => 'success',
            'message' => sprintf( __( 'The unit group %s has been updated.' ), $unitGroup->name ),
            'data' => [
                'group' => $unitGroup,
            ],
        ];
    }

    /**
     * get a specific defined group
     *
     * @param int group id
     * @return array|UnitGroup
     */
    public function getGroups( $id = null )
    {
        if ( $id !== null ) {
            $group = UnitGroup::find( $id );

            if ( ! $group instanceof UnitGroup ) {
                throw new Exception( __( 'Unable to find the unit group to which this unit is attached.' ) );
            }

            return $group;
        } else {
            return UnitGroup::get();
        }
    }

    /**
     * Get sibling units
     * Used to retreive other units that belongs to
     * the same unit group and the defined unit.
     */
    public function getSiblingUnits( Unit $unit )
    {
        $unit->load( [ 'group.units' => function ( $query ) use ( $unit ) {
            $query->whereNotIn( 'id', [ $unit->id ] );
        }] );

        return $unit->group->units;
    }

    /**
     * Create a unit using the provided informations
     *
     * @param array unit array
     * @return array response
     */
    public function createUnit( $data )
    {
        $group = $this->getGroups( $data[ 'group_id' ] );

        /**
         * Let's make sure that if the
         * unit is set as base unit, all
         * other units changes
         */
        if ( $data[ 'base_unit' ] === true ) {
            $group->units->map( function ( $unit ) {
                $unit->base_unit = false;
                $unit->save();
            } );
        }

        $unit = new Unit;
        $fields = $data;

        foreach ( $fields as $field => $value ) {
            $unit->$field = $value;
        }

        $unit->author = Auth::id();
        $unit->save();

        return [
            'status' => 'success',
            'message' => __( 'The unit has been saved.' ),
            'data' => compact( 'unit' ),
        ];
    }

    /**
     * Get all units defined
     *
     * @return Collection|Unit
     */
    public function get( $id = null )
    {
        if ( $id !== null ) {
            $unit = Unit::find( $id );
            if ( ! $unit instanceof Unit ) {
                throw new NotFoundException( [
                    'status' => 'error',
                    'message' => __( 'Unable to find the Unit using the provided id.' ),
                ] );
            }

            return $unit;
        }

        return Unit::get();
    }

    /**
     * get a unit that uses a specific
     * identififer
     *
     * @param string
     * @return Unit
     */
    public function getUsingIdentifier( $identifier )
    {
        return Unit::identifier( $identifier )->first();
    }

    /**
     * update a specific unit
     * using the provided id
     *
     * @param int id
     * @param array data
     * @return array response
     */
    public function updateUnit( $id, $fields )
    {
        $unit = Unit::findOrFail( $id );

        try {
            $group = $this->getGroups( $fields[ 'group_id' ] );
        } catch ( \Exception $exception ) {
            throw new NotFoundException( [
                'status' => 'error',
                'message' => __( 'Unable to find the unit group to which this unit is attached.' ),
            ] );
        }

        /**
         * Let's make sure that if the
         * unit is set as base unit, all
         * other units changes
         */
        if ( $fields[ 'base_unit' ] === true ) {
            $group->units->map( function ( $unit ) use ( $id ) {
                if ( $unit->id !== $id ) {
                    $unit->base_unit = false;
                    $unit->save();
                }
            } );
        }

        foreach ( $fields as $field => $value ) {
            $unit->$field = $value;
        }

        $unit->author = Auth::id();
        $unit->save();

        return [
            'status' => 'success',
            'message' => __( 'The unit has been updated.' ),
            'data' => compact( 'unit' ),
        ];
    }

    /**
     * retrieve a group of a
     * specific unit using an id
     *
     * @param int Parent Group
     * @return UnitGroup
     */
    public function getUnitParentGroup( $id )
    {
        $unit = Unit::findOrFail( $id );

        return $unit->group;
    }

    /**
     * get the single base unit defined
     * for a specific group
     *
     * @return Unit
     */
    public function getBaseUnit( UnitGroup $group )
    {
        $baseUnit = UnitGroup::find( $group->id )
            ->units()
            ->get()
            ->filter( function ( $unit ) {
                return $unit->base_unit;
            } );

        $unitCount = $baseUnit->count();

        if ( $unitCount > 1 ) {
            throw new Exception( sprintf( __( 'The unit group %s has more than one base unit' ), $group->name ) );
        } elseif ( $unitCount === 0 ) {
            throw new Exception( sprintf( __( 'The unit group %s doesn\'t have a base unit' ), $group->name ) );
        }

        return $baseUnit->first();
    }

    /**
     * return what is the exact total base unit
     * value of 2 Unit instance provided
     *
     * @param Unit base unit
     */
    public function computeBaseUnit( Unit $unit, Unit $base, $quantity )
    {
        $value = $this->currency->value( $base->value )
            ->multiplyBy( $unit->value )
            ->get();

        return $this->currency->value( $value )
            ->multiplyBy( $quantity )
            ->get();
    }

    /**
     * Checks wether two units belongs to the same unit group.
     */
    public function isFromSameGroup( Unit $from, Unit $to ): bool
    {
        return $from->group_id === $to->group_id;
    }

    /**
     * Will returns the final quantity of a converted unit.
     */
    public function getConvertedQuantity( Unit $from, Unit $to, float $quantity ): float|int
    {
        return ns()->currency->define(
            ns()->currency
                ->define( $from->value )
                ->multipliedBy( $quantity )
                ->toFloat()
        )
            ->dividedBy( $to->value )
            ->toFloat();
    }

    /**
     * Using the source unit, will return the purchase price
     * for a converted unit.
     */
    public function getPurchasePriceFromUnit( $purchasePrice, Unit $from, Unit $to )
    {
        return ns()->currency->define(
            ns()->currency->define( $purchasePrice )->dividedBy( $from->value )->toFloat()
        )->multipliedBy( $to->value )->toFloat();
    }

    public function deleteUnit( $id )
    {
        /**
         * @todo we might check if the
         * unit is currently in use
         */
        $unit = $this->get( $id );
        $unit->delete();

        return [
            'status' => 'success',
            'message' => __( 'The unit has been deleted.' ),
        ];
    }
}
