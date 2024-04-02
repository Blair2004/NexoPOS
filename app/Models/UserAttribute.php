<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int   $id
 * @property int   $user_id
 * @property mixed $avatar_link
 * @property mixed $theme
 * @property mixed $language
 */
class UserAttribute extends NsRootModel
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'nexopos_users_attributes';

    protected $fillable = [ 'language' ];
}
