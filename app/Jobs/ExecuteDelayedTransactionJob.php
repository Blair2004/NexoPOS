<?php

namespace App\Jobs;

use App\Models\Role;
use App\Models\TransactionHistory;
use App\Services\DateService;
use App\Services\NotificationService;
use App\Services\TransactionService;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteDelayedTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NsSerialize, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public TransactionHistory $transactionHistory )
    {
        $this->prepareSerialization();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle( TransactionService $transactionService, NotificationService $notificationService, DateService $dateService )
    {
        $transactionService->triggerTransactionHistory( $this->transactionHistory );
        $transaction = $this->transactionHistory->transaction;

        $notificationService->create( [
            'title' => __( 'Scheduled Transactions' ),
            'description' => sprintf( __( 'the transaction "%s" was executed as scheduled on %s.' ), $transaction->name, $dateService->getNowFormatted() ),
            'url' => ns()->route( 'ns.dashboard.transactions.history', [ 'transaction' => $transaction->id ] ),
        ] )->dispatchForGroup( [ Role::namespace( Role::ADMIN ), Role::namespace( Role::STOREADMIN ) ] );
    }
}
