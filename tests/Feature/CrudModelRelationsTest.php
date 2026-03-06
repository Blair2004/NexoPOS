<?php

namespace Tests\Feature;

use App\Crud\ProviderCrud;
use App\Models\Provider;
use App\Services\CrudService;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

/**
 * Covers the model-based relation syntax introduced in CrudService.
 *
 * The new grouped syntax lets CRUD classes declare SQL joins by pointing at
 * an Eloquent relationship method instead of spelling out the raw table/column
 * names:
 *
 *   public $relations = [
 *       'leftJoin' => [
 *           [ User::class, 'user' ],   // calls Provider::user() => BelongsTo
 *       ],
 *   ];
 *
 * This automatically:
 *  • derives the join clause (table/FK/ownerKey) from the Eloquent relation.
 *  • uses the method name ("user") as the SQL alias so columns are prefixed
 *    with "user_" instead of the raw table name.
 *  • excludes columns declared in the related model's $hidden array from
 *    the SELECT, preventing fields like password / remember_token from being
 *    returned to the client.
 *
 * Old raw-array syntax (['nexopos_users as author', 'fk', '=', 'pk']) is
 * preserved for backward compatibility.
 */
class CrudModelRelationsTest extends TestCase
{
    use WithAuthentication;

    // -----------------------------------------------------------------------
    // New syntax tests
    // -----------------------------------------------------------------------

    /**
     * ProviderCrud now uses the grouped model-based syntax.
     *
     * User::$hidden = ['password', 'remember_token']
     *
     * These must NOT appear as "user_password" / "user_remember_token"
     * in any CRUD entry.  The "user_username" column must still be present
     * (proves the join itself works).
     */
    public function test_model_based_relation_excludes_hidden_fields(): void
    {
        $this->attemptAuthenticate();

        $crud    = new ProviderCrud;
        $entries = $crud->getEntries();

        $this->assertArrayHasKey( 'data', $entries );

        foreach ( $entries['data'] as $entry ) {
            $values = $entry->jsonSerialize();

            $this->assertArrayNotHasKey(
                'user_password',
                $values,
                'user_password must not be exposed by CRUD entries when using model-based relations.'
            );

            $this->assertArrayNotHasKey(
                'user_remember_token',
                $values,
                'user_remember_token must not be exposed by CRUD entries when using model-based relations.'
            );

            // Confirm the join itself resolves correctly.
            $this->assertArrayHasKey(
                'user_username',
                $values,
                'user_username (from the joined User model) must be present.'
            );
        }
    }

    /**
     * Verify the same protection applies via an API request so that the
     * HTTP layer does not accidentally re-expose hidden fields.
     */
    public function test_api_entries_do_not_expose_hidden_relation_fields(): void
    {
        $this->attemptAuthenticate();

        $response = $this->withSession( $this->app['session']->all() )
            ->json( 'GET', 'api/crud/ns.providers' );

        $response->assertJsonMissing( [ 'user_password' ] );
        $response->assertJsonMissing( [ 'user_remember_token' ] );
    }

    // -----------------------------------------------------------------------
    // Backward-compatibility tests
    // -----------------------------------------------------------------------

    /**
     * Old raw-array relations (numeric key, 4-element array) must still
     * produce correct results after the resolveRelations() normalisation step.
     */
    public function test_legacy_numeric_key_relation_still_works(): void
    {
        $this->attemptAuthenticate();

        $crud = new class extends CrudService {
            const IDENTIFIER = 'test.legacy-numeric';

            protected $table = 'nexopos_providers';

            protected $model = Provider::class;

            /**
             * Old style: numeric key, raw 4-element array with explicit alias.
             */
            public $relations = [
                [ 'nexopos_users as author', 'nexopos_providers.author_id', '=', 'author.id' ],
            ];

            public $pick = [
                'author' => [ 'username', 'email' ],
            ];
        };

        $entries = $crud->getEntries();

        $this->assertArrayHasKey( 'data', $entries, 'getEntries() must return a data key.' );

        foreach ( $entries['data'] as $entry ) {
            $values = $entry->jsonSerialize();

            // $pick restricts to username+email, so password is absent anyway.
            $this->assertArrayNotHasKey( 'author_password', $values );
            $this->assertArrayHasKey( 'author_username', $values );
        }
    }

