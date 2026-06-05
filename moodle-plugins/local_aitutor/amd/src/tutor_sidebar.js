// AI Tutor sidebar — JWT fetch + SSE streaming to Cloudflare Worker.
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    return {
        init: function(courseid, cmid) {
            const root = document.getElementById('local-aitutor-sidebar');
            if (!root) {
                return;
            }

            const output = root.querySelector('.local-aitutor-output');
            const toggle = root.querySelector('.local-aitutor-toggle');
            const collapsed = localStorage.getItem('local_aitutor_collapsed') === '1';
            if (collapsed) {
                root.classList.add('collapsed');
            }

            toggle?.addEventListener('click', () => {
                root.classList.toggle('collapsed');
                localStorage.setItem('local_aitutor_collapsed', root.classList.contains('collapsed') ? '1' : '0');
            });

            const fetchJwt = () => Ajax.call([{
                methodname: 'local_aitutor_get_jwt',
                args: {courseid: courseid, cmid: cmid || 0, conversationuuid: ''},
            }])[0];

            root.querySelector('.local-aitutor-send')?.addEventListener('click', async () => {
                const prompt = root.querySelector('.local-aitutor-input')?.value?.trim();
                if (!prompt || !output) {
                    return;
                }
                output.textContent = '';
                try {
                    const [{token, workerurl}] = await fetchJwt();
                    const url = workerurl + '?token=' + encodeURIComponent(token) + '&q=' + encodeURIComponent(prompt);
                    const source = new EventSource(url);
                    source.onmessage = (event) => {
                        output.textContent += event.data;
                    };
                    source.onerror = () => {
                        source.close();
                        output.textContent = M.util.get_string('unavailable', 'local_aitutor');
                    };
                } catch (err) {
                    Notification.exception(err);
                }
            });
        },
    };
});
