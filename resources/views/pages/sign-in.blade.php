@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div id="nexopos-authentication" class="h-full w-full bg-gray-300 flex">
        <div class="container mx-auto flex-auto items-center justify-center flex">
            <div id="sign-in-box" class="w-full md:w-2/4 lg:w-1/3">
                <form action="{{ url( '/auth/sign-in' ) }}" method="post">
                    @csrf
                    <div class="flex justify-center items-center py-6">
                        <h2 class="text-6xl font-bold">NexoPOS</h2>
                    </div>
                    <div class="bg-white p-3 rounded shadow -my-2">
                        <div class="form-field flex flex-col my-2">
                            @if( $errors->has( 'password' ) )
                            <label for="" class="font-semibold text-red-600 text-sm mb-1">Username</label>
                            @else
                            <label for="" class="font-semibold text-sm mb-1">Username</label>
                            @endif
                            <input name="username" type="text" class="border-2 border-blue-200 bg-blue-100 rounded p-2 w-full">
                            @if( $errors->has( 'username' ) )
                                @foreach( $errors->get( 'username' ) as $error )
                                <p class="text-xs text-red-600">{{ $error }}</p>
                                @endforeach
                            @else
                            <p class="text-xs text-gray-600">Provide your username</p>
                            @endif
                        </div>
                        <div class="form-field flex flex-col my-2">
                            @if( $errors->has( 'password' ) )
                            <label for="" class="font-semibold text-red-600 text-sm mb-1">Password</label>
                            @else
                            <label for="" class="font-semibold text-sm mb-1">Password</label>
                            @endif
                            <input name="password" type="password" class="border-2 border-blue-200 bg-blue-100 rounded p-2 w-full">
                            @if( $errors->has( 'password' ) )
                                @foreach( $errors->get( 'password' ) as $error )
                                <p class="text-xs text-red-600">{{ $error }}</p>
                                @endforeach
                            @else
                            <p class="text-xs text-gray-600">Provide your password</p>
                            @endif
                        </div>
                        <div class="flex justify-between flex-col items-center my-2">
                            <div class="flex justify-between w-full mb-6">
                                <div>
                                    <x-ns-button :label="__( 'Sign In' )" color="blue"/>
                                </div>
                                <div>
                                    <x-ns-link :href="url( '/sign-up' )" type="success" :label="__( 'Register' )"></x-ns-link>
                                </div>
                            </div>
                            <div>
                                <a href="{{ url( '/password-lost' )}}" class="hover:underline text-blue-600 text-sm">Password Forgotten ?</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section( 'layout.base.footer' )
    @parent
    <script src="{{ asset( 'js/auth.js' ) }}"></script>
@endsection