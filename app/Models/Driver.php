<?php

namespace App\Models;

use App\Models\Scopes\DriverScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

#[ScopedBy([ DriverScope::class])]
class Driver extends User
{
    const STATUS_AVAILABLE = 'available';
    const STATUS_BUSY = 'busy';
    const STATUS_OFFLINE = 'offline';
    const STATUS_DISABLED = 'disabled';
    
    public function status()
    {
        return $this->hasOne( DriverStatus::class );
    }
}
