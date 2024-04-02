<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property mixed          $name
 * @property mixed          $status
 * @property string         $description
 * @property int            $used_by
 * @property int            $author
 * @property float          $balance
 * @property mixed          $uuid
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Register extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'registers';

    const STATUS_OPENED = 'opened';

    const STATUS_CLOSED = 'closed';

    const STATUS_DISABLED = 'disabled';

    const STATUS_INUSE = 'in-use';

    public function scopeClosed( $query )
    {
        return $query->where( 'status', self::STATUS_CLOSED );
    }

    public function scopeOpened( $query )
    {
        return $query->where( 'status', self::STATUS_OPENED );
    }

    public function scopeInUse( $query )
    {
        return $query->where( 'status', self::STATUS_INUSE );
    }

    public function scopeDisabled( $query )
    {
        return $query->where( 'status', self::STATUS_DISABLED );
    }

    public function scopeUsedBy( $query, $user )
    {
        return $query->where( 'used_by', $user );
    }

    public function history()
    {
        return $this->hasMany( RegisterHistory::class, 'register_id', 'id' );
    }
}
