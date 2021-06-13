<?php
namespace App\Services;

use App\Models\Unit;
use App\Models\UnitGroup;
use Database\Seeders\CustomerGroupSeeder;
use Database\Seeders\DefaultProviderSeeder;
use Illuminate\Support\Facades\Auth;

class DemoCoreService
{
    protected $categoryService;
    protected $productService;
    protected $user;

    /**
     * Will configure a basic unit system
     * @param void
     * @return void
     */
    public function prepareDefaultUnitSystem()
    {
        $group  =   UnitGroup::where( 'name', __( 'Countable' ) )->first();

        if ( ! $group instanceof UnitGroup ) {
            $group          =   new UnitGroup;
            $group->name    =   __( 'Countable' );
            $group->author  =   Auth::id();
            $group->save();
        }
        
        $unit       =   Unit::identifier( 'piece' )->first();

        if ( ! $unit instanceof Unit ) {
            $unit               =   new Unit;
            $unit->name         =   __( 'Piece' );
            $unit->identifier   =   'piece';
            $unit->description  =   '';
            $unit->author       =   Auth::id();
            $unit->group_id     =   $group->id;
            $unit->base_unit    =   true;
            $unit->value        =   1;
            $unit->save();
        }
    }

    public function createCustomers()
    {
        (new CustomerGroupSeeder)->run();
    }

    public function createProviders()
    {
        (new DefaultProviderSeeder)->run();
    }
}