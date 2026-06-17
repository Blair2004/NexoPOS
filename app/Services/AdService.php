<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AdService
{
    /**
     * Fetch ads from the NexoPlatform API.
     * Caches the result for 24 hours.
     *
     * @return array
     */
    public function getAds(): array
    {
        return Cache::remember('nexoplatform_ads', 60 * 60 * 24, function () {
            try {
                $response = Http::timeout(5)->get('https://my.nexopos.com/api/nexoplatform/ads');

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                // Ignore and fall back to default ads
            }

            return $this->getDefaultAds();
        });
    }

    /**
     * Determine which ad to show for the given user on the current route.
     *
     * @param \App\Models\User $user
     * @param string $routeName
     * @return array|null
     */
    public function getAdToDisplay($user, string | null $routeName): ?array
    {
        // Check if the user has snoozed ads
        $userOptions = new UserOptions($user->id);
        if ($userOptions->get('snooze_ads_24h') === 'yes') {
            return null;
        }

        $allAds = $this->getAds();
        $validAds = [];

        foreach ($allAds as $ad) {
            $adRoutes = $ad['routes'] ?? [];
            $strict = $ad['strict'] ?? false;

            if ($strict) {
                if (in_array($routeName, $adRoutes)) {
                    $validAds[] = $ad;
                }
            } else {
                $validAds[] = $ad;
            }
        }

        if (empty($validAds)) {
            return null;
        }

        // Randomly pop one
        return $validAds[array_rand($validAds)];
    }

    /**
     * Fallback preconfigured ads
     *
     * @return array
     */
    private function getDefaultAds(): array
    {
        $ads    =   [
            [
                'title' => __( 'Need Something Specific?' ),
                'message' => __( 'Check out our store for exclusive NexoPOS modules.' ),
                'icon' => 'la-store',
                'url' => ns()->route( 'ns.dashboard.modules-list' ),
                'routes' => [],
                'strict' => false,
            ],
        ];

        $marketplaceService     =   app()->make( MarketplaceService::class );

        if ( $marketplaceService->testConnection() ) {
            $ads[]  =   [
                'title' => __( 'New to NexoPOS?' ),
                'message' => __( 'Connect your store to our marketplace and discover new modules.' ),
                'icon' => 'la-plug',
                'url' => ns()->route( 'ns.dashboard.modules-list' ),
                'routes' => [],
                'strict' => true,
            ];
        }

        return $ads;
    }
}
