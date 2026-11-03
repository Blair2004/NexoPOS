<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRoleRelation;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class ResponseCacheSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware( [ VerifyCsrfToken::class, ValidateCsrfToken::class ] );
    }

    public function test_authenticated_dashboard_response_is_not_stored_by_browsers(): void
    {
        $admin = User::where( 'username', 'admin' )->firstOrFail();

        $response = $this->actingAs( $admin )->get( '/dashboard/users/profile' );

        $response->assertOk();
        $this->assertStringContainsString( 'no-store', (string) $response->headers->get( 'Cache-Control' ) );
        $this->assertStringContainsString( 'private', (string) $response->headers->get( 'Cache-Control' ) );
    }

    public function test_authenticated_api_response_is_not_stored_by_browsers(): void
    {
        $admin = User::where( 'username', 'admin' )->firstOrFail();
        Sanctum::actingAs( $admin, [ '*' ] );

        $response = $this->getJson( '/api/user' );

        $response->assertOk();
        $this->assertStringContainsString( 'no-store', (string) $response->headers->get( 'Cache-Control' ) );
        $this->assertStringContainsString( 'private', (string) $response->headers->get( 'Cache-Control' ) );
    }

    public function test_logout_invalidates_and_rotates_the_session(): void
    {
        $suffix = Str::random( 8 );
        $user = User::create( [
            'username' => 'logout-test-' . $suffix,
            'email' => 'logout-test-' . $suffix . '@example.com',
            'password' => Hash::make( 'password' ),
            'active' => true,
        ] );
        $user->assignRole( Role::ADMIN );
        $user->attribute()->create( [ 'language' => 'en' ] );

        try {
            $loginResponse = $this->post( '/auth/sign-in', [
                'username' => $user->username,
                'password' => 'password',
            ] );
            $loginResponse->assertRedirect();

            $loginCookie = $this->sessionCookieFrom( $loginResponse );
            $previousSessionId = $this->sessionIdFrom( $loginCookie );

            $dashboardResponse = $this->withCookie(
                $loginCookie->getName(),
                $previousSessionId
            )->get( '/dashboard/users/profile' );
            $dashboardResponse->assertOk();

            $dashboardCookie = $this->sessionCookieFrom( $dashboardResponse );
            $this->assertSame( $previousSessionId, $this->sessionIdFrom( $dashboardCookie ) );

            $signOutResponse = $this->withCookie(
                $dashboardCookie->getName(),
                $this->sessionIdFrom( $dashboardCookie )
            )->get( '/sign-out' );
            $signOutResponse->assertRedirect();

            $signOutCookie = $this->sessionCookieFrom( $signOutResponse );

            $this->assertNotSame( $previousSessionId, $this->sessionIdFrom( $signOutCookie ) );
            $this->assertNotEmpty( session()->token() );
            $this->assertGuest();
        } finally {
            UserRoleRelation::where( 'user_id', $user->id )->delete();
            $user->attribute()->delete();
            $user->delete();
        }
    }

    private function sessionCookieFrom( $response ): Cookie
    {
        $cookie = collect( $response->headers->getCookies() )->first(
            fn ( Cookie $cookie ) => $cookie->getName() === config( 'session.cookie' )
        );

        $this->assertInstanceOf( Cookie::class, $cookie, 'The response did not set a session cookie.' );

        return $cookie;
    }

    private function sessionIdFrom( Cookie $cookie ): string
    {
        return CookieValuePrefix::remove( Crypt::decryptString( $cookie->getValue() ) );
    }
}
