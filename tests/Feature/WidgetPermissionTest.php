<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserWidget;
use App\Services\WidgetService;
use App\Widgets\OrdersSummaryWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class WidgetPermissionTest extends TestCase
{
    /**
     * Test that widgets without proper permissions are filtered out
     *
     * @return void
     */
    public function test_widget_permission_filtering()
    {
        // This test verifies that the bootWidgetsAreas method
        // properly filters out widgets that the user doesn't have permission to access
        
        // Create a user without the orders summary widget permission
        $user = User::factory()->create();
        
        // Act as this user
        $this->actingAs($user);
        
        // Create a widget entry for this user
        UserWidget::create([
            'user_id' => $user->id,
            'class_name' => OrdersSummaryWidget::class,
            'identifier' => 'nsOrdersSummary',
            'column' => 'first-column',
            'position' => 0,
        ]);
        
        // Define a gate that denies access to the widget
        Gate::define('nexopos.see.orders-summary-widget', function ($user) {
            return false; // Deny access
        });
        
        // Get the widget service
        $widgetService = app(WidgetService::class);
        
        // Boot the widget areas
        $widgetService->bootWidgetsAreas();
        
        // Get the widgets area
        $widgetArea = $widgetService->getWidgetsArea('ns-dashboard-widgets');
        
        // Assert that the first column has no widgets (filtered out due to permission)
        $firstColumn = $widgetArea->firstWhere('name', 'first-column');
        
        $this->assertNotNull($firstColumn, 'First column should exist');
        $this->assertCount(0, $firstColumn['widgets'], 'User should not see widgets without permission');
    }
    
    /**
     * Test that widgets with proper permissions are included
     *
     * @return void
     */
    public function test_widget_with_permission_is_included()
    {
        // Create a user with the orders summary widget permission
        $user = User::factory()->create();
        
        // Act as this user
        $this->actingAs($user);
        
        // Create a widget entry for this user
        UserWidget::create([
            'user_id' => $user->id,
            'class_name' => OrdersSummaryWidget::class,
            'identifier' => 'nsOrdersSummary',
            'column' => 'first-column',
            'position' => 0,
        ]);
        
        // Define a gate that allows access to the widget
        Gate::define('nexopos.see.orders-summary-widget', function ($user) {
            return true; // Allow access
        });
        
        // Get the widget service
        $widgetService = app(WidgetService::class);
        
        // Boot the widget areas
        $widgetService->bootWidgetsAreas();
        
        // Get the widgets area
        $widgetArea = $widgetService->getWidgetsArea('ns-dashboard-widgets');
        
        // Assert that the first column has the widget
        $firstColumn = $widgetArea->firstWhere('name', 'first-column');
        
        $this->assertNotNull($firstColumn, 'First column should exist');
        $this->assertCount(1, $firstColumn['widgets'], 'User should see widgets with permission');
    }
}
