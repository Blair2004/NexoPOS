@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
    <div class="px-4 flex flex-col" id="dashboard-content">
        @include( 'common.dashboard.title' )
        <div>
            <div class="flex justify-between items-center">
                <div class="">
                    <a href="{{ ns()->route( 'ns.dashboard.modules-list' ) }}" class="rounded-lg text-primary bg-surface-secondary shadow px-3 py-1 hover:bg-info-secondary hover:text-typography"><i class="las la-angle-left"></i> {{ __( 'Go Back' ) }}</a>
                </div>
            </div>
            <form action="{{ ns()->route( 'ns.dashboard.modules-upload-post' ) }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="module-section my-4 flex flex-wrap -mx-4 text-primary">
                    <div class="px-4">
                        <div class="{{ $errors->any() ? 'form-input-invalid' : 'form-input' }}">
                            <label for="file">{{ __( 'Your Module' ) }}</label>
                            <input type="file" name="module" id="upload-file" class="my-2">
                            <p>{{ $errors->any() ? __( $errors->first( 'module' ) ) : __( 'Choose the zip file you would like to upload' ) }}</p>
                        </div>
                    </div>    
                </div>
                <div>
                    <button class="rounded-lg px-3 py-2 bg-surface-secondary hover:bg-info-secondary hover:text-typography text-secondary shadow" type="submit">{{ __( 'Upload' ) }}</button>
                </div>            
            </form>
        </div>
    </div>
</div>
@endsection