<?php

namespace App\Models;

use App\Models\Scopes\DriverScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ScopedBy([ DriverScope::class])]
class Driver extends User
{
    const STATUS_AVAILABLE = 'available';
    const STATUS_BUSY = 'busy';
    const STATUS_OFFLINE = 'offline';
    const STATUS_DISABLED = 'disabled';
    
    public function status(): HasOne
    {
        return $this->hasOne( DriverStatus::class );
    }

    /**
     * Get all earnings for this driver.
     */
    public function earnings(): HasMany
    {
        return $this->hasMany(DriverEarning::class, 'driver_id');
    }

    /**
     * Get pending earnings for this driver.
     */
    public function pendingEarnings(): HasMany
    {
        return $this->earnings()->where('status', DriverEarning::STATUS_PENDING);
    }

    /**
     * Get total earnings amount for this driver.
     */
    public function getTotalEarnings(): float
    {
        return $this->earnings()
            ->where('status', DriverEarning::STATUS_PAID)
            ->sum('earning_amount');
    }

    /**
     * Get pending earnings amount for this driver.
     */
    public function getPendingEarnings(): float
    {
        return $this->pendingEarnings()->sum('earning_amount');
    }
}
