<?php

namespace App\Jobs;

use App\Enums\NotificationsEnum;
use App\Models\Expense;
use App\Models\Notification;
use App\Models\Role;
use App\Services\DateService;
use App\Services\ExpenseService;
use App\Services\NotificationService;
use App\Traits\NsSerialize;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        $dateService     =   app()->make( DateService::class );

        /**
         * @var ExpenseService $expenseService
         */
        $expenseService =   app()->make( ExpenseService::class );

        /**
         * @var NotificationService $notificationService
         */
        $notificationService =   app()->make( NotificationService::class );

        $expenseDate    =   Carbon::parse( $this->expense->scheduled_date );
        $format         =   'Y-m-d h:m';

        /**
         * We need to check if the scheduled expense
         * should trigger. The condition is that the scheduled date matches 
         * the actual server date.
        **/
        if ( $dateService->format( $format ) === $expenseDate->format( $format ) ) {
            $expenseService->triggerExpense( $this->expense );

            $notificationService->create([
                'title' =>  __( 'Scheduled Expenses' ),
                'identifier'    =>  NotificationsEnum::NSSCHEDULEDEXPENSES,
                'description'   =>  sprintf( __( 'the expense %s was executed as scheduled on %s.' ), $this->expense->name, $dateService->getNowFormatted() ),
                'url'   =>  ns()->route( 'ns.dashboard.expenses.edit', [ 'expense' => $this->expense->id ])
            ])->dispatchForGroup([ Role::namespace( Role::ADMIN ), Role::namespace( Role::STOREADMIN ) ]);
        } else {
            Log::alert( 
                sprintf( 
                    __( 'The expense %s which was scheduled on %s didn\'t executed, as the server date and the scheduled date doesn\'t match. The expense scheduled date might have been updated.' ), 
                    $this->expense->name,
                    $dateService->getNowFormatted()
                ) 
            );
        }
    }
}
