<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testPostingOrder()
    {

        $response = $this->withCookies([
            'XSRF-TOKEN'        =>  'eyJpdiI6IkMxQmd4YjZoZDhURG1mN05RbFlROGc9PSIsInZhbHVlIjoidTdyWnQwQW5JZ2lDOXE1UWc2b3pIRHErRm52ek5WY2pMNkx6eTl6ZlczYlZsdHJtMFZWTzg3QnRIVFdPWU9Pc1V6Z00rTnRBVWpxaUlQU1FLRW41cXNMTm5uT0hoN1RrZVNlT1IwRHRqeGZFcjBXSWtKMnJNdXFuRnhkOHpYMVMiLCJtYWMiOiI0NjU4ZmY2NjBkYWExZjI5NzY0MTM3YTk4YmFlZWZlMjc0M2NiOGNhMTMzZGQ4MTlkMmI0NWVkNzAxZjczOWNmIn0%3D',
            'laravel_session'   =>  'eyJpdiI6Ik94dE1Ycmx3WkdDR21JWmRuREhvalE9PSIsInZhbHVlIjoiSEpSdVpqeU80c2ZHT2hLWldTRXRReGNWUmZhOElsUXdnVThYWVFlN3BhVklTK01jYTVHdU9zNDV1ekhaSHR2akxBMFN2NDhnODFZdzRSWmtROUFtbWN2NzZzVGxDL2UxRlRaYmJvSlorVzBMcm4wVHZLaitHSXRCUzN1TURSN2IiLCJtYWMiOiJiN2NhMWNlYzRhYTY0MWMyY2U3OWNlMDcwOWEyNmZhM2RjMDI3NmM4YTc5ZGJhMGE5NGYxMmIxMWViYmQ3YjU0In0%3D',
        ])->json( 'GET', 'api/nexopos/v4/medias', [

        ]);

        $response->dump();
        
        // $response->assertRedirect( '/sign-in' );

        $response->assertStatus(200);
    }
}
