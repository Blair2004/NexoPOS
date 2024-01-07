@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="px-4 flex flex-col" id="dashboard-content">
        @include( 'common.dashboard.title' )
        <div>
            <div class="flex justify-between items-center">
                <div class="ns-button">
                    <a href="{{ ns()->route( 'ns.dashboard.modules-list' ) }}" class="rounded-lg text-primary shadow px-3 py-1"><i class="las la-angle-left"></i> {{ __( 'Go Back' ) }}</a>
                </div>
            </div>
            <form action="{{ ns()->route( 'ns.dashboard.modules-upload-post' ) }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="module-section my-4 flex flex-wrap -mx-4">
                    <div class="px-4">
                        <div class="ns-box rounded shadow">
                            <div class="{{ $errors->any() ? 'form-input-invalid' : 'form-input' }} ns-box-body p-2">
                                <label for="file">{{ __( 'Your Module' ) }}</label>
                                <input type="file" name="module" id="upload-file" class="my-2">
                                <p>{{ $errors->any() ? __( $errors->first( 'module' ) ) : __( 'Choose the zip file you would like to upload' ) }}</p>
                            </div>
                            <div class="ns-box-footer border-t p-2 justify-end">
                                <div class="ns-button info">
                                    <button class="rounded-lg px-3 py-2 shadow" type="submit">{{ __( 'Upload' ) }}</button>
                                </div>
                            </div>
                        </div>
                    </div>    
                </div>        
            </form>
        </div>
    </div>
</div>
@endsection