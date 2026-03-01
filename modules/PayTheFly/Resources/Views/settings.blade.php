@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="h-full flex flex-col">
    <div class="flex-auto flex flex-col overflow-y-auto" id="paythefly-settings">
        <div class="p-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold">
                    <i class="las la-wallet text-3xl mr-2"></i>
                    {{ __( 'PayTheFly Crypto Payment' ) }}
                </h2>
                <div class="flex items-center gap-2">
                    @if( $service->isConfigured() && $service->isEnabled() )
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                            <i class="las la-check-circle"></i> {{ __( 'Active' ) }}
                        </span>
                    @else
                        <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium">
                            <i class="las la-times-circle"></i> {{ __( 'Inactive' ) }}
                        </span>
                    @endif
                </div>
            </div>

            @if( session( 'success' ) )
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session( 'success' ) }}
                </div>
            @endif

            @if( session( 'error' ) )
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session( 'error' ) }}
                </div>
            @endif

            @if( $errors->any() )
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc pl-5">
                        @foreach( $errors->all() as $error )
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route( 'ns.dashboard.modules-settings.paythefly.save' ) }}">
                @csrf

                {{-- Webhook URL Info --}}
                <div class="bg-blue-50 border border-blue-200 rounded p-4 mb-6">
                    <h3 class="font-semibold text-blue-800 mb-2">
                        <i class="las la-info-circle"></i> {{ __( 'Webhook URL' ) }}
                    </h3>
                    <p class="text-sm text-blue-700 mb-2">
                        {{ __( 'Configure this URL in your PayTheFly Pro dashboard as the webhook endpoint:' ) }}
                    </p>
                    <code class="block bg-white border rounded px-3 py-2 text-sm font-mono break-all select-all">
                        {{ url( '/api/paythefly/webhook' ) }}
                    </code>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Enable/Disable --}}
                    <div class="col-span-full">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="paythefly_enabled" value="0">
                            <input
                                type="checkbox"
                                name="paythefly_enabled"
                                value="1"
                                class="w-5 h-5 rounded"
                                {{ $options->get( 'paythefly_enabled', false ) ? 'checked' : '' }}
                            >
                            <span class="text-lg font-medium">{{ __( 'Enable PayTheFly Payments' ) }}</span>
                        </label>
                    </div>

                    {{-- Project ID --}}
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __( 'Project ID' ) }} <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            name="paythefly_project_id"
                            value="{{ old( 'paythefly_project_id', $options->get( 'paythefly_project_id', '' ) ) }}"
                            class="w-full border rounded px-3 py-2"
                            placeholder="your-project-id"
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">{{ __( 'From your PayTheFly Pro dashboard.' ) }}</p>
                    </div>

                    {{-- Project Key --}}
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __( 'Project Key (HMAC Secret)' ) }} <span class="text-red-500">*</span></label>
                        <input
                            type="password"
                            name="paythefly_project_key"
                            value="{{ old( 'paythefly_project_key', $options->get( 'paythefly_project_key', '' ) ) }}"
                            class="w-full border rounded px-3 py-2"
                            placeholder="••••••••"
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">{{ __( 'Used for webhook signature verification.' ) }}</p>
                    </div>

                    {{-- Private Key --}}
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __( 'Signer Private Key' ) }} <span class="text-red-500">*</span></label>
                        <input
                            type="password"
                            name="paythefly_private_key"
                            value="{{ old( 'paythefly_private_key', $options->get( 'paythefly_private_key', '' ) ) }}"
                            class="w-full border rounded px-3 py-2"
                            placeholder="0x..."
                            required
                        >
                        <p class="text-xs text-red-500 mt-1">
                            <i class="las la-exclamation-triangle"></i>
                            {{ __( 'Used for EIP-712 payment request signing. Keep this secret!' ) }}
                        </p>
                    </div>

                    {{-- Verifying Contract --}}
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __( 'Verifying Contract Address' ) }} <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            name="paythefly_verifying_contract"
                            value="{{ old( 'paythefly_verifying_contract', $options->get( 'paythefly_verifying_contract', '' ) ) }}"
                            class="w-full border rounded px-3 py-2"
                            placeholder="0x..."
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">{{ __( 'PayTheFly Pro smart contract address for EIP-712 domain.' ) }}</p>
                    </div>

                    {{-- Chain --}}
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __( 'Blockchain Network' ) }} <span class="text-red-500">*</span></label>
                        <select
                            name="paythefly_chain"
                            class="w-full border rounded px-3 py-2"
                            required
                        >
                            @php $selectedChain = old( 'paythefly_chain', $options->get( 'paythefly_chain', 'BSC' ) ); @endphp
                            <option value="BSC" {{ $selectedChain === 'BSC' ? 'selected' : '' }}>BSC (BNB Chain) — Chain ID 56</option>
                            <option value="TRON" {{ $selectedChain === 'TRON' ? 'selected' : '' }}>TRON — Chain ID 728126428</option>
                        </select>
                    </div>

                    {{-- Token Address --}}
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __( 'Custom Token Address' ) }}</label>
                        <input
                            type="text"
                            name="paythefly_token_address"
                            value="{{ old( 'paythefly_token_address', $options->get( 'paythefly_token_address', '' ) ) }}"
                            class="w-full border rounded px-3 py-2"
                            placeholder="{{ __( 'Leave empty for native token (BNB/TRX)' ) }}"
                        >
                        <p class="text-xs text-gray-500 mt-1">
                            {{ __( 'BSC native: 0x0000...0000 | TRON native: T9yD14Nj...uWwb' ) }}
                        </p>
                    </div>

                    {{-- Deadline --}}
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ __( 'Payment Deadline (minutes)' ) }} <span class="text-red-500">*</span></label>
                        <input
                            type="number"
                            name="paythefly_deadline_minutes"
                            value="{{ old( 'paythefly_deadline_minutes', $options->get( 'paythefly_deadline_minutes', 30 ) ) }}"
                            class="w-full border rounded px-3 py-2"
                            min="5"
                            max="1440"
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">{{ __( 'How long the payment link stays valid. Default: 30 minutes.' ) }}</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded font-medium">
                        <i class="las la-save mr-1"></i>
                        {{ __( 'Save Settings' ) }}
                    </button>
                </div>
            </form>

            {{-- Supported Chains Reference --}}
            <div class="mt-8 border-t pt-6">
                <h3 class="text-lg font-semibold mb-3">{{ __( 'Supported Networks' ) }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="border rounded p-4">
                        <h4 class="font-medium mb-2">🔶 BSC (BNB Smart Chain)</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li><strong>Chain ID:</strong> 56</li>
                            <li><strong>Decimals:</strong> 18</li>
                            <li><strong>Native Token:</strong> BNB</li>
                            <li><strong>Native Address:</strong> <code class="text-xs">0x0000...0000</code></li>
                        </ul>
                    </div>
                    <div class="border rounded p-4">
                        <h4 class="font-medium mb-2">💎 TRON</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li><strong>Chain ID:</strong> 728126428</li>
                            <li><strong>Decimals:</strong> 6</li>
                            <li><strong>Native Token:</strong> TRX</li>
                            <li><strong>Native Address:</strong> <code class="text-xs">T9yD14Nj...uWwb</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
