<script>
document.addEventListener('DOMContentLoaded', function() {
    window['DriversWidgetComponent'] = defineComponent({
        template: `<div class='ns-box p-4'>
            <h2 class='text-lg font-bold mb-2'>{{ __('Drivers Overview') }}</h2>
            <p>{{ __('This widget displays driver-related information and actions.') }}</p>
            <!-- Add driver stats, actions, or links here -->
        </div>`
    });
});
</script>
