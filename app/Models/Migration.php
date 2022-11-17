<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $batch
 * @property string $type
*/
class Migration extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'migrations';

    protected $migration;
}
