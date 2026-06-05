<script>
    if (!window.__hospitalHeartbeatInitialized) {
        window.__hospitalHeartbeatInitialized = true;

        const heartbeat = () => {
            const url = new URL(@js(route('heartbeat')), window.location.origin);
            url.searchParams.set('_hb', Date.now().toString());

            fetch(url.toString(), {
                method: 'GET',
                cache: 'no-store',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }).catch(() => {});
        };

        heartbeat();
        setInterval(heartbeat, 300000);
    }
</script>
