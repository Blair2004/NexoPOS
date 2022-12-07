<?php

namespace App\Models;

use App\Traits\NsUuid;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed $class_name
 * @property integer $user_id
 * @property \Carbon\Carbon $updated_at
*/
class UserWidget extends Model
{
    use HasFactory, HasUuids;

    protected $fillable     =   [ 'column', 'identifier', 'id', 'column', 'position', 'user_id' ];

    protected $table    =   'nexopos_users_widgets';
}
