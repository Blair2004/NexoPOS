<?php

namespace App\Jobs;

use App\Models\Expense;
use App\Models\Role;
use App\Services\DateService;
use App\Services\ExpenseService;
use App\Services\NotificationService;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteDelayedExpenseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, NsSerialize;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public Expense $expense )
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
         * @var ExpenseService $expenseService
         */
        $expenseService = app()->make( ExpenseService::class );

        /**
         * @var NotificationService $notificationService
         */
        $notificationService = app()->make( NotificationService::class );

        $expenseService->triggerExpense( $this->expense );

        $notificationService->create([
            'title' => __( 'Scheduled Expenses' ),
            'description' => sprintf( __( 'the expense "%s" was executed as scheduled on %s.' ), $this->expense->name, $dateService->getNowFormatted() ),
            'url' => ns()->route( 'ns.dashboard.expenses.edit', [ 'expense' => $this->expense->id ]),
        ])->dispatchForGroup([ Role::namespace( Role::ADMIN ), Role::namespace( Role::STOREADMIN ) ]);
    }
}
