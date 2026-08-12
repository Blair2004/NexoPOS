<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed  $class_name
 * @property int    $user_id
 * @property Carbon $updated_at
 */
class UserWidget extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [ 'column', 'identifier', 'class_name', 'position', 'user_id', 'layout' ];

    protected $table = 'nexopos_users_widgets';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'user_id' => 'integer',
        ];
    }
}
