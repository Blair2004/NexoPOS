<?php

namespace App\Jobs;

use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckForUpdatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NsSerialize, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->prepareSerialization();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $response = Http::withHeaders( [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'NexoPOS',
            ] )->get( 'https://api.github.com/repos/blair2004/NexoPOS/releases/latest' );

            if ( $response->successful() ) {
                $latestVersion = ltrim( $response->json( 'tag_name' ), 'vV' );
                $currentVersion = config( 'nexopos.version' );

                if ( version_compare( $latestVersion, $currentVersion, '>' ) ) {
                    ns()->option->set( 'ns_latest_version', $latestVersion );
                } else {
                    ns()->option->delete( 'ns_latest_version' );
                }
            } else {
                Log::warning( 'CheckForUpdatesJob: GitHub API returned status ' . $response->status() );
            }
        } catch ( \Exception $e ) {
            Log::warning( 'CheckForUpdatesJob: Failed to check for updates - ' . $e->getMessage() );
        }
    }
}
