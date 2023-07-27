@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body.with-title' )
    <ns-transaction></ns-transaction>
@endsection

@section( 'layout.dashboard.footer' )
    <script>
        window.nsTransactionData     =   @json( $transaction );
    </script>
    @parent
@endsection