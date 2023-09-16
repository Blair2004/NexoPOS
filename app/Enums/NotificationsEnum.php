<?php

namespace App\Enums;

/**
 * @todo should be moved to the model Notification.
 */
class NotificationsEnum
{
    const NSCRONDISABLED = 'ns.notifications.cron-disabled';

    const NSWORKERDISABLED = 'ns.notifications.workers-disabled';

    const NSCHEDULEDTRANSACTIONS = 'ns.notifications.scheduled-transactions';

    const NSSYMBOLICLINKSMISSING = 'ns.notifications.symbolic-links-missing';
}
