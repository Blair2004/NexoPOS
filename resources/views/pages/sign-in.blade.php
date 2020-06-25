@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div id="page-container" class="h-full w-full bg-gray-300 flex">
        <div class="container mx-auto flex-auto items-center justify-center flex">
            <div id="sign-in-box" class="w-full md:w-1/3">
                <div class="bg-white p-3 rounded shadow -my-2">
                    <div class="form-field flex flex-col my-2">
                        <label for="" class="font-semibold text-sm mb-1">Username</label>
                        <input type="text" class="border-2 border-blue-200 bg-blue-100 rounded p-2 w-full">
                        <p class="text-xs text-gray-600">Provide your username</p>
                    </div>
                    <div class="form-field flex flex-col my-2">
                        <label for="" class="font-semibold text-sm mb-1">Password</label>
                        <input type="password" class="border-2 border-blue-200 bg-blue-100 rounded p-2 w-full">
                        <p class="text-xs text-gray-600">Provide your password</p>
                    </div>
                    <div class="flex justify-between items-center my-2">
                        <div>
                            <a href="{{ url( '/password-lost' )}}" class="hover:underline text-blue-600 text-sm">Password Forgotten ?</a>
                        </div>
                        <div class="flex -mx-2">
                            <div class="px-2">
                                <a href="{{ url( '/sign-up' )}}" class=" text-center block rounded border border-gray-600 w-24 bg-gray-400 text-gray-800 py-1 px-3">Register</a>
                            </div>
                            <div class="px-2">
                                <button class=" text-center rounded shadow border border-blue-600 w-24 bg-blue-400 text-white py-1 px-3">Login</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection