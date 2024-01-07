@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div>
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="px-4 flex flex-col" id="dashboard-content">
        <experiment></experiment>
    </div>
</div>
@endsection

@section( 'layout.dashboard.footer.inject' )
    <script>
    Vue.component( 'experiment', {
        template: `
        <div class="p-2 shadow bg-white w-72">
            <ns-field :field="field"></ns-field>
        </div>
        `,
        data() {
            return {
                field: {
                    label: 'Select An Image',
                    name: 'image',
                    description: 'choose one of the uploaded image',
                    type: 'media',
                    data: {
                        type: 'model',
                    },
                    errors: []
                }
            }
        }
    });
    </script>
@endsection