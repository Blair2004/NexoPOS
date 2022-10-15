<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshExpenseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $range_starts;

    public $range_ends;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( $range_starts, $range_ends )
    {
        $this->range_ends = $range_ends;
        $this->range_starts = $range_starts;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // ...
    }
}
