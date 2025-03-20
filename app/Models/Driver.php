<?php

namespace App\Models;

use App\Models\Scopes\DriverScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

#[ScopedBy([ DriverScope::class])]
class Driver extends User
{
    public function status()
    {
        return $this->hasOne( DriverStatus::class );
    }
}
