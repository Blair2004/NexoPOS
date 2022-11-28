@inject( 'widgetService', 'App\Services\WidgetService' )
<div id="dashboard-content" class="px-4">
    <ns-dashboard>
        <ns-dragzone 
            :raw-columns="{{ $widgetService->getWidgetsArea( 'ns-dashboard-widgets' )->toJson() }}" 
            :raw-widgets="{{ $widgetService->getWidgets()->toJson() }}">
        </ns-dragzone>
    </ns-dashboard>
</div>