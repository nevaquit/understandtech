// Domain readiness radar — Chart.js via Moodle core/chartjs with live AJAX refresh.
define(['jquery', 'core/chartjs', 'core/ajax'], function($, ChartJS, Ajax) {
    const BRAND = {
        navy: '#0B1F3A',
        gold: '#C9A227',
        teal: '#1A8A7D',
        success: '#2DD4A0',
        tealLight: '#22B5A5',
    };

    const POLL_INTERVAL_MS = 45000;

    /**
     * Parse radar telemetry from canvas data attribute.
     *
     * @param {string} raw JSON array from local_certmaster\api::get_user_readiness().
     * @return {Array|null}
     */
    const parseRadar = (raw) => {
        if (!raw) {
            return null;
        }
        try {
            const data = JSON.parse(raw);
            return Array.isArray(data) && data.length ? data : null;
        } catch (e) {
            return null;
        }
    };

    /**
     * Truncate long blueprint labels for narrow containers.
     *
     * @param {string} label Domain label.
     * @param {number} maxLen Maximum character count.
     * @return {string}
     */
    const truncateLabel = (label, maxLen) => {
        if (!label || label.length <= maxLen) {
            return label;
        }
        return label.slice(0, maxLen - 1) + '\u2026';
    };

    /**
     * Resolve label length from container width.
     *
     * @param {number} width Container width in pixels.
     * @return {number}
     */
    const getLabelMaxLen = (width) => {
        if (width < 280) {
            return 8;
        }
        if (width < 400) {
            return 12;
        }
        return 24;
    };

    /**
     * Build chart labels and scores from radar telemetry.
     *
     * @param {Array} radar Domain telemetry rows.
     * @param {number} containerWidth Container width in pixels.
     * @return {{labels: string[], scores: number[]}}
     */
    const buildChartData = (radar, containerWidth) => {
        const maxLen = getLabelMaxLen(containerWidth);
        return {
            labels: radar.map((d) => truncateLabel(d.label || d.domain, maxLen)),
            scores: radar.map((d) => Number(d.score) || 0),
        };
    };

    /**
     * Create Chart.js radar options shared by init and refresh.
     *
     * @return {Object}
     */
    const getChartOptions = () => ({
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            r: {
                beginAtZero: true,
                min: 0,
                max: 100,
                ticks: {stepSize: 20, backdropColor: 'transparent'},
                grid: {color: 'rgba(11, 31, 58, 0.12)'},
                angleLines: {color: 'rgba(11, 31, 58, 0.08)'},
                pointLabels: {
                    font: {size: 11, family: 'Rajdhani, Segoe UI, sans-serif'},
                    color: BRAND.navy,
                },
            },
        },
        plugins: {
            legend: {display: false},
            tooltip: {
                callbacks: {
                    label: (ctx) => `${ctx.label}: ${ctx.parsed.r}%`,
                },
            },
        },
    });

    /**
     * Update the visible readiness score and screen-reader table.
     *
     * @param {HTMLElement} canvas
     * @param {Array} radar
     * @param {number|null} overall
     */
    const updateBlockSummary = (canvas, radar, overall) => {
        const block = canvas.closest('.block-examreadiness');
        if (!block) {
            return;
        }

        if (overall !== null && overall !== undefined) {
            const scoreEl = block.querySelector('.block-examreadiness-score .display-6');
            if (scoreEl) {
                scoreEl.textContent = `${overall}%`;
            }
        }

        const tbody = block.querySelector('.block-examreadiness-chart table tbody');
        if (!tbody || !Array.isArray(radar)) {
            return;
        }

        tbody.innerHTML = radar.map((row) => {
            const label = row.label || row.domain || '';
            const score = Number(row.score) || 0;
            return `<tr><th scope="row">${label}</th><td>${score}%</td></tr>`;
        }).join('');
    };

    /**
     * Apply fresh telemetry to an existing chart instance.
     *
     * @param {HTMLCanvasElement} canvas
     * @param {Array} radar
     * @param {number|null} overall
     */
    const applyRadarData = (canvas, radar, overall) => {
        canvas.dataset.radar = JSON.stringify(radar);
        canvas._utRadarSource = radar;

        const container = canvas.closest('.block-examreadiness') || canvas.parentElement;
        const containerWidth = container ? container.clientWidth : 400;
        const {labels, scores} = buildChartData(radar, containerWidth);

        updateBlockSummary(canvas, radar, overall);

        const chart = canvas._utRadarChart;
        if (!chart) {
            return;
        }

        chart.data.labels = labels;
        chart.data.datasets[0].data = scores;
        chart.update('none');
    };

    /**
     * Fetch readiness from the web service and update the chart.
     *
     * @param {HTMLCanvasElement} canvas
     * @return {Promise<void>}
     */
    const refreshFromServer = async (canvas) => {
        const certificationid = Number(canvas.dataset.certificationid);
        if (!certificationid) {
            return;
        }

        try {
            const requests = Ajax.call([{
                methodname: 'local_certmaster_get_user_readiness',
                args: {certificationid},
            }]);
            const [response] = await requests[0];
            if (!response || !Array.isArray(response.radar) || !response.radar.length) {
                return;
            }
            applyRadarData(canvas, response.radar, response.overall_readiness);
        } catch (e) {
            // Silent fail — stale chart data is acceptable until next poll.
        }
    };

    /**
     * Start visibility-aware polling for live refresh after quiz attempts.
     *
     * @param {HTMLCanvasElement} canvas
     */
    const startLiveRefresh = (canvas) => {
        if (!canvas.dataset.certificationid || canvas._utRadarPollStarted) {
            return;
        }
        canvas._utRadarPollStarted = true;

        const poll = () => {
            if (document.hidden) {
                return;
            }
            refreshFromServer(canvas);
        };

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                poll();
            }
        });

        if (typeof IntersectionObserver !== 'undefined') {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        poll();
                    }
                });
            }, {threshold: 0.2});
            observer.observe(canvas);
            canvas._utRadarVisibilityObserver = observer;
        }

        canvas._utRadarPollTimer = window.setInterval(poll, POLL_INTERVAL_MS);
    };

    /**
     * Render a single radar chart on a canvas element.
     *
     * @param {HTMLCanvasElement} canvas
     */
    const renderChart = (canvas) => {
        const radar = parseRadar(canvas.dataset.radar);
        if (!radar) {
            return;
        }

        canvas._utRadarSource = radar;

        const container = canvas.closest('.block-examreadiness') || canvas.parentElement;
        const containerWidth = container ? container.clientWidth : 400;
        const {labels, scores} = buildChartData(radar, containerWidth);

        canvas.style.width = '100%';
        canvas.setAttribute('role', 'img');

        // eslint-disable-next-line no-new
        const chart = new ChartJS(canvas, {
            type: 'radar',
            data: {
                labels,
                datasets: [{
                    label: 'Mastery',
                    data: scores,
                    borderColor: BRAND.teal,
                    backgroundColor: 'rgba(26, 138, 125, 0.2)',
                    pointBackgroundColor: BRAND.gold,
                    pointBorderColor: BRAND.navy,
                    borderWidth: 2,
                }],
            },
            options: getChartOptions(),
        });

        canvas._utRadarChart = chart;

        if (typeof ResizeObserver !== 'undefined' && container) {
            const observer = new ResizeObserver(() => {
                const source = canvas._utRadarSource || radar;
                const width = container.clientWidth || 400;
                chart.data.labels = buildChartData(source, width).labels;
                chart.update('none');
            });
            observer.observe(container);
            canvas._utRadarObserver = observer;
        }

        startLiveRefresh(canvas);
    };

    /**
     * Initialise all readiness radar canvases matching selector.
     *
     * @param {string} selector CSS selector for canvas elements.
     */
    const init = (selector) => {
        $(selector).each(function() {
            renderChart(this);
        });
    };

    return {init, refreshFromServer};
});
