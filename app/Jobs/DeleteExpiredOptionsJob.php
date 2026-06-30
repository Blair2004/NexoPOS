<?php

namespace App\Jobs;

use App\Services\Options;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteExpiredOptionsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle( Options $options ): void
    {
        $options->deleteExpired();
    }
}
