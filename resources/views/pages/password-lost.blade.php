@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div id="page-container" class="h-full w-full bg-gray-300 flex">
        <div class="container mx-auto flex-auto items-center justify-center flex">
            <div id="sign-in-box" class="w-full md:w-1/3">
                <div class="bg-white p-3 rounded shadow -my-2">
                    <div class="form-field flex flex-col my-2">
                        <label for="" class="font-semibold text-sm mb-1">{{ __( 'Email' ) }}</label>
                        <input type="email" class="border-2 border-blue-200 bg-blue-100 rounded p-2 w-full">
                        <p class="text-xs text-gray-600">{{ __( 'Provide the email you\'ve signed with.' ) }}</p>
                    </div>
                    <div class="flex justify-between items-center my-2">
                        <div>
                            <a href="{{ url( '/sign-in' ) }}" class="hover:underline text-blue-600 text-sm">Remember your password ?</a>
                        </div>
                        <div class="flex -mx-2">
                            <div class="px-2">
                                <button class="text-center rounded shadow border border-blue-600 w-24 bg-blue-400 text-white py-2 px-3">Recover</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection