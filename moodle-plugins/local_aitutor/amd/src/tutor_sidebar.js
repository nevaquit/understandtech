// AI Tutor sidebar — JWT fetch, multi-turn SSE, conversation history (white paper §3.1).
define(['core/ajax', 'core/str'], function(Ajax, Str) {
    const STRINGS = [
        {key: 'unavailable', component: 'local_aitutor'},
        {key: 'loading', component: 'local_aitutor'},
        {key: 'error_generic', component: 'local_aitutor'},
        {key: 'history_label', component: 'local_aitutor'},
        {key: 'new_conversation', component: 'local_aitutor'},
        {key: 'welcome_hint', component: 'local_aitutor'},
    ];

    /**
     * @param {number} courseid
     * @param {number} cmid
     * @return {boolean}
     */
    const bindSidebar = (courseid, cmid) => {
        const root = document.getElementById('local-aitutor-sidebar');
        if (!root || root.dataset.aitutorBound === '1') {
            return !!root;
        }
        root.dataset.aitutorBound = '1';

        const thread = root.querySelector('.local-aitutor-thread');
        const sendBtn = root.querySelector('.local-aitutor-send');
        const toggle = root.querySelector('.local-aitutor-toggle');
        const input = root.querySelector('.local-aitutor-input');
        const historySelect = root.querySelector('.local-aitutor-history');
        const newBtn = root.querySelector('.local-aitutor-new');

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
            history_label: 'Conversation history',
            new_conversation: 'New conversation',
            welcome_hint: 'Ask a concept question — the tutor guides you without giving quiz or lab answers.',
        };

        Str.get_strings(STRINGS).then((resolved) => {
            strings = {
                unavailable: resolved[0],
                loading: resolved[1],
                error_generic: resolved[2],
                history_label: resolved[3],
                new_conversation: resolved[4],
                welcome_hint: resolved[5],
            };
            if (historySelect && historySelect.options.length === 1) {
                historySelect.options[0].textContent = strings.history_label;
            }
            if (newBtn) {
                newBtn.textContent = strings.new_conversation;
            }
            showWelcome();
        });

        let conversationUuid = '';
        let chatMessages = [];
        let learnerContext = null;

        const setLoading = (active) => {
            root.classList.toggle('is-loading', active);
            if (sendBtn) {
                sendBtn.disabled = active;
            }
        };

        const showWelcome = () => {
            if (!thread) {
                return;
            }
            thread.innerHTML = '';
            const hint = document.createElement('p');
            hint.className = 'local-aitutor-welcome text-muted small mb-0';
            hint.textContent = strings.welcome_hint;
            thread.appendChild(hint);
        };

        const renderThread = () => {
            if (!thread) {
                return;
            }
            thread.innerHTML = '';
            if (chatMessages.length === 0) {
                showWelcome();
                return;
            }
            chatMessages.forEach((message) => {
                const bubble = document.createElement('div');
                bubble.className = 'local-aitutor-message local-aitutor-message--' + message.role;
                bubble.textContent = message.content;
                thread.appendChild(bubble);
            });
            thread.scrollTop = thread.scrollHeight;
        };

        const appendStreamingAssistant = () => {
            if (!thread) {
                return null;
            }
            const bubble = document.createElement('div');
            bubble.className = 'local-aitutor-message local-aitutor-message--assistant local-aitutor-message--streaming';
            bubble.textContent = strings.loading;
            thread.appendChild(bubble);
            thread.scrollTop = thread.scrollHeight;
            return bubble;
        };

        const populateHistory = (conversations) => {
            if (!historySelect) {
                return;
            }
            historySelect.innerHTML = '';
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = strings.history_label;
            historySelect.appendChild(placeholder);

            conversations.forEach((item) => {
                const option = document.createElement('option');
                option.value = item.conversationuuid;
                const when = new Date(item.timemodified * 1000).toLocaleString();
                option.textContent = when;
                historySelect.appendChild(option);
            });
        };

        const loadConversations = () => Ajax.call([{
            methodname: 'local_aitutor_get_conversations',
            args: {courseid: courseid, limit: 15},
        }])[0];

        const loadMessages = (uuid) => Ajax.call([{
            methodname: 'local_aitutor_get_messages',
            args: {conversationuuid: uuid, limit: 50},
        }])[0];

        const fetchJwt = (uuid) => Ajax.call([{
            methodname: 'local_aitutor_get_jwt',
            args: {courseid: courseid, cmid: cmid || 0, conversationuuid: uuid || ''},
        }])[0];

        const streamTutorReply = async (workerurl, token, uuid, learnerCtx) => {
            const assistantBubble = appendStreamingAssistant();
            let assistantText = '';

            const response = await fetch(workerurl, {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    messages: chatMessages.map((m) => ({role: m.role, content: m.content})),
                    context: {
                        courseid: courseid,
                        activityid: cmid || null,
                        conversation_id: uuid,
                    },
                    learner_context: learnerCtx,
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
                        return assistantText;
                    }
                    try {
                        const chunk = JSON.parse(payload);
                        if (chunk.token) {
                            assistantText += chunk.token;
                            if (assistantBubble) {
                                assistantBubble.textContent = assistantText;
                                assistantBubble.classList.remove('local-aitutor-message--streaming');
                            }
                            if (thread) {
                                thread.scrollTop = thread.scrollHeight;
                            }
                        } else if (chunk.error) {
                            throw new Error(chunk.error);
                        }
                    } catch (parseErr) {
                        if (parseErr instanceof SyntaxError) {
                            assistantText += payload;
                            if (assistantBubble) {
                                assistantBubble.textContent = assistantText;
                            }
                        } else {
                            throw parseErr;
                        }
                    }
                }
            }

            return assistantText;
        };

        const startNewConversation = () => {
            conversationUuid = '';
            chatMessages = [];
            if (historySelect) {
                historySelect.value = '';
            }
            showWelcome();
        };

        historySelect?.addEventListener('change', async () => {
            const uuid = historySelect.value;
            if (!uuid) {
                startNewConversation();
                return;
            }
            conversationUuid = uuid;
            try {
                const {messages} = await loadMessages(uuid);
                chatMessages = messages.map((m) => ({role: m.role, content: m.content}));
                renderThread();
            } catch (err) {
                startNewConversation();
            }
        });

        newBtn?.addEventListener('click', () => {
            startNewConversation();
        });

        loadConversations().then(({conversations}) => {
            populateHistory(conversations || []);
        }).catch(() => {
            // History is optional — sidebar still works for new chats.
        });

        const sendMessage = async () => {
            const prompt = input?.value?.trim();
            if (!prompt || !thread) {
                return;
            }

            root.classList.remove('has-error');
            chatMessages.push({role: 'user', content: prompt});
            renderThread();
            if (input) {
                input.value = '';
            }
            setLoading(true);

            try {
                const jwtResponse = await fetchJwt(conversationUuid);
                conversationUuid = jwtResponse.conversationuuid || conversationUuid;
                if (jwtResponse.learnercontextjson) {
                    try {
                        learnerContext = JSON.parse(jwtResponse.learnercontextjson);
                    } catch (e) {
                        learnerContext = null;
                    }
                }

                const assistantText = await streamTutorReply(
                    jwtResponse.workerurl,
                    jwtResponse.token,
                    conversationUuid,
                    learnerContext,
                );

                if (assistantText && assistantText.trim() !== '') {
                    chatMessages.push({role: 'assistant', content: assistantText});
                    renderThread();
                } else if (assistantText === '') {
                    root.classList.add('has-error');
                    const errNode = document.createElement('p');
                    errNode.className = 'local-aitutor-error small text-danger mb-0';
                    errNode.textContent = strings.unavailable;
                    thread.appendChild(errNode);
                }

                loadConversations().then(({conversations}) => {
                    populateHistory(conversations || []);
                    if (historySelect && conversationUuid) {
                        historySelect.value = conversationUuid;
                    }
                }).catch(() => undefined);
            } catch (err) {
                const networkFailure = err instanceof TypeError
                    || String(err?.message ?? '').startsWith('worker_http_');
                root.classList.add('has-error');
                const errNode = document.createElement('p');
                errNode.className = 'local-aitutor-error small text-danger mb-0';
                errNode.textContent = networkFailure ? strings.unavailable : strings.error_generic;
                thread.appendChild(errNode);
            } finally {
                setLoading(false);
            }
        };

        sendBtn?.addEventListener('click', sendMessage);
        input?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        });

        return true;
    };

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
