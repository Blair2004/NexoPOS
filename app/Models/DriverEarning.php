<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverEarning extends NsModel
{
    protected $table = 'nexopos_drivers_earnings';

    const PAYMENT_TYPE_FIXED = 'fixed';
    const PAYMENT_TYPE_PERCENTAGE = 'percentage';

    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'driver_id',
        'order_id',
        'payment_type',
        'delivery_fee',
        'rate_value',
        'earning_amount',
        'status',
        'delivery_date',
        'paid_date',
        'notes'
    ];

    protected $casts = [
        'delivery_fee' => 'float',
        'rate_value' => 'float',
        'earning_amount' => 'float',
        'delivery_date' => 'datetime',
        'paid_date' => 'datetime',
    ];

    /**
     * Get the driver that owns the earning.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    /**
     * Get the order associated with the earning.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Calculate earning amount based on payment type and rate.
     */
    public function calculateEarning(): float
    {
        if ($this->payment_type === self::PAYMENT_TYPE_FIXED) {
            return (float) $this->rate_value;
        } elseif ($this->payment_type === self::PAYMENT_TYPE_PERCENTAGE) {
            return ($this->delivery_fee * $this->rate_value) / 100;
        }

        return 0;
    }

    /**
     * Mark earning as paid.
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_date' => now()
        ]);
    }

    /**
     * Mark earning as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED
        ]);
    }

    /**
     * Scope for pending earnings.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for paid earnings.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope for a specific driver.
     */
    public function scopeForDriver($query, Driver $driver)
    {
        return $query->where('driver_id', $driver->id );
    }
}
