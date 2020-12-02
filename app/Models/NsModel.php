<?php

namespace App\Models;

use App\Classes\Hook;
use Illuminate\Database\Eloquent\Model;

abstract class NsModel extends Model
{
    public function getTable()
    {
        return Hook::filter( 'ns-model-table', $this->table );
    }
}
