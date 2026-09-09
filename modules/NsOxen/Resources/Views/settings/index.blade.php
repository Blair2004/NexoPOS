@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="flex-auto flex flex-col">
    @include(Hook::filter('ns-dashboard-header-file', '../common/dashboard-header'))
    <div class="px-4 flex flex-col" id="dashboard-content">
        <div class="flex-auto flex flex-col">
            @include('common.dashboard.title')
        </div>
        <div>
            <ns-oxen-settings></ns-oxen-settings>
        </div>
    </div>
</div>
@endsection

@section('layout.dashboard.footer.inject')
    @parent
    @moduleViteAssets('Resources/ts/settings.ts', 'NsOxen')
@endsection
