@extends( 'layout.base' )

@section( 'layout.base.body' )
<div id="dev-app" class="h-full flex w-full">
    <div class="flex flex-auto">
        <div class="aside w-60 bg-surface">
            <div class="py-8 flex items-center justify-center">
                <h1 class="font-bold text-2xl">Components</h1>
            </div>
            <ns-menu label="Date Controls">
                <ns-submenu to="/inputs/date">Date Picker</ns-submenu>
                <ns-submenu to="/inputs/daterange">Date Range Picker</ns-submenu>
                <ns-submenu to="/inputs/datetime">Date Time Picker</ns-submenu>
            </ns-menu>
            <ns-menu label="Input Controls">
                <ns-submenu to="/inputs/inline-multiselect">Inline Multiselect</ns-submenu>
                <ns-submenu to="/inputs/multiselect">Multiselect</ns-submenu>
                <ns-submenu to="/inputs/upload">Upload</ns-submenu>
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