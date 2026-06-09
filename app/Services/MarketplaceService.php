<?php
namespace App\Services;

use App\Exceptions\CoreException;
use App\Exceptions\NotAllowedException;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class MarketplaceService
{
    /**
     * This method will return the list of modules available in the marketplace, it will also handle the authentication 
     * if we have a valid access token and refresh it if it's expired
     */
    public function getModules( array $data = [] ): Response
    {
        $queryParams =  [
            'per_page' => $data[ 'per_page' ] ?? 12,
            'page' => $data[ 'page' ] ?? 1,
            'type' => 'zip'
        ];

        $request = Http::withHeader( 'X-NEXOPOS-DOMAIN', $data[ 'host' ] ?? request()->getHost() );

        if ( env( 'APP_ENV' ) === 'local' ) {
            $request->withoutVerifying();
        }

        /**
         * if we have access and refresh token, we'll test the token if that works if it's not the case we'll try to refresh it if the refresh fails
         * we'll just return the public modules without the ones that are only available for authenticated users
         */
        $this->authenticateRequest( $request );

        return $request->accept( 'application/json' )
            ->withoutVerifying()
            ->get( env( 'MARKETPLACE_DOMAIN', 'https://my.nexopos.com' ) . '/api/nexoplatform/marketplace/items?' . http_build_query( $queryParams ) );
    }

    /**
     * This method will check if we have a valid access token and if it's not expired, 
     * if it's expired it will try to refresh it using the refresh token, 
     * if the refresh token is also expired or invalid it will just return without authenticating the request
     */
    public function authenticateRequest( PendingRequest $request ): void
    {
        $accessToken = ns()->option->get( 'mynexopos_access_token' );
        $refreshToken = ns()->option->get( 'mynexopos_refresh_token' );
        $tokenExpiresAt = ns()->option->get( 'mynexopos_token_expires_in' );

        if ( $accessToken && $refreshToken && Carbon::now()->lessThan( Carbon::parse( $tokenExpiresAt ) ) ) {
            $request->withToken( $accessToken );
        } elseif ( $refreshToken ) {
            try {
                $this->refreshAccessToken( $request, $refreshToken );
            } catch ( CoreException $e ) {
                // If refreshing the token fails, we can choose to log the error or ignore it and proceed without authentication
                // For this example, we'll just ignore it and proceed without authentication
            }
        }
    }

    /**
     * This method will try to refresh the access token using the refresh token, if it fails it will throw an exception.
     */
    public function refreshAccessToken( PendingRequest $request, string $refreshToken ): void
    {
        $response = $request->accept( 'application/json' )
            ->withoutVerifying()
            ->post( env( 'MARKETPLACE_DOMAIN', 'https://my.nexopos.com' ) . '/oauth/token', [
                'grant_type' => 'refresh_token',
                'client_id' => env( 'MARKETPLACE_CLIENT_ID', '019eac04-114e-711d-8433-2cde59066bad' ),
                'refresh_token' => $refreshToken,
            ] );

        if ( $response->failed() ) {
            throw new CoreException( __( 'Failed to refresh access token.' ) );
        }

        ns()->option->set( 'mynexopos_access_token', $response->json( 'access_token' ) );
        ns()->option->set( 'mynexopos_refresh_token', $response->json( 'refresh_token' ) );
        ns()->option->set( 'mynexopos_token_type', $response->json( 'token_type' ) );
        ns()->option->set( 'mynexopos_token_expires_in', Carbon::now()->addSeconds( $response->json( 'expires_in' ) ) );

        $request->withToken( $response->json( 'access_token' ) );
    }

    /**
     * This method will redirect the user to the marketplace authorization page, 
     * it will generate a code verifier and code challenge for PKCE authentication and store the code verifier 
     * and state in the session for later use in the callback.
     */
    public function authorize(): Redirector | RedirectResponse
    {
        $request = Http::withHeader( 'X-NEXOPOS-DOMAIN', request()->getHost() );

        if ( env( 'APP_ENV' ) === 'local' ) {
            $request->withoutVerifying();
        }

        $codeVerifier = Str::random(96);

        $codeChallenge = rtrim(strtr(
            base64_encode(hash('sha256', $codeVerifier, true)),
            '+/',
            '-_'
        ), '=');

        $state = Str::random(64);

        Session::put('mynexopos_oauth_state', $state);
        Session::put('mynexopos_code_verifier', $codeVerifier);

        return redirect()->away( env( 'MARKETPLACE_DOMAIN', 'https://my.nexopos.com' ) . '/oauth/nexopos/enroll?' . http_build_query( [
            'domain' => request()->getHost(),
            'client_id' => env( 'MARKETPLACE_CLIENT_ID', '019eac04-114e-711d-8433-2cde59066bad' ),
            'scope' => 'read-licenses update-licenses manage-cart download-products',
            'code_verifier' => $codeVerifier,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'state' => $state,
            'callback_url' => route( ns()->routeName( 'ns.oauth.mynexopos.callback' ) ),
        ] ) );
    }
    
    /**
     * This method will handle the callback from the marketplace after the user has authorized the application,
     * it will validate the provided code and state, then it will request the access token using
     * the provided code and code_verification.
     */
    public function handleCallback( array $data = [] ): Redirector | RedirectResponse
    {
        /**
         * we now need to request the access token using the provided code and code_verification
         */
        if ( $data[ 'state' ] !== Session::get( 'mynexopos_oauth_state' ) ) {
            throw new NotAllowedException( __( 'Invalid state parameter.' ) );
        }

        $request = Http::withHeader( 'X-NEXOPOS-DOMAIN', request()->getHost() );

        if ( env( 'APP_ENV' ) === 'local' ) {
            $request->withoutVerifying();
        }

        $response = $request->accept( 'application/json' )
            ->withoutVerifying()
            ->post( env( 'MARKETPLACE_DOMAIN', 'https://my.nexopos.com' ) . '/oauth/token', [
                'grant_type' => 'authorization_code',
                'client_id' => env( 'MARKETPLACE_CLIENT_ID', '019eac04-114e-711d-8433-2cde59066bad' ),
                'code' => $data[ 'code' ],
                'code_verifier' => Session::get( 'mynexopos_code_verifier' ),
                'redirect_uri' => Str::chopEnd( env( 'MARKETPLACE_DOMAIN' ), '/' ) . '/oauth/nexopos/callback',
            ] );

        if ( $response->failed() ) {
            throw new CoreException( __( 'Failed to retrieve access token.' ) );
        }

        /**
         * We now need to store the access token and refresh token in the options table
         */
        ns()->option->set( 'mynexopos_access_token', $response->json( 'access_token' ) );
        ns()->option->set( 'mynexopos_refresh_token', $response->json( 'refresh_token' ) );
        ns()->option->set( 'mynexopos_token_type', $response->json( 'token_type' ) );
        ns()->option->set( 'mynexopos_token_expires_in', Carbon::now()->addSeconds( $response->json( 'expires_in' ) ) );

        return redirect( route( ns()->routeName( 'ns.dashboard.modules-marketplace' ) ) )->with( 'success', __( 'Successfully connected to My NexoPOS.' ) );
    }

    public function addToCart( int | string $productId )
    {
        $request = Http::withHeader( 'X-NEXOPOS-DOMAIN', request()->getHost() );

        if ( env( 'APP_ENV' ) === 'local' ) {
            $request->withoutVerifying();
        }

        $this->authenticateRequest( $request );

        /**
         * We might need to check if the item is already added to the cart. If it's the case
         * we'll just foward the user to the card for the payment.
         */
        $cartCheck = $this->hasItemOnCart( $request, $productId );

        if ( isset( $cartCheck[ 'data' ][ 'hasItem' ] ) && $cartCheck[ 'data' ][ 'hasItem' ] ) {
            return [
                'status' => 'success',
                'message' => __( 'Item is already on the cart.' ),
                'data' => [
                    'action' => 'already-on-cart',
                    'redirect' => $cartCheck[ 'data' ][ 'cartUrl' ]
                ]
            ];
        }

        $response = $request->accept( 'application/json' )
            ->withoutVerifying()
            ->post( env( 'MARKETPLACE_DOMAIN', 'https://my.nexopos.com' ) . '/api/nexoplatform/oauth/user/cart/items', [
                'product_id' => $productId,
                'quantity' => 1,
                'meta' => []
            ] );

        if ( $response->failed() ) {
            throw new CoreException( $response->json( 'message' ) ?: __( 'Failed to add item to cart.' ) );
        }

        return [
            'status' => 'success',
            'message' => __( 'Item added to cart successfully.' ),
            'data' => [
                'action' => 'added-to-cart',
                'redirect' => $response->json( 'data.cartUrl' )
            ]
        ];
    }

    /**
     * Will check if the item is already
     * on the user cart to prevent multiple addition.
     */
    public function hasItemOnCart( PendingRequest $request, $productId ): array
    {
        $query = Http::withHeader( 'X-NEXOPOS-DOMAIN', request()->getHost() )
            ->withToken( ns()->option->get( 'mynexopos_access_token' ) )
            ->accept( 'application/json' );

        if ( env( 'APP_ENV' ) === 'local' ) {
            $query->withoutVerifying();
        }
            
        $response = $query->post( env( 'MARKETPLACE_DOMAIN', 'https://my.nexopos.com' ) . '/api/nexoplatform/oauth/user/cart/has-item', [
            'product_id' => $productId,
        ]);

        if ( $response->failed() ) {
            throw new CoreException( $response->json( 'message' ) ?: __( 'Failed to retrieve cart information.' ) );
        }

        return $response->json();
    }

    public function getLicenses( int | string $itemId ): Response
    {
        $request = Http::withHeader( 'X-NEXOPOS-DOMAIN', request()->getHost() );

        if ( env( 'APP_ENV' ) === 'local' ) {
            $request->withoutVerifying();
        }

        $this->authenticateRequest( $request );

        return $request->accept( 'application/json' )
            ->withoutVerifying()
            ->get( env( 'MARKETPLACE_DOMAIN', 'https://my.nexopos.com' ) . '/api/nexoplatform/oauth/user/licenses/for-product/' . $itemId );
    }

    public function downloadModule( int | string $itemId, string $licenseId ): Response
    {
        $request = Http::withHeader( 'X-NEXOPOS-DOMAIN', request()->getHost() );

        if ( env( 'APP_ENV' ) === 'local' ) {
            $request->withoutVerifying();
        }

        $this->authenticateRequest( $request );

        return $request->accept( 'application/json' )
            ->withoutVerifying()
            ->post( env( 'MARKETPLACE_DOMAIN', 'https://my.nexopos.com' ) . '/api/nexoplatform/oauth/user/download', [
                'product_id' => $itemId,
                'license_id' => $licenseId,
            ] );
    }
}