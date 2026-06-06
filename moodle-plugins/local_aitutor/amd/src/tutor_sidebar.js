// AI Tutor sidebar — JWT fetch + SSE streaming to Cloudflare Worker.
define(['core/ajax', 'core/notification', 'core/str'], function(Ajax, Notification, Str) {
    return {
        init: function(courseid, cmid) {
            const bind = () => {
                const root = document.getElementById('local-aitutor-sidebar');
                if (!root || root.dataset.aitutorBound === '1') {
                    return !!root;
                }
                root.dataset.aitutorBound = '1';

                const output = root.querySelector('.local-aitutor-output');
                const sendBtn = root.querySelector('.local-aitutor-send');
                const toggle = root.querySelector('.local-aitutor-toggle');
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

                const fetchJwt = () => Ajax.call([{
                    methodname: 'local_aitutor_get_jwt',
                    args: {courseid: courseid, cmid: cmid || 0, conversationuuid: ''},
                }])[0];

                const showUnavailable = () => Str.get_string('unavailable', 'local_aitutor').then((text) => {
                    if (output) {
                        output.textContent = text;
                    }
                });

                const streamTutorReply = async (workerurl, token, prompt) => {
                    const response = await fetch(workerurl, {
                        method: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + token,
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            messages: [{role: 'user', content: prompt}],
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
                                    output.textContent += chunk.token;
                                } else if (chunk.error) {
                                    throw new Error(chunk.error);
                                }
                            } catch (parseErr) {
                                if (parseErr instanceof SyntaxError) {
                                    output.textContent += payload;
                                } else {
                                    throw parseErr;
                                }
                            }
                        }
                    }
                };

                sendBtn?.addEventListener('click', async () => {
                    const prompt = root.querySelector('.local-aitutor-input')?.value?.trim();
                    if (!prompt || !output) {
                        return;
                    }
                    output.textContent = '';
                    if (sendBtn) {
                        sendBtn.disabled = true;
                    }
                    try {
                        const [{token, workerurl}] = await fetchJwt();
                        await streamTutorReply(workerurl, token, prompt);
                    } catch (err) {
                        await showUnavailable();
                        Notification.exception(err);
                    } finally {
                        if (sendBtn) {
                            sendBtn.disabled = false;
                        }
                    }
                });
                return true;
            };

            if (!bind()) {
                window.requestAnimationFrame(() => {
                    bind();
                });
            }
        },
    };
});
