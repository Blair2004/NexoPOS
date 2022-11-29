<?php

namespace App\Models;

use App\Casts\CurrencyCast;
use App\Casts\ExpenseOccurrenceCast;
use App\Casts\ExpenseTypeCast;
use App\Casts\YesNoBoolCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property integer $id
 * @property string $type
 * @property integer $author
 * @property string $description
 * @property float $value
 * @property bool $active
 * @property \Carbon\Carbon $scheduled_date
*/
class Expense extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'expenses';

    protected $casts = [
        'recurring'     => 'boolean',
        'active'        => 'boolean'
    ];

    const OCCURRENCE_START_OF_MONTH = 'month_starts';

    const OCCURRENCE_END_OF_MONTH = 'month_ends';

    const OCCURRENCE_MIDDLE_OF_MONTH = 'month_mid';

    const OCCURRENCE_SPECIFIC_DAY = 'on_specific_day';

    const OCCURRENCE_X_AFTER_MONTH_STARTS = 'x_after_month_starts';

    const OCCURRENCE_X_BEFORE_MONTH_ENDS = 'x_before_month_ends';

    const TYPE_SCHEDULED = 'ns.scheduled-expenses';

    const TYPE_RECURRING = 'ns.recurring-expenses';

    const TYPE_SALARY = 'ns.salary-expenses';

    const TYPE_DIRECT = 'ns.direct-expenses';

    protected static function boot(){
        parent::boot();

        static::addGlobalScope( 'category', function($builder){
            $builder->with( 'category' );
        });
    }

    public function category()
    {
        return $this->belongsTo( AccountType::class, 'category_id' );
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
