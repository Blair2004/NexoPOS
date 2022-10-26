@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div id="main-container" class="h-full w-full flex">
        <ns-database-update></ns-database-update>
    </div>
@endsection
@section( 'layout.base.footer' )
    @parent
    <script>
        const Update    =   {
            returnLink: '{{ $redirect ?? ( ! in_array( url()->previous(), [ ns()->route( "ns.database-update" ), ns()->route( "ns.do-setup" ) ]) ? url()->previous() : ns()->route( "ns.dashboard.home" ) ) }}',
            files:  @json( ns()->update->getMigrations()->values() ),
            modules: @json( $modules )
        }
    </script>
    @vite([ 'resources/ts/update.ts' ])
@endsection