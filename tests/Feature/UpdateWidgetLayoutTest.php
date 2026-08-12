<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserWidget;
use App\Services\WidgetService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateWidgetLayoutTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authenticated_user_can_replace_the_complete_widget_layout(): void
    {
        [ $user, $widgetService ] = $this->authenticateWithTestWidgets();

        $widgets = $widgetService->getWidgets()->values();
        $identifiers = $widgets->pluck( 'component-name' )->all();

        $requestedWidgets = collect( array_reverse( $identifiers ) )->map( fn( string $identifier ): array => [
            'identifier' => $identifier,
            'layout' => match ( $identifier ) {
                'testDashboardWidget' => '3x5',
                'testWideDashboardWidget' => '2x5',
                default => null,
            },
        ] )->all();

        $response = $this->json( 'PUT', '/api/users/widgets', [ 'widgets' => $requestedWidgets ] );

        $response->assertOk()->assertJsonPath( 'status', 'success' );

        $storedWidgets = UserWidget::where( 'user_id', $user->id )
            ->orderBy( 'position' )
            ->get();

        $this->assertSame( array_reverse( $identifiers ), $storedWidgets->pluck( 'identifier' )->all() );
        $this->assertSame( range( 0, count( $identifiers ) - 1 ), $storedWidgets->pluck( 'position' )->all() );
        $this->assertSame( array_fill( 0, count( $identifiers ), 'dashboard' ), $storedWidgets->pluck( 'column' )->all() );
        $this->assertSame( '3x5', $storedWidgets->firstWhere( 'identifier', 'testDashboardWidget' )->layout );
        $this->assertSame( '2x5', $storedWidgets->firstWhere( 'identifier', 'testWideDashboardWidget' )->layout );

        foreach ( $storedWidgets as $storedWidget ) {
            $registeredWidget = $widgets->firstWhere( 'component-name', $storedWidget->identifier );

            $this->assertSame( $registeredWidget->{'class-name'}, $storedWidget->class_name );
        }
    }

    public function test_empty_layout_removes_only_the_authenticated_users_widgets(): void
    {
        $otherUser = $this->createUser();
        [ $user ] = $this->authenticateWithTestWidgets();
        $otherWidget = UserWidget::create( [
            'identifier' => 'other-user-widget',
            'class_name' => TestDashboardWidget::class,
            'column' => 'dashboard',
            'position' => 0,
            'user_id' => $otherUser->id,
        ] );

        $this->json( 'PUT', '/api/users/widgets', [ 'widgets' => [] ] )
            ->assertOk();

        $this->assertDatabaseMissing( ( new UserWidget )->getTable(), [ 'user_id' => $user->id ] );
        $this->assertModelExists( $otherWidget );
    }

    public function test_duplicate_widget_identifiers_are_rejected(): void
    {
        [ , $widgetService ] = $this->authenticateWithTestWidgets();
        $identifier = $widgetService->getWidgets()->first()->{'component-name'};

        $this->json( 'PUT', '/api/users/widgets', [
            'widgets' => [
                [ 'identifier' => $identifier ],
                [ 'identifier' => $identifier ],
            ],
        ] )->assertUnprocessable()->assertJsonValidationErrors( 'widgets.1.identifier' );
    }

    public function test_unknown_widget_identifiers_are_rejected(): void
    {
        $this->authenticateWithTestWidgets();

        $this->json( 'PUT', '/api/users/widgets', [
            'widgets' => [ [ 'identifier' => 'unknown-widget-component' ] ],
        ] )->assertUnprocessable()->assertJsonValidationErrors( 'widgets.0.identifier' );
    }

    public function test_layout_must_be_within_the_global_bounds(): void
    {
        $this->authenticateWithTestWidgets();

        $this->json( 'PUT', '/api/users/widgets', [
            'widgets' => [ [ 'identifier' => 'testDashboardWidget', 'layout' => '3x6' ] ],
        ] )->assertUnprocessable()->assertJsonValidationErrors( 'widgets.0.layout' );
    }

    public function test_widget_policy_rejects_an_unsupported_layout(): void
    {
        $this->authenticateWithTestWidgets();

        $this->json( 'PUT', '/api/users/widgets', [
            'widgets' => [ [ 'identifier' => 'testWideDashboardWidget', 'layout' => '3x5' ] ],
        ] )->assertUnprocessable()->assertJsonValidationErrors( 'widgets.0.layout' );

        $this->json( 'PUT', '/api/users/widgets', [
            'widgets' => [ [ 'identifier' => 'testStrictDashboardWidget', 'layout' => '1x2' ] ],
        ] )->assertUnprocessable()->assertJsonValidationErrors( 'widgets.0.layout' );
    }

    public function test_null_layout_resets_a_user_override_to_the_widget_suggestion(): void
    {
        [ $user ] = $this->authenticateWithTestWidgets();

        $this->json( 'PUT', '/api/users/widgets', [
            'widgets' => [ [ 'identifier' => 'testWideDashboardWidget', 'layout' => '2x5' ] ],
        ] )->assertOk();
        $this->assertSame( '2x5', UserWidget::where( 'user_id', $user->id )->firstOrFail()->layout );

        $this->json( 'PUT', '/api/users/widgets', [
            'widgets' => [ [ 'identifier' => 'testWideDashboardWidget', 'layout' => null ] ],
        ] )->assertOk();
        $this->assertNull( UserWidget::where( 'user_id', $user->id )->firstOrFail()->layout );
    }

    public function test_unauthenticated_layout_updates_are_rejected(): void
    {
        $this->json( 'PUT', '/api/users/widgets', [ 'widgets' => [] ] )
            ->assertUnauthorized();
    }

    /**
     * @return array{User, WidgetService}
     */
    private function authenticateWithTestWidgets(): array
    {
        $user = $this->createUser();
        Sanctum::actingAs( $user, [ '*' ] );

        $widgetService = app( WidgetService::class );
        $widgetService->registerWidgets( [
            TestDashboardWidget::class,
            TestWideDashboardWidget::class,
            TestStrictDashboardWidget::class,
        ] );

        return [ $user, $widgetService ];
    }

    private function createUser(): User
    {
        return User::create( [
            'username' => 'widget_' . Str::lower( Str::random( 12 ) ),
            'email' => Str::lower( Str::random( 12 ) ) . '@widget-test.invalid',
            'password' => bcrypt( Str::random( 24 ) ),
            'active' => true,
        ] );
    }
}

class TestDashboardWidget extends WidgetService
{
    protected $vueComponent = 'testDashboardWidget';

    protected string $layoutPolicy = 'unrestricted';

    public function __construct()
    {
        $this->name = 'Test Dashboard Widget';
        $this->description = 'Used by the widget layout feature tests.';
    }
}

class TestWideDashboardWidget extends WidgetService
{
    protected $vueComponent = 'testWideDashboardWidget';

    protected string $layout = '2x1';

    protected string $layoutPolicy = 'restricted';

    protected array $supportedLayouts = [ '1x1', '2x1', '2x5' ];

    public function __construct()
    {
        $this->name = 'Test Wide Dashboard Widget';
        $this->description = 'Used by the widget layout feature tests.';
    }
}

class TestStrictDashboardWidget extends WidgetService
{
    protected $vueComponent = 'testStrictDashboardWidget';

    protected string $layout = '1x3';

    public function __construct()
    {
        $this->name = 'Test Strict Dashboard Widget';
        $this->description = 'Used by the widget layout feature tests.';
    }
}
