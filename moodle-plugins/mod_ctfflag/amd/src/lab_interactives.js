// This file is part of Moodle - http://moodle.org/
/**
 * Per-lab interactive widgets (calculators, triage tools, sorters).
 * No flag answers are hardcoded — learners derive values from interactions.
 *
 * @module mod_ctfflag/lab_interactives
 */
define([], function() {
    'use strict';

    /**
     * @param {string} text
     * @return {Promise<string>}
     */
    const sha256Prefix8 = async(text) => {
        const digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(text));
        const hex = Array.from(new Uint8Array(digest))
            .map((b) => b.toString(16).padStart(2, '0'))
            .join('');
        return hex.slice(0, 8);
    };

    /**
     * @param {HTMLElement} root
     * @param {string} message
     * @param {boolean} ok
     */
    const setFeedback = (root, message, ok) => {
        const el = root.querySelector('[data-ut-feedback]');
        if (!el) {
            return;
        }
        el.textContent = message;
        el.className = 'ut-lab-widget-feedback ' + (ok ? 'ut-lab-widget-feedback--ok' : 'ut-lab-widget-feedback--err');
    };

    /**
     * @param {HTMLElement} root
     */
    const initSiemTriage = (root) => {
        const iocButtons = root.querySelectorAll('[data-ioc-type]');
        iocButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                iocButtons.forEach((b) => b.classList.remove('is-selected', 'is-correct', 'is-wrong'));
                btn.classList.add('is-selected');
                const correct = btn.getAttribute('data-ioc-type') === 'hash';
                btn.classList.add(correct ? 'is-correct' : 'is-wrong');
                setFeedback(root.querySelector('[data-widget="ioc"]'),
                    correct ? 'Correct — file hash is the best perimeter block IOC for this alert.' :
                        'Not the best choice for perimeter blocking here. Try again.',
                    correct);
            });
        });

        const hashEl = root.querySelector('[data-source-hash]');
        const extractBtn = root.querySelector('[data-action="extract-hash-prefix"]');
        const outEl = root.querySelector('[data-hash-prefix-out]');
        if (extractBtn && hashEl && outEl) {
            extractBtn.addEventListener('click', () => {
                const hash = (hashEl.textContent || '').trim();
                if (!/^[a-fA-F0-9]{64}$/.test(hash)) {
                    setFeedback(root.querySelector('[data-widget="hash"]'), 'Invalid SHA-256 hash in scenario.', false);
                    return;
                }
                outEl.textContent = hash.slice(0, 8);
                setFeedback(root.querySelector('[data-widget="hash"]'),
                    'Prefix extracted. Wrap as UT{' + hash.slice(0, 8) + '} in the flag panel →', true);
            });
        }
    };

    /**
     * @param {HTMLElement} root
     */
    const initPhishing = (root) => {
        const lines = root.querySelectorAll('[data-header-line]');
        const required = ['return-path', 'auth-results', 'reply-to', 'campaign-tag'];
        const widget = root.querySelector('[data-widget="headers"]');

        const checkComplete = () => {
            const flagged = required.filter((key) => {
                const line = root.querySelector('[data-header-line="' + key + '"]');
                return line && line.classList.contains('is-flagged');
            });
            if (flagged.length < required.length) {
                return;
            }
            const tagLine = root.querySelector('[data-header-line="campaign-tag"]');
            const tag = tagLine ? tagLine.getAttribute('data-value') : '';
            const out = root.querySelector('[data-campaign-out]');
            if (out && tag) {
                out.textContent = 'UT{' + tag + '}';
            }
            setFeedback(widget,
                'Suspicious headers identified. Submit the derived flag shown above.', true);
        };

        lines.forEach((line) => {
            line.addEventListener('click', () => {
                line.classList.toggle('is-flagged');
                const key = line.getAttribute('data-header-line');
                if (line.classList.contains('is-flagged') && !required.includes(key)) {
                    setFeedback(widget, 'That line is less relevant for spoofing indicators.', false);
                }
                checkComplete();
            });
        });
    };

    /**
     * @param {HTMLElement} root
     */
    const initFirewall = (root) => {
        const auditBtn = root.querySelector('[data-action="audit-rule"]');
        if (!auditBtn) {
            return;
        }
        auditBtn.addEventListener('click', () => {
            const src = root.querySelector('[data-field="source"]');
            const violations = root.querySelectorAll('[data-violation]');
            violations.forEach((v) => v.classList.remove('is-visible'));
            if (src && src.textContent.trim().toUpperCase() === 'ANY') {
                root.querySelector('[data-violation="source-any"]')?.classList.add('is-visible');
            }
            const cmdb = root.querySelector('[data-field="cmdb-id"]');
            const out = root.querySelector('[data-cmdb-out]');
            if (cmdb && out) {
                out.textContent = 'UT{' + cmdb.textContent.trim() + '}';
            }
            setFeedback(root.querySelector('[data-widget="audit"]'),
                'Policy violations highlighted. Submit the CMDB rule set ID as your flag.', true);
        });
    };

    /**
     * @param {number} prefix
     * @return {{hosts: number, broadcast: string}}
     */
    const cidrInfo = (prefix) => {
        const hosts = Math.max(0, Math.pow(2, 32 - prefix) - 2);
        return { hosts, prefix };
    };

    /**
     * @param {HTMLElement} root
     */
    const initIpv4Subnet = (root) => {
        const prefixInput = root.querySelector('[data-input="prefix"]');
        const calcBtn = root.querySelector('[data-action="calc-subnet"]');
        const hostsOut = root.querySelector('[data-hosts-out]');
        const broadcastInput = root.querySelector('[data-input="broadcast"]');
        const hashBtn = root.querySelector('[data-action="hash-broadcast"]');
        const hashOut = root.querySelector('[data-hash-out]');

        if (calcBtn && prefixInput && hostsOut) {
            calcBtn.addEventListener('click', () => {
                const p = parseInt(prefixInput.value, 10);
                if (Number.isNaN(p) || p < 1 || p > 30) {
                    setFeedback(root.querySelector('[data-widget="subnet"]'), 'Enter a valid prefix (/1–/30).', false);
                    return;
                }
                const { hosts } = cidrInfo(p);
                hostsOut.textContent = String(hosts);
                const ok = hosts >= 50;
                setFeedback(root.querySelector('[data-widget="subnet"]'),
                    ok ? '/' + p + ' provides ' + hosts + ' usable hosts — meets the 50-host requirement.' :
                        '/' + p + ' only provides ' + hosts + ' usable hosts — too small.',
                    ok);
            });
        }

        if (hashBtn && broadcastInput && hashOut) {
            hashBtn.addEventListener('click', async() => {
                const bc = broadcastInput.value.trim();
                if (!bc) {
                    setFeedback(root.querySelector('[data-widget="hash"]'), 'Enter the broadcast address first.', false);
                    return;
                }
                const prefix = await sha256Prefix8(bc);
                hashOut.textContent = 'UT{' + prefix + '}';
                setFeedback(root.querySelector('[data-widget="hash"]'),
                    'Flag derived from SHA-256 prefix. Copy to the flag panel if it matches your analysis.', true);
            });
        }
    };

    /**
     * @param {HTMLElement} root
     */
    const initVlan = (root) => {
        root.querySelectorAll('[data-layer-choice]').forEach((btn) => {
            btn.addEventListener('click', () => {
                root.querySelectorAll('[data-layer-choice]').forEach((b) => b.classList.remove('is-selected', 'is-correct', 'is-wrong'));
                btn.classList.add('is-selected');
                const ok = btn.getAttribute('data-layer-choice') === 'l3';
                btn.classList.add(ok ? 'is-correct' : 'is-wrong');
                setFeedback(root.querySelector('[data-widget="layer"]'),
                    ok ? 'Correct — DHCP works, so L2 trunking is fine; gateway failure points to L3/SVI.' :
                        'Hosts receive DHCP — reconsider which layer is broken.', ok);
            });
        });

        const hashBtn = root.querySelector('[data-action="hash-diagnosis"]');
        const phraseInput = root.querySelector('[data-input="diagnosis-phrase"]');
        const hashOut = root.querySelector('[data-hash-out]');
        if (hashBtn && phraseInput && hashOut) {
            hashBtn.addEventListener('click', async() => {
                const phrase = phraseInput.value.trim();
                if (!phrase) {
                    setFeedback(root.querySelector('[data-widget="hash"]'), 'Enter your diagnosis keyword phrase.', false);
                    return;
                }
                const prefix = await sha256Prefix8(phrase);
                hashOut.textContent = 'UT{' + prefix + '}';
                setFeedback(root.querySelector('[data-widget="hash"]'),
                    'Computed flag candidate — verify it matches the lab instructions.', true);
            });
        }
    };

    /**
     * @param {HTMLElement} root
     */
    const initAcl = (root) => {
        root.querySelectorAll('[data-acl-rule]').forEach((row) => {
            row.addEventListener('click', () => {
                row.classList.toggle('is-marked');
                const num = row.getAttribute('data-acl-rule');
                const isBad = num === '30';
                if (row.classList.contains('is-marked')) {
                    setFeedback(root.querySelector('[data-widget="acl"]'),
                        isBad ? 'Rule 30 permit ip any any is overly permissive — correct find.' :
                            'Rule ' + num + ' is acceptable in this audit context.',
                        isBad);
                }
            });
        });

        const hashBtn = root.querySelector('[data-action="hash-rule-id"]');
        const phraseInput = root.querySelector('[data-input="rule-phrase"]');
        const hashOut = root.querySelector('[data-hash-out]');
        if (hashBtn && phraseInput && hashOut) {
            hashBtn.addEventListener('click', async() => {
                const phrase = phraseInput.value.trim();
                if (!phrase) {
                    setFeedback(root.querySelector('[data-widget="hash"]'), 'Enter the rule identifier phrase.', false);
                    return;
                }
                hashOut.textContent = 'UT{' + await sha256Prefix8(phrase) + '}';
                setFeedback(root.querySelector('[data-widget="hash"]'), 'Flag candidate computed.', true);
            });
        }
    };

    /**
     * @param {HTMLElement} root
     */
    const initRamStorage = (root) => {
        root.querySelectorAll('[data-upgrade-choice]').forEach((btn) => {
            btn.addEventListener('click', () => {
                root.querySelectorAll('[data-upgrade-choice]').forEach((b) => b.classList.remove('is-selected', 'is-correct', 'is-wrong'));
                btn.classList.add('is-selected');
                const ok = btn.getAttribute('data-upgrade-choice') === 'ram';
                btn.classList.add(ok ? 'is-correct' : 'is-wrong');
                setFeedback(root.querySelector('[data-widget="upgrade"]'),
                    ok ? 'Correct — VM workloads need RAM before storage bandwidth for this profile.' :
                        'For VM study workloads, memory is usually the bottleneck first.', ok);
            });
        });

        const hashBtn = root.querySelector('[data-action="hash-phrase"]');
        const phraseInput = root.querySelector('[data-input="config-phrase"]');
        const hashOut = root.querySelector('[data-hash-out]');
        if (hashBtn && phraseInput && hashOut) {
            hashBtn.addEventListener('click', async() => {
                const phrase = phraseInput.value.trim();
                if (!phrase) {
                    setFeedback(root.querySelector('[data-widget="hash"]'), 'Enter the configuration phrase from the lab.', false);
                    return;
                }
                hashOut.textContent = 'UT{' + await sha256Prefix8(phrase) + '}';
                setFeedback(root.querySelector('[data-widget="hash"]'), 'Flag candidate ready.', true);
            });
        }
    };

    /**
     * @param {HTMLElement} root
     */
    const initBootTrouble = (root) => {
        const list = root.querySelector('[data-sortable="boot-steps"]');
        if (!list) {
            return;
        }
        let dragEl = null;
        list.querySelectorAll('[data-step]').forEach((item) => {
            item.setAttribute('draggable', 'true');
            item.addEventListener('dragstart', () => {
                dragEl = item;
                item.classList.add('is-dragging');
            });
            item.addEventListener('dragend', () => {
                item.classList.remove('is-dragging');
                dragEl = null;
            });
            item.addEventListener('dragover', (e) => {
                e.preventDefault();
            });
            item.addEventListener('drop', (e) => {
                e.preventDefault();
                if (dragEl && dragEl !== item) {
                    list.insertBefore(dragEl, item);
                }
            });
        });

        const checkBtn = root.querySelector('[data-action="check-order"]');
        if (checkBtn) {
            checkBtn.addEventListener('click', () => {
                const order = Array.from(list.querySelectorAll('[data-step]')).map((el) => el.getAttribute('data-step'));
                const expected = ['safe-mode', 'startup-repair', 'system-restore'];
                const ok = order.join(',') === expected.join(',');
                setFeedback(root.querySelector('[data-widget="sort"]'),
                    ok ? 'Recovery order matches least-invasive-first methodology.' :
                        'Not quite — least invasive first: Safe Mode → Startup Repair → System Restore.',
                    ok);
            });
        }

        const hashBtn = root.querySelector('[data-action="hash-phrase"]');
        const phraseInput = root.querySelector('[data-input="recovery-phrase"]');
        const hashOut = root.querySelector('[data-hash-out]');
        if (hashBtn && phraseInput && hashOut) {
            hashBtn.addEventListener('click', async() => {
                const phrase = phraseInput.value.trim();
                if (!phrase) {
                    return;
                }
                hashOut.textContent = 'UT{' + await sha256Prefix8(phrase) + '}';
                setFeedback(root.querySelector('[data-widget="hash"]'), 'Flag candidate ready.', true);
            });
        }
    };

    /**
     * @param {HTMLElement} root
     */
    const initNetworkConnectivity = (root) => {
        root.querySelectorAll('[data-layer-guess]').forEach((btn) => {
            btn.addEventListener('click', () => {
                root.querySelectorAll('[data-layer-guess]').forEach((b) => b.classList.remove('is-selected', 'is-correct', 'is-wrong'));
                btn.classList.add('is-selected');
                const ok = btn.getAttribute('data-layer-guess') === 'dhcp';
                btn.classList.add(ok ? 'is-correct' : 'is-wrong');
                setFeedback(root.querySelector('[data-widget="layer"]'),
                    ok ? 'Correct — APIPA (169.254.x.x) indicates DHCP failure.' :
                        'APIPA addresses suggest a different layer — try again.', ok);
            });
        });

        const cmdBtn = root.querySelector('[data-action="run-cmd"]');
        const cmdSelect = root.querySelector('[data-input="cmd"]');
        const cmdOut = root.querySelector('[data-cmd-out]');
        if (cmdBtn && cmdSelect && cmdOut) {
            cmdBtn.addEventListener('click', () => {
                const cmd = cmdSelect.value;
                const outputs = {
                    'ipconfig': 'Wireless LAN adapter Wi-Fi:\n   IPv4 Address. . . : 169.254.44.12\n   Subnet Mask . . . : 255.255.0.0',
                    'ipconfig-release': 'Released IP for Wi-Fi adapter.',
                    'ipconfig-renew': 'DHCP request sent — awaiting lease from server.',
                };
                cmdOut.textContent = outputs[cmd] || 'Command not recognized.';
            });
        }

        const hashBtn = root.querySelector('[data-action="hash-phrase"]');
        const phraseInput = root.querySelector('[data-input="fix-phrase"]');
        const hashOut = root.querySelector('[data-hash-out]');
        if (hashBtn && phraseInput && hashOut) {
            hashBtn.addEventListener('click', async() => {
                const phrase = phraseInput.value.trim();
                if (!phrase) {
                    return;
                }
                hashOut.textContent = 'UT{' + await sha256Prefix8(phrase) + '}';
                setFeedback(root.querySelector('[data-widget="hash"]'), 'Flag candidate ready.', true);
            });
        }
    };

    const REGISTRY = {
        'siem-triage': initSiemTriage,
        'phishing-analysis': initPhishing,
        'firewall-review': initFirewall,
        'ipv4-subnet': initIpv4Subnet,
        'vlan-troubleshoot': initVlan,
        'acl-review': initAcl,
        'ram-storage': initRamStorage,
        'boot-troubleshoot': initBootTrouble,
        'network-connectivity': initNetworkConnectivity,
    };

    /**
     * @return {void}
     */
    const init = () => {
        document.querySelectorAll('.ut-lab-content[data-ut-lab]').forEach((root) => {
            const type = root.getAttribute('data-ut-lab');
            if (type && REGISTRY[type]) {
                REGISTRY[type](root);
            }
        });
    };

    return { init: init };
});
