<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * @property int            $tokenable_id
 * @property mixed          $token
 * @property string         $abilities
 * @property \Carbon\Carbon $updated_at
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasFactory;

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: function ( $value ) {
                return $value === null ? null : ns()->date->copy()->parse( $value )->diffForHumans();
            }
        );
    }

    protected function expiresAt(): Attribute
    {
        return Attribute::make(
            get: function ( $value ) {
                return $value === null ? null : ns()->date->copy()->parse( $value )->diffForHumans();
            }
        );
    }

    protected function lastUsedAt(): Attribute
    {
        return Attribute::make(
            get: function ( $value ) {
                return $value === null ? null : ns()->date->copy()->parse( $value )->diffForHumans();
            }
        );
    }
}
