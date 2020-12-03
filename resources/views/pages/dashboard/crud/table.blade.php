<?php
use App\Classes\Hook;
use App\Classes\Output;
?>
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div>
    @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
    <div id="dashboard-content" class="px-4">
        <div class="page-inner-header mb-4">
            <h3 class="text-3xl text-gray-700 font-bold">{{ $title ?? __( 'Unamed Table' ) }}</h3>
            <p class="text-gray-600">{{ $description ?? __( 'No description' ) }}</p>
        </div>
        <ns-crud 
            src="{{ $src }}" 
            :query-params='@json( $queryParams ?? [] )'
            create-url="{{ $createUrl ?? '#' }}"
            id="crud-table-body">
            <template v-slot:bulk-label>{{ $bulkLabel ?? __( 'Bulk Actions' ) }}</template>
        </ns-crud>
    </div>
</div>
@endsection

@section( 'layout.dashboard.footer' )
    @parent
<?php
$identifier    =   collect( explode( '/', $src ) )
    ->filter( fn( $segment ) => ! empty( $segment ) )
    ->last();
?>
{!! ( string ) Hook::filter( 'ns-crud-footer', new Output, $identifier ) !!}
@endsection