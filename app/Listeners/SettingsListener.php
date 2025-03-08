<?php

namespace App\Listeners;

use App\Enums\NotificationsEnum;
use App\Events\SettingsSavedEvent;
use App\Jobs\TestWorkerJob;
use App\Models\Role;
use App\Services\NotificationService;
use App\Services\Options;

class SettingsListener
{
    protected $notificationService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        NotificationService $notificationService
    ) {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     *
     * @param  object $event
     * @return void
     */
    public function handle( SettingsSavedEvent $event )
    {
        $options = app()->make( Options::class );

        if ( $options->get( 'ns_workers_enabled' ) === 'await_confirm' ) {
            $notification_id = NotificationsEnum::NSWORKERDISABLED;

            TestWorkerJob::dispatch( $notification_id )
                ->delay( now() );

            $this->notificationService->create(
                title: __( 'Workers Disabled' ),
                description: __( 'The workers has been disabled. You can enable them by going to the settings.' ),
                identifier: $notification_id,
                url: route( 'ns.settings.workers' ),
            )->dispatchForGroup(
                [ Role::namespace( 'admin' ) ]
            );
        }
    }
}
