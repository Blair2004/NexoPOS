<?php

namespace App\Traits;

use App\Models\CustomerAddress;
use App\Models\CustomerBillingAddress;
use App\Models\CustomerShippingAddress;

trait NsCustomerAddress
{
    public function addresses()
    {
        return $this->hasMany(
            related: CustomerAddress::class,
            foreignKey: 'customer_id',
            localKey: 'id'
        );
    }

    public function billing()
    {
        return $this->hasOne(
            related: CustomerBillingAddress::class,
            foreignKey: 'customer_id',
            localKey: 'id'
        );
    }

    public function shipping()
    {
        return $this->hasOne(
            related: CustomerShippingAddress::class,
            foreignKey: 'customer_id',
            localKey: 'id'
        );
    }
}
