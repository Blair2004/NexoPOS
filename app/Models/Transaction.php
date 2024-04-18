<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property mixed          $name
 * @property int            $account_id
 * @property string         $description
 * @property int            $media_id
 * @property float          $value
 * @property bool           $recurring
 * @property mixed          $type             / "income" or "expense"
 * @property bool           $active
 * @property int            $group_id
 * @property mixed          $occurrence
 * @property mixed          $occurrence_value
 * @property \Carbon\Carbon $scheduled_date
 * @property int            $author
 * @property mixed          $uuid
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Transaction extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'transactions';

    protected $casts = [
        'recurring' => 'boolean',
        'occurrence_value' => 'integer',
        'active' => 'boolean',
    ];

    const OCCURRENCE_START_OF_MONTH = 'month_starts';

    const OCCURRENCE_END_OF_MONTH = 'month_ends';

    const OCCURRENCE_MIDDLE_OF_MONTH = 'month_mid';

    const OCCURRENCE_SPECIFIC_DAY = 'on_specific_day';

    const OCCURRENCE_X_AFTER_MONTH_STARTS = 'x_after_month_starts';

    const OCCURRENCE_X_BEFORE_MONTH_ENDS = 'x_before_month_ends';

    const TYPE_SCHEDULED = 'ns.scheduled-transaction';

    const TYPE_RECURRING = 'ns.recurring-transaction';

    const TYPE_ENTITY = 'ns.entity-transaction';

    const TYPE_DIRECT = 'ns.direct-transaction';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope( 'account', function ( $builder ) {
            $builder->with( 'account' );
        } );
    }

    public function account()
    {
        return $this->belongsTo( TransactionAccount::class, 'account_id' );
    }

    public function histories()
    {
        return $this->hasMany( TransactionHistory::class, 'transaction_id' );
    }

    public function scopeScheduled( $query )
    {
        return $query->where( 'type', self::TYPE_SCHEDULED );
    }

    public function scopeScheduledAfterDate( $query, $date )
    {
        return $query->where( 'scheduled_date', '>=', $date );
    }

    public function scopeScheduledBeforeDate( $query, $date )
    {
        return $query->where( 'scheduled_date', '<=', $date );
    }

    public function scopeRecurring( $query )
    {
        return $query->where( 'recurring', true );
    }

    public function scopeNotRecurring( $query )
    {
        return $query->where( 'recurring', false );
    }

    public function scopeActive( $query )
    {
        return $query->where( 'active', true );
    }
}
