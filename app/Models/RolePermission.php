<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

use App\Models\Permission;

class RolePermission extends Model
{
    protected $table    =   'nexopos_role_permission';
}