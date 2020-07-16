<?php
namespace App\Observers;

use Illuminate\Support\Facades\DB;

use App\Models\Permission;
use App\Models\Role;

class RoleObserver 
{
    /**
     * Handle the role "created" event.
     *
     * @param  Role  $role
     * @return void
     */
    public function created( Role $role )
    {
        //
    }

    /**
     * Handle the role "updated" event.
     *
     * @param  Role  $role
     * @return void
     */
    public function updated( Role $role )
    {
        //
    }

    /**
     * Handle the role "deleted" event.
     *
     * @param  Role  $role
     * @return void
     */
    public function deleted( Role $role )
    {
        DB::table( 'tendoo_role_permission' )
            ->where( 'role_id', $role->id )
            ->delete();        
    }
}