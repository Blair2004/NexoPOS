@extends( 'layout.dashboard' )

@section( 'layout.dashboard.with-title' )
    <div class="w-full md:w-1/2 ns-box border">
        <div class="box-header p-2 text-center border-b">{{ __( 'Environment Details' ) }}</div>
        <div class="box-body p-2">
            <table class="table ns-table">
                <thead>
                    <tr class="info">
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
                    <tr class="info">
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
                    <tr class="info">
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

            @if ( env( 'APP_DEBUG' ) )
            <br>

            <table class="table ns-table">
                <thead>
                    <tr>
                        <th colspan="2" class="p-2">{{ __( 'Developper Section' ) }}</th>
                    </tr>
                    <tr class="info">
                        <th class="p-2">{{ __( 'Configurations' ) }}</th>
                        <th class="p-2">{{ __( 'Status' ) }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach( $developpers as $label => $value )
                    <tr>
                        <td class="p-2">{{ $label }}</td>
                        <td class="p-2 text-right">{{ $value }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @endif
        </div>
    </div>
@endsection
