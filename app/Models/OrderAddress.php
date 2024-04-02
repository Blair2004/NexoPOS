<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property int            $author
 * @property string         $uuid
 * @property \Carbon\Carbon $updated_at
 */
class OrderAddress extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'orders_addresses';
}
