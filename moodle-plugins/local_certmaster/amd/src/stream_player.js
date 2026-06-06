// Cloudflare Stream player — refresh signed iframe URL before 60s JWT expiry.
define(['core/ajax'], function(Ajax) {
    /** Refresh interval: 50s (policy max 60s). */
    const REFRESH_MS = 50000;

    /**
     * @param {string} videoid Stream UID (server-side only; not stored in DOM).
     * @return {boolean}
     */
    const init = (videoid) => {
        const root = document.querySelector('.local-certmaster-stream-player');
        const iframe = root?.querySelector('.local-certmaster-stream-iframe');
        if (!root || !iframe || !videoid) {
            return false;
        }

        const refresh = () => {
            Ajax.call([{
                methodname: 'local_certmaster_get_stream_iframe_url',
                args: {videoid: videoid},
            }])[0].then((result) => {
                if (result?.iframesrc) {
                    iframe.src = result.iframesrc;
                    root.dataset.expiresat = String(result.expiresat ?? '');
                }
            }).catch(() => {
                // Player keeps last signed URL until user reloads.
            });
        };

        window.setInterval(refresh, REFRESH_MS);
        return true;
    };

    return {init};
});
