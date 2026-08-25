<?php

namespace App\Services;

use App\Models\Option;
use Illuminate\Database\Eloquent\Builder;

class UserOptions extends Options
{
    protected int $user_id;

    public function __construct( int $user_id )
    {
        $this->user_id = $user_id;

        parent::__construct();
    }

    public function option(): Builder
    {
        return Option::where( 'user_id', $this->user_id );
    }

    /**
     * Set Option
     **/
    public function set( string $key, mixed $value, ?string $expiration = null )
    {
        if ( isset( $this->rawOptions[ $key ] ) ) {
            $this->rawOptions[ $key ]->value = $value;
            $this->rawOptions[ $key ]->expire_on = $expiration;

            $this->encodeOptionValue( $this->rawOptions[ $key ], $value );

            $this->rawOptions[ $key ]->save();
        } else {
            $option = new Option;
            $option->key = trim( strtolower( $key ) );
            $option->array = false;
            $option->value = $value;
            $option->user_id = $this->user_id;
            $option->expire_on = $expiration;

            $this->encodeOptionValue( $option, $value );

            $option->save();
            $this->rawOptions[ $key ] = $option;
        }
    }
}
