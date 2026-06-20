<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use App\Models\Role;
use App\Services\UsersService;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Verifies that the permissions declared on CrudService and CrudController
 * are actually enforced for the ProductCategoryCrud resource.
 *
 * A freshly created "user" role account (no admin privileges) should receive
 * HTTP 403 on every CRUD operation: create, read, update, and delete.
 */
class CrudPermissionsTest extends TestCase
{
    /**
     * Asserts that a "user" role account cannot create a product category.
     */
    public function test_user_cannot_create_product_category(): void
    {
        $user = $this->createUserRoleUser();

        Sanctum::actingAs( $user, [ '*' ] );

        $response = $this->json( 'POST', 'api/crud/ns.products-categories', [
            'name' => 'Unauthorized Category ' . Str::random( 5 ),
            'general' => [ 'displays_on_pos' => true ],
        ] );

        $response->assertStatus( 403 );
        $response->assertJson( [ 'status' => 'error' ] );

        $user->delete();
    }

    /**
     * Asserts that a "user" role account cannot list product categories.
     */
    public function test_user_cannot_read_product_categories(): void
    {
        $user = $this->createUserRoleUser();

        Sanctum::actingAs( $user, [ '*' ] );

        $response = $this->json( 'GET', 'api/crud/ns.products-categories' );

        $response->assertStatus( 403 );
        $response->assertJson( [ 'status' => 'error' ] );

        $user->delete();
    }

    /**
     * Asserts that a "user" role account cannot update a product category.
     */
    public function test_user_cannot_update_product_category(): void
    {
        $category = ProductCategory::first();

        $this->assertNotNull(
            $category,
            'At least one product category must exist in the database to run this test.'
        );

        $user = $this->createUserRoleUser();

        Sanctum::actingAs( $user, [ '*' ] );

        $response = $this->json( 'PUT', 'api/crud/ns.products-categories/' . $category->id, [
            'name' => 'Unauthorized Update ' . Str::random( 5 ),
            'general' => [ 'displays_on_pos' => true ],
        ] );

        $response->assertStatus( 403 );
        $response->assertJson( [ 'status' => 'error' ] );

        $user->delete();
    }

    /**
     * Asserts that a "user" role account cannot delete a product category.
     */
    public function test_user_cannot_delete_product_category(): void
    {
        $category = ProductCategory::first();

        $this->assertNotNull(
            $category,
            'At least one product category must exist in the database to run this test.'
        );

        $user = $this->createUserRoleUser();

        Sanctum::actingAs( $user, [ '*' ] );

        $response = $this->json( 'DELETE', 'api/crud/ns.products-categories/' . $category->id );

        $response->assertStatus( 403 );
        $response->assertJson( [ 'status' => 'error' ] );

        // The category must still exist: the deletion must have been blocked.
        $this->assertDatabaseHas( 'nexopos_products_categories', [ 'id' => $category->id ] );

        $user->delete();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Creates a transient user assigned to the "user" role by going through the
     * same UsersService::setUser() path that the application itself uses.
     *
     * The caller is responsible for deleting the returned User after the test.
     */
    private function createUserRoleUser(): \App\Models\User
    {
        $userRole = Role::namespace( Role::USER );

        $this->assertNotNull(
            $userRole,
            'The "user" role must exist in the database to run this test.'
        );

        // UsersService::setUser() validates that ns_registration_role points to a
        // real role.  Save the current value so we can restore it after the call.
        $previousRegistrationRole = ns()->option->get( 'ns_registration_role' );

        ns()->option->set( 'ns_registration_role', $userRole->id );
        ns()->option->set( 'ns_registration_validated', 'no' ); // activate immediately, no e-mail token

        $service = new UsersService;

        $result = $service->setUser( [
            'username' => 'crud_perm_user_' . Str::random( 8 ),
            'email' => 'crud_perm_' . Str::random( 8 ) . '@nexopos-test.invalid',
            'password' => Str::random( 16 ),
            'active' => true,
        ] );

        // Restore the original option so this test does not pollute others.
        ns()->option->set( 'ns_registration_role', $previousRegistrationRole );

        $this->assertEquals(
            'success',
            $result['status'],
            'UsersService::setUser() failed: ' . ( $result['message'] ?? 'unknown error' )
        );

        return $result['data']['user'];
    }
}
