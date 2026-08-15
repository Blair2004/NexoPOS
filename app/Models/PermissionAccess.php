<?php

namespace App\Models;

use Illuminate\Support\Carbon;

/**
 * PermissionAccess Model
 *
 * @property int         $requester_id
 * @property int         $granter_id
 * @property string      $status
 * @property string      $permission
 * @property string|null $url
 * @property Carbon|null $approved_at
 * @property Carbon|null $expired_at
 */
class PermissionAccess extends NsModel
{
    protected $table = 'nexopos_' . 'permissions_access';

    /**
     * The permission access is granted.
     *
     * @param string
     */
    const GRANTED = 'granted';

    /**
     * The permission access is denied.
     *
     * @param string
     */
    const DENIED = 'denied';

    /**
     * The permission access is pending.
     *
     * @param string
     */
    const PENDING = 'pending';

    /**
     * The permission access is expired.
     *
     * @param string
     */
    const EXPIRED = 'expired';

    /**
     * The permission access is used.
     *
     * @param string
     */
    const USED = 'used';

    public function perm()
    {
        return $this->belongsTo( Permission::class, 'permission', 'namespace' );
    }
}
