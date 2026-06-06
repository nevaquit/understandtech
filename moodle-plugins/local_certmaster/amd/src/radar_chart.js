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
     * Render a single radar chart on a canvas element.
     *
     * @param {HTMLCanvasElement} canvas
     */
    const renderChart = (canvas) => {
        const radar = parseRadar(canvas.dataset.radar);
        if (!radar) {
            return;
        }

        const labels = radar.map((d) => d.label || d.domain);
        const scores = radar.map((d) => Number(d.score) || 0);

        canvas.style.width = '100%';
        canvas.setAttribute('role', 'img');

        // eslint-disable-next-line no-new
        new ChartJS(canvas, {
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
                        ticks: {stepSize: 20},
                        grid: {color: 'rgba(11, 31, 58, 0.12)'},
                        pointLabels: {
                            font: {size: 11},
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
