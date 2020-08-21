@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div id="page-container" class="h-full w-full bg-gray-300 flex">
        <div class="container mx-auto flex-auto items-center justify-center flex">
            <form action="{{ route( 'ns.register.post' ) }}" method="post" id="sign-in-box" class="w-full md:w-1/3">
                @csrf
                <div class="flex justify-center items-center py-6">
                    <h2 class="text-6xl font-bold text-transparent bg-clip-text from-blue-500 to-teal-500 bg-gradient-to-br">NexoPOS</h2>
                </div>
                <div class="bg-white rounded shadow overflow-hidden">
                    <div class="p-3 -my-2">
                        <div class="{{ $errors->has( 'password' ) ? 'form-input-invalid' : 'form-input' }} flex flex-col my-2">
                            @if( $errors->has( 'username' ) )
                            <label for="username">{{ __( 'Username' ) }}</label>
                            @else
                            <label for="username">{{ __( 'Username' ) }}</label>
                            @endif
                            <input type="text" name="username" value="{{ old( 'username' ) }}">
                            @if( $errors->has( 'username' ) )
                                @foreach( $errors->get( 'username' ) as $error )
                                <p>{{ $error }}</p>
                                @endforeach
                            @else
                            <p>{{ __( 'Provide your username' ) }}</p>
                            @endif
                        </div>
                        <div class="{{ $errors->has( 'password' ) ? 'form-input-invalid' : 'form-input' }} flex flex-col my-2">
                            @if( $errors->has( 'email' ) )
                            <label for="email">{{ __( 'Email' ) }}</label>
                            @else
                            <label for="email">{{ __( 'Email' ) }}</label>
                            @endif
                            <input type="email" name="email" value="{{ old( 'email' ) }}" class="{{ $errors->has( 'password' ) ? 'form-input-invalid' : 'form-input' }}">
                            @if( $errors->has( 'email' ) )
                                @foreach( $errors->get( 'email' ) as $error )
                                <p>{{ $error }}</p>
                                @endforeach
                            @else
                            <p>{{ __( 'Helpful to recover your account.' ) }}</p>
                            @endif
                        </div>
                        <div class="{{ $errors->has( 'password' ) ? 'form-input-invalid' : 'form-input' }} flex flex-col my-2">
                            @if( $errors->has( 'password' ) )
                            <label for="password" class="font-semibold text-red-600 text-sm mb-1">{{ __( 'Password' ) }}</label>
                            @else
                            <label for="password">{{ __( 'Password' ) }}</label>
                            @endif
                            <input type="password" name="password" value="{{ old( 'password' ) }}" class="{{ $errors->has( 'password' ) ? 'form-input-invalid' : 'form-input' }}">
                            @if( $errors->has( 'password' ) )
                                @foreach( $errors->get( 'password' ) as $error )
                                <p>{{ $error }}</p>
                                @endforeach
                            @else
                            <p>{{ __( 'Provide your password' ) }}</p>
                            @endif
                        </div>
                        <div class="{{ $errors->has( 'password' ) ? 'form-input-invalid' : 'form-input' }} flex flex-col my-2">
                            @if( $errors->has( 'password' ) )
                            <label for="password_confirm" class="font-semibold text-red-600 text-sm mb-1">{{ __( 'Password Confirm' ) }}</label>
                            @else
                            <label for="password_confirm">{{ __( 'Password Confirm' ) }}</label>
                            @endif
                            <input type="password" name="password_confirm" value="{{ old( 'password_confirm' ) }}" class="{{ $errors->has( 'password' ) ? 'form-input-invalid' : 'form-input' }}">
                            @if( $errors->has( 'password_confirm' ) )
                                @foreach( $errors->get( 'password_confirm' ) as $error )
                                <p>{{ $error }}</p>
                                @endforeach
                            @else
                            <p>{{ __( 'Should be the same as the password.' ) }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-gray-200">
                        <div>
                            <a href="{{ url( '/sign-in' ) }}" class="hover:underline text-blue-600 text-sm">{{ __( 'Already registered ?' ) }}</a>
                        </div>
                        <div class="flex -mx-2">
                            <div class="px-2">
                                <button class="btn btn-blue">{{ __( 'Register' ) }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection