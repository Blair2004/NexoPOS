@extends( 'layout.dashboard' )

@section( 'layout.dashboard.with-title' )
    <div class="w-full md:w-1/2 box">
        <div class="box-body">
            <table class="table ns-table">
                <thead>
                    <tr>
                        <th class="p-2">{{ __( 'Properties' ) }}</th>
                        <th class="p-2">{{ __( 'Value' ) }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach( $details as $label => $value )
                    <tr>
                        <td class="p-2">{{ $label }}</td>
                        <td class="p-2 text-right">{{ $value }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <br>

            <table class="table ns-table">
                <thead>
                    <tr>
                        <th class="p-2">{{ __( 'Extensions' ) }}</th>
                        <th class="p-2">{{ __( 'Status' ) }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach( $extensions as $label => $value )
                    <tr class="{{ $value ? 'success' : 'error' }}">
                        <td class="p-2">{{ $label }}</td>
                        <td class="p-2 text-right">{{ $value ? __( 'Yes' ) : __( 'No' ) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <br>

            <table class="table ns-table">
                <thead>
                    <tr>
                        <th class="p-2">{{ __( 'Configurations' ) }}</th>
                        <th class="p-2">{{ __( 'Status' ) }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach( $configurations as $label => $value )
                    <tr>
                        <td class="p-2">{{ $label }}</td>
                        <td class="p-2 text-right">{{ $value }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection