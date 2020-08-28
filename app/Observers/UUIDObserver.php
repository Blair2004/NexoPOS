<?php
namespace App\Observers;

use Illuminate\Support\Str;

class UUIDObserver
{
    public function created( $model )
    {
        $model->uuid    =   Str::uuid();
        $model->save();
    }
}