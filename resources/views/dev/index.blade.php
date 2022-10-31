@extends( 'layout.base' )

@section( 'layout.base.body' )
<div id="dev-app" class="h-full flex w-full">
    <div class="flex flex-auto">
        <div class="aside w-60 bg-surface">
            <ns-menu label="Inputs">
                <ns-submenu to="/inputs/datetime">Date Time Picker</ns-submenu>
                <ns-submenu to="/inputs/daterange">Date Range Picker</ns-submenu>
            </ns-menu>
        </div>
        <div class="body bg-surface w-full flex flex-col">
            <router-view></router-view>
        </div>
    </div>
</div>
@endsection

@section( 'layout.base.footer' )
    @parent
    @vite([ 'resources/ts/dev.ts' ])
@endsection