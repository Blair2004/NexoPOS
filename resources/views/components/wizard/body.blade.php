@extends( 'layout.dashboard-blank' )

@section( 'layout.dashboard.body' )
<div id="wizard-wrapper">
    <NsWizard/>
</div>
@vite([ 'resources/ts/wizard.ts' ])
@endsection