<?php
use App\Classes\Hook;
use App\Classes\Output;
?>
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div>
    @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
    <div id="dashboard-content" class="px-4">
        @include( 'common.dashboard.title' )
        @include( 'components.session-message' )
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