@inject( 'widgetService', 'App\Services\WidgetService' )
<div id="dashboard-content" class="px-4">
    <ns-dashboard>
        <ns-dragzone 
            :raw-columns="{{ $widgetService->getWidgetsArea( 'ns-dashboard-widgets' )->values()->toJson() }}" 
            :raw-widgets="{{ $widgetService->getWidgets()->values()->toJson() }}">
        </ns-dragzone>
    </ns-dashboard>
</div>