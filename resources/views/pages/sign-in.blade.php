@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div id="nexopos-authentication" class="h-full w-full bg-gray-300 flex">
        <div class="container mx-auto flex-auto items-center justify-center flex">
            <div id="sign-in-box" class="w-full md:w-2/4 lg:w-1/3">
                <form action="{{ url( '/auth/sign-in' ) }}" method="post">
                    @csrf
                    <div class="flex justify-center items-center py-6">
                        <h2 class="text-6xl font-bold text-transparent bg-clip-text from-blue-500 to-teal-500 bg-gradient-to-br">NexoPOS</h2>
                    </div>
                    <div class="bg-white rounded shadow overflow-hidden">
                        <div class="p-3 -my-2">
                            <div class="{{ $errors->has( 'username' ) ? 'form-input-invalid' : 'form-input' }} flex flex-col my-2">
                                @if( $errors->has( 'username' ) )
                                <label for="username">{{ __( 'Username' ) }}</label>
                                @else
                                <label for="username">{{ __( 'Username' ) }}</label>
                                @endif
                                <input name="username" value="{{ old( 'username' ) }}" type="text">
                                @if( $errors->has( 'username' ) )
                                    @foreach( $errors->get( 'username' ) as $error )
                                    <p>{{ $error }}</p>
                                    @endforeach
                                @else
                                <p>{{ __( 'Provide your username' ) }}</p>
                                @endif
                            </div>
                            <div class="{{ $errors->has( 'password' ) ? 'form-input-invalid' : 'form-input' }} flex flex-col my-2">
                                @if( $errors->has( 'password' ) )
                                <label for="password">{{ __( 'Password' ) }}</label>
                                @else
                                <label for="password">{{ __( 'Password' ) }}</label>
                                @endif
                                <input name="password" type="password">
                                @if( $errors->has( 'password' ) )
                                    @foreach( $errors->get( 'password' ) as $error )
                                    <p>{{ $error }}</p>
                                    @endforeach
                                @else
                                <p>{{ __( 'Provide your password' ) }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex w-full items-center justify-center py-4">
                            <a href="{{ url( '/password-lost' )}}" class="hover:underline text-blue-600 text-sm">Password Forgotten ?</a>
                        </div>
                        <div class="flex justify-between items-center bg-gray-200 p-3">
                            <div>
                                <x-ns-button :label="__( 'Sign In' )" color="blue"/>
                            </div>
                            <div>
                                <x-ns-link :href="url( '/sign-up' )" type="success" :label="__( 'Register' )"></x-ns-link>
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