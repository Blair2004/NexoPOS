@php
    $adService = new \App\Services\AdService();
    $adToDisplay = $adService->getAdToDisplay(auth()->user(), request()->route()->getName());

    $bgClass = $adToDisplay['style']['bgClass'] ?? 'bg-box-background';
    $textClass = $adToDisplay['style']['textClass'] ?? 'text-fontcolor';
    $borderClass = $adToDisplay['style']['borderClass'] ?? 'border-box-edge';
@endphp

@if($adToDisplay)
<div id="ns-dashboard-ad" class="m-4 mt-0 rounded-lg {{ $bgClass }} {{ $textClass }} px-4 py-2 flex items-center justify-between border {{ $borderClass }} text-sm">
    <div class="flex flex-auto items-center">
        @if(!empty($adToDisplay['icon']))
        <i class="las {{ $adToDisplay['icon'] }} text-2xl mr-2"></i>
        @endif
        <div>
            @if(!empty($adToDisplay['title']))
            <strong class="font-semibold">{{ $adToDisplay['title'] }}</strong> - 
            @endif
            <span>{{ $adToDisplay['message'] }}</span>
        </div>
    </div>
    <div class="flex items-center space-x-4">
        @if(!empty($adToDisplay['url']))
        <a href="{{ $adToDisplay['url'] }}" target="_blank" class="font-medium hover:underline">{{ __('Learn More') }}</a>
        @endif
        <button type="button" id="ad-snooze-btn" class="opacity-75 hover:opacity-100 flex items-center" title="{{ __('Snooze for 24h') }}">
            <i class="las la-times text-xl"></i>
        </button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const snoozeBtn = document.getElementById('ad-snooze-btn');
        if (snoozeBtn) {
            snoozeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('ns-dashboard-ad').style.display = 'none';
                
                if (typeof nsHttpClient !== 'undefined') {
                    nsHttpClient.post('{{ ns()->url('/api/user/snooze-ads') }}').subscribe({
                        next: () => console.log('Ad snoozed successfully'),
                        error: (err) => console.error('Error snoozing ad', err)
                    });
                } else {
                    fetch('{{ ns()->url('/api/user/snooze-ads') }}', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    }).catch(err => console.error('Error snoozing ad:', err));
                }
            });
        }
    });
</script>
@endif
