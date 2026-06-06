// Domain readiness radar — Chart.js via Moodle core/chartjs.
define(['jquery', 'core/chartjs'], function($, ChartJS) {
    const BRAND = {
        navy: '#0B1F3A',
        gold: '#C9A227',
        teal: '#1A8A7D',
    };

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
     * Render a single radar chart on a canvas element.
     *
     * @param {HTMLCanvasElement} canvas
     */
    const renderChart = (canvas) => {
        const radar = parseRadar(canvas.dataset.radar);
        if (!radar) {
            return;
        }

        const container = canvas.closest('.block-examreadiness') || canvas.parentElement;
        const containerWidth = container ? container.clientWidth : 400;
        const maxLen = getLabelMaxLen(containerWidth);
        const labels = radar.map((d) => truncateLabel(d.label || d.domain, maxLen));
        const scores = radar.map((d) => Number(d.score) || 0);

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
            options: {
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
            },
        });

        canvas._utRadarChart = chart;

        if (typeof ResizeObserver !== 'undefined' && container) {
            const observer = new ResizeObserver(() => {
                const width = container.clientWidth || 400;
                const newMax = getLabelMaxLen(width);
                chart.data.labels = radar.map((d) => truncateLabel(d.label || d.domain, newMax));
                chart.update('none');
            });
            observer.observe(container);
            canvas._utRadarObserver = observer;
        }
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

    return {init};
});
