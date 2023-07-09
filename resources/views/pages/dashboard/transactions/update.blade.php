@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body.with-title' )
    <ns-expense></ns-expense>
@endsection

@section( 'layout.dashboard.footer' )
    <script>
        window.nsExpenseData     =   @json( $transaction );
    </script>
    @parent
@endsection