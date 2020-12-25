<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ModuleMigration extends NsModel
{
    use HasFactory;

    protected $table    =   'nexopos_' . 'modules_migrations';
    public $timestamps  =   false;

    public function scopeNamespace( $query, $namespace )
    {
        return $query->where( 'namespace', $namespace );
    }
}
