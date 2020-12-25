<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RolePermission extends NsRootModel
{
    use HasFactory;
    protected $table    =   'nexopos_role_permission';

    public $timestamps   =   false;
}