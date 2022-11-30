<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasFactory;

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: function( $value ) {
                return $value === null ? null : ns()->date->copy()->parse( $value )->diffForHumans();
            }
        );
    }

    protected function expiresAt(): Attribute
    {
        return Attribute::make(
            get: function( $value ) {
                return $value === null ? null : ns()->date->copy()->parse( $value )->diffForHumans();
            }
        );
    }

    protected function lastUsedAt(): Attribute
    {
        return Attribute::make(
            get: function( $value ) {
                return $value === null ? null : ns()->date->copy()->parse( $value )->diffForHumans();
            }
        );
    }
}
