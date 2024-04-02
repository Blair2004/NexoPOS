<?php

namespace App\Jobs;

use App\Models\Procurement;
use App\Models\Role;
use App\Services\NotificationService;
use App\Services\ProcurementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcurementRefreshJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param Procurement
     */
    public $procurement;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( Procurement $procurement )
    {
        $this->procurement = $procurement;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle( ProcurementService $procurementService, NotificationService $notificationService )
    {
        $procurementService->refresh( $this->procurement );

        $notificationService->create( [
            'title' => __( 'Procurement Refreshed' ),
            'description' => sprintf(
                __( 'The procurement "%s" has been successfully refreshed.' ),
                $this->procurement->name
            ),
            'identifier' => 'ns.procurement-refresh' . $this->procurement->id,
            'url' => ns()->route( 'ns.procurement-invoice', [ 'procurement' => $this->procurement->id ] ),
        ] )->dispatchForGroup( [
            Role::ADMIN,
            Role::STOREADMIN,
        ] );
    }
}
