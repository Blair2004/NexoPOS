@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div id="nexopos-authentication" class="h-full w-full bg-gray-300 flex">
        <div class="container mx-auto flex-auto items-center justify-center flex">
            <div id="sign-in-box" class="w-full md:w-2/4 lg:w-1/3">
                <div>
                    @csrf
                    <div class="flex justify-center items-center py-6">
                        <h2 class="text-6xl font-bold text-transparent bg-clip-text from-blue-500 to-teal-500 bg-gradient-to-br">NexoPOS</h2>
                    </div>
                    <div class="bg-white rounded shadow overflow-hidden transition-all duration-100">
                        <div class="p-3 -my-2">
                            <div class="py-2 fade-in-entrance anim-duration-300" v-if="fields.length > 0">
                                <ns-field v-for="field of fields" :field="field"></ns-field>
                            </div>
                        </div>
                        <div class="flex items-center justify-center" v-if="fields.length === 0">
                            <ns-spinner></ns-spinner>
                        </div>
                        <div class="flex w-full items-center justify-center py-4">
                            <a href="{{ url( '/password-lost' )}}" class="hover:underline text-blue-600 text-sm">Password Forgotten ?</a>
                        </div>
                        <div class="flex justify-between items-center bg-gray-200 p-3">
                            <div>
                                <ns-button @click="signIn()" type="info">{{ __( 'Sign In' ) }}</ns-button>
                            </div>
                            <div>
                                <x-ns-link :href="url( '/sign-up' )" type="success" :label="__( 'Register' )"></x-ns-link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section( 'layout.base.footer' )
    @parent
    <script src="{{ asset( 'js/auth.js' ) }}"></script>
@endsection