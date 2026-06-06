// AI Tutor sidebar — JWT fetch + SSE streaming to Cloudflare Worker.
define(['core/ajax', 'core/str'], function(Ajax, Str) {
    const STRINGS = [
        {key: 'unavailable', component: 'local_aitutor'},
        {key: 'loading', component: 'local_aitutor'},
        {key: 'error_generic', component: 'local_aitutor'},
    ];

    /**
     * Bind sidebar UI once the footer-injected markup is in the DOM.
     *
     * @param {number} courseid
     * @param {number} cmid
     * @return {boolean} True when binding completed.
     */
    const bindSidebar = (courseid, cmid) => {
        const root = document.getElementById('local-aitutor-sidebar');
        if (!root || root.dataset.aitutorBound === '1') {
            return !!root;
        }
        root.dataset.aitutorBound = '1';

        const output = root.querySelector('.local-aitutor-output');
        const sendBtn = root.querySelector('.local-aitutor-send');
        const toggle = root.querySelector('.local-aitutor-toggle');
        const input = root.querySelector('.local-aitutor-input');

        const collapsed = localStorage.getItem('local_aitutor_collapsed') === '1';
        if (collapsed) {
            root.classList.add('collapsed');
        }

        toggle?.addEventListener('click', () => {
            root.classList.toggle('collapsed');
            localStorage.setItem(
                'local_aitutor_collapsed',
                root.classList.contains('collapsed') ? '1' : '0',
            );
        });

        let strings = {
            unavailable: 'AI Tutor is temporarily unavailable. Please try again later.',
            loading: 'Thinking…',
            error_generic: 'Something went wrong. Please try again.',
        };

        Str.get_strings(STRINGS).then((resolved) => {
            strings = {
                unavailable: resolved[0],
                loading: resolved[1],
                error_generic: resolved[2],
            };
        });

        const setLoading = (active) => {
            root.classList.toggle('is-loading', active);
            if (sendBtn) {
                sendBtn.disabled = active;
            }
        };

        const setError = (message) => {
            root.classList.add('has-error');
            if (output) {
                output.textContent = message;
            }
        };

        const clearError = () => {
            root.classList.remove('has-error');
        };

        const fetchJwt = () => Ajax.call([{
            methodname: 'local_aitutor_get_jwt',
            args: {courseid: courseid, cmid: cmid || 0, conversationuuid: ''},
        }])[0];

        const streamTutorReply = async (workerurl, token, prompt, courseid, cmid) => {
            const response = await fetch(workerurl, {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    messages: [{role: 'user', content: prompt}],
                    context: {
                        courseid: courseid,
                        activityid: cmid || null,
                    },
                }),
            });

            if (!response.ok) {
                throw new Error('worker_http_' + response.status);
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const {done, value} = await reader.read();
                if (done) {
                    break;
                }
                buffer += decoder.decode(value, {stream: true});
                const lines = buffer.split('\n');
                buffer = lines.pop() || '';

                for (const line of lines) {
                    if (!line.startsWith('data: ')) {
                        continue;
                    }
                    const payload = line.slice(6).trim();
                    if (payload === '[DONE]') {
                        return;
                    }
                    try {
                        const chunk = JSON.parse(payload);
                        if (chunk.token) {
                            if (output && output.textContent === strings.loading) {
                                output.textContent = '';
                            }
                            if (output) {
                                output.textContent += chunk.token;
                            }
                        } else if (chunk.error) {
                            throw new Error(chunk.error);
                        }
                    } catch (parseErr) {
                        if (parseErr instanceof SyntaxError) {
                            if (output) {
                                output.textContent += payload;
                            }
                        } else {
                            throw parseErr;
                        }
                    }
                }
            }
        };

        sendBtn?.addEventListener('click', async () => {
            const prompt = input?.value?.trim();
            if (!prompt || !output) {
                return;
            }

            clearError();
            setLoading(true);
            output.textContent = strings.loading;

            try {
                const {token, workerurl} = await fetchJwt();
                await streamTutorReply(workerurl, token, prompt, courseid, cmid);
                if (output.textContent === strings.loading || output.textContent.trim() === '') {
                    output.textContent = strings.unavailable;
                }
            } catch (err) {
                setError(strings.error_generic);
            } finally {
                setLoading(false);
            }
        });

        return true;
    };

    /**
     * Bind sidebar once footer-injected markup (with data-courseid) is in the DOM.
     *
     * @return {void}
     */
    const initFromDom = () => {
        const poll = () => {
            const root = document.getElementById('local-aitutor-sidebar');
            if (!root) {
                window.requestAnimationFrame(poll);
                return;
            }
            const courseid = parseInt(root.dataset.courseid, 10);
            const cmid = parseInt(root.dataset.cmid, 10) || 0;
            if (!Number.isFinite(courseid) || courseid < 1) {
                window.requestAnimationFrame(poll);
                return;
            }
            bindSidebar(courseid, cmid);
        };
        poll();
    };

    return {
        init: function(courseid, cmid) {
            bindSidebar(courseid, cmid);
        },
        initFromDom: initFromDom,
    };
});
