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
            returnLink: '{{ $redirect ?? ( ! in_array( url()->previous(), [ url( "database-update" ), route( "setup" ) ]) ? url()->previous() : url( "/dashboard" ) ) }}',
            files:  @json( ns()->update->getMigrations()->values() )
        }
    </script>
    <script src="{{ asset( '/js/update.js' ) }}"></script>
@endsection