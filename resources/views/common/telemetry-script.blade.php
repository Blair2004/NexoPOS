<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof nsHttpClient !== 'undefined') {
            nsHttpClient.post('/api/system/telemetry').subscribe({
                next: (response) => {
                    console.log('Telemetry sent successfully.');
                },
                error: (error) => {
                    console.error('Failed to send telemetry:', error);
                }
            });
        }
    });
</script>