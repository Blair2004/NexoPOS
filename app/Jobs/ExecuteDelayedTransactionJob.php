<?php

namespace App\Jobs;

use App\Models\Role;
use App\Models\Transaction;
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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, NsSerialize;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public Transaction $transaction )
    {
        $this->prepareSerialization();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /**
         * @var DateService $dateService
         */
        $dateService = app()->make( DateService::class );

        /**
         * @var TransactionService $transactionService
         */
        $transactionService = app()->make( TransactionService::class );

        /**
         * @var NotificationService $notificationService
         */
        $notificationService = app()->make( NotificationService::class );

        $transactionService->triggerTransaction( $this->transaction );

        $notificationService->create([
            'title' => __( 'Scheduled Transactions' ),
            'description' => sprintf( __( 'the transaction "%s" was executed as scheduled on %s.' ), $this->transaction->name, $dateService->getNowFormatted() ),
            'url' => ns()->route( 'ns.dashboard.transactions.history', [ 'transaction' => $this->transaction->id ]),
        ])->dispatchForGroup([ Role::namespace( Role::ADMIN ), Role::namespace( Role::STOREADMIN ) ]);
    }
}
