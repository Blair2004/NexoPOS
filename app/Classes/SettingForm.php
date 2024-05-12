<?php

namespace App\Classes;

class SettingForm extends CrudForm
{
    public static function form( $title, $description = '', $tabs = [] )
    {
        return compact( 'title', 'description', 'tabs' );
    }
}
