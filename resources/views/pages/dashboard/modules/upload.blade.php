@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
    <div class="px-4 flex flex-col" id="dashboard-content">
        <div class="flex-auto flex flex-col">
            <div class="page-inner-header mb-4">
                <h3 class="text-3xl text-gray-800 font-bold">{{ $title ?? __( 'Unamed Page' ) }}</h3>
                <p class="text-gray-600">{{ $description ?? __( 'No Description Provided' ) }}</p>
            </div>
        </div>
        <div>
            <div class="flex justify-between items-center">
                <div class="">
                    <a href="{{ ns()->route( 'ns.dashboard.modules-list' ) }}" class="rounded-lg text-gray-600 bg-white shadow px-3 py-1 hover:bg-blue-400 hover:text-white"><i class="las la-angle-left"></i> {{ __( 'Go Back' ) }}</a>
                </div>
            </div>
            <form action="{{ ns()->route( 'ns.dashboard.modules-upload-post' ) }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="module-section my-4 flex flex-wrap -mx-4">
                    <div class="px-4">
                        <div class="{{ $errors->any() ? 'form-input-invalid' : 'form-input' }}">
                            <label for="file">{{ __( 'Your Module' ) }}</label>
                            <input type="file" name="module" id="upload-file" class="my-2">
                            <p>{{ $errors->any() ? __( $errors->first( 'module' ) ) : __( 'Choose the zip file you would like to upload' ) }}</p>
                        </div>
                    </div>    
                </div>
                <div>
                    <button class="rounded-lg px-3 py-2 bg-white hover:bg-blue-400 hover:text-white text-gray-700 shadow" type="submit">{{ __( 'Upload' ) }}</button>
                </div>            
            </form>
        </div>
    </div>
</div>
@endsection