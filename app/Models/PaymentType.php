<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentType extends NsModel
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'payments_types';

    public function scopeActive( $query )
    {
        return $query->where( 'active', true );
    }

    public function scopeIdentifier( $query, $identifier )
    {
        return $query->where( 'identifier', $identifier );
    }
}