<?php

namespace App\Services;

use App\Models\Option;

class UserOptions extends Options
{
    protected $user_id;

    public function __construct( $user_id )
    {
        $this->user_id = $user_id;
        parent::__construct();
    }

    public function option()
    {
        return Option::where( 'user_id', $this->user_id );
    }

    public function beforeSave( $option )
    {
        $option->user_id = $this->user_id;

        /**
         * sanitizing input to remove
         * all script tags
         */
        $option->value = strip_tags( $option->value );

        return $option;
    }
}
