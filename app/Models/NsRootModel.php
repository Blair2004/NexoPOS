<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class NsRootModel extends Model
{
    /**
     * While saving model, this will
     * use the timezone defined on the settings
     */
    public function freshTimestamp()
    {
        return ns()->date->getNow();
    }
}
