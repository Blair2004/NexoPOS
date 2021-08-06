@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div id="update-app" class="h-full w-full bg-gray-300 flex">
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
    <script src="{{ asset( ns()->isProduction() ? '/js/update.min.js' : '/js/update.js' ) }}"></script>
@endsection