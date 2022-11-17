<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property integer $id
 * @property integer $user_id
 * @property string $gender
*/
class UserAttribute extends NsRootModel
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'nexopos_users_attributes';

    protected $fillable = [ 'language' ];
}
