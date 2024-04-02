<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property string         $identifier
 * @property int            $author
 * @property string         $description
 * @property bool           $readonly
 * @property \Carbon\Carbon $updated_at
 */
class PaymentType extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'payments_types';

    public function scopeActive( $query )
    {
        return $query->where( 'active', true );
    }

    public function scopeIdentifier( $query, $identifier )
    {
        return $query->where( 'identifier', $identifier );
    }
}