    // -----------------------------------------------------------------------
    // Explicit alias-key tests
    // -----------------------------------------------------------------------

    /**
     * When the inner array uses a string key as an explicit alias
     *   'author' => [User::class, 'user']
     * the SQL alias becomes "author" (not "user"), so columns are prefixed
     * with "author_" and hidden fields are excluded as "author_password" etc.
     *
     * This lets a CRUD join the same model twice under different aliases, or
     * simply give a column prefix that differs from the Eloquent method name.
     */
    public function test_explicit_alias_key_overrides_method_name_as_prefix(): void
    {
        $this->attemptAuthenticate();

        $crud = new class extends CrudService {
            const IDENTIFIER = 'test.explicit-alias';

            protected $table = 'nexopos_providers';

            protected $model = Provider::class;

            /**
             * 'author' key → SQL alias = "author", method called = Provider::user().
             * Column prefix must be "author_", NOT "user_".
             */
            public $relations = [
                'leftJoin' => [
                    'author' => [ \App\Models\User::class, 'user' ],
                ],
            ];

            public $pick = [
                'author' => [ 'username' ],
            ];
        };

        $entries = $crud->getEntries();

        $this->assertArrayHasKey( 'data', $entries );

        foreach ( $entries['data'] as $entry ) {
            $values = $entry->jsonSerialize();

            // Confirm alias-based prefix is used.
            $this->assertArrayHasKey(
                'author_username',
                $values,
                '"author_username" must exist when alias key is "author".'
            );

            // The old prefix must NOT appear (method name was "user").
            $this->assertArrayNotHasKey(
                'user_username',
                $values,
                '"user_username" must NOT appear; the alias overrides the method name.'
            );

            // Hidden fields are excluded under the alias-derived name.
            $this->assertArrayNotHasKey(
                'author_password',
                $values,
                '"author_password" must be excluded (from User::$hidden).'
            );

            $this->assertArrayNotHasKey(
                'author_remember_token',
                $values,
                '"author_remember_token" must be excluded (from User::$hidden).'
            );
        }
    }

    /**
     * A leading '@' is accepted as syntactic sugar (it is stripped automatically)
     * and behaves identically to the plain alias.
     */
    public function test_at_prefixed_alias_key_is_tolerated(): void
    {
        $this->attemptAuthenticate();

        $crud = new class extends CrudService {
            const IDENTIFIER = 'test.at-alias';

            protected $table = 'nexopos_providers';

            protected $model = Provider::class;

            /**
             * '@author' should behave identically to 'author'.
             */
            public $relations = [
                'leftJoin' => [
                    '@author' => [ \App\Models\User::class, 'user' ],
                ],
            ];

            public $pick = [
                'author' => [ 'username' ],
            ];
        };

        $entries = $crud->getEntries();

        $this->assertArrayHasKey( 'data', $entries );

        foreach ( $entries['data'] as $entry ) {
            $values = $entry->jsonSerialize();

            $this->assertArrayHasKey( 'author_username', $values,
                '"author_username" must be present when "@author" alias key is used.' );

            $this->assertArrayNotHasKey( 'user_username', $values,
                '"user_username" must NOT appear when "@author" alias overrides it.' );

            $this->assertArrayNotHasKey( 'author_password', $values );
        }
    }

    /**
     * Old-style grouped junction syntax ('leftJoin' => [raw-array, ...])
     * must pass through resolveRelations() unmodified.
     */
    public function test_legacy_grouped_junction_relation_still_works(): void
    {
        $this->attemptAuthenticate();

        $crud = new class extends CrudService {
            const IDENTIFIER = 'test.legacy-grouped';

            protected $table = 'nexopos_providers';

            protected $model = Provider::class;

            /**
             * Old grouped style: string junction key, raw 4-element sub-arrays.
             */
            public $relations = [
                'leftJoin' => [
                    [ 'nexopos_users as author', 'nexopos_providers.author_id', '=', 'author.id' ],
                ],
            ];

            public $pick = [
                'author' => [ 'username' ],
            ];
        };

        $entries = $crud->getEntries();

        $this->assertArrayHasKey( 'data', $entries );

        foreach ( $entries['data'] as $entry ) {
            $values = $entry->jsonSerialize();

            $this->assertArrayHasKey( 'author_username', $values );
            $this->assertArrayNotHasKey( 'author_password', $values );
        }
    }
}
