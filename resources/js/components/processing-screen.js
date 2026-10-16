/**
 * Processing screen: polls scans.status until the queue job is done, then
 * forwards to the correction mode.
 */
const TOTAL_STEPS = 4;
const POLL_INTERVAL = 1500;

export default (config) => ({
    status: config.status,
    step: config.step,
    statusUrl: config.statusUrl,
    readingTitle: config.readingTitle,
    timer: null,

    init() {
        if (this.status === 'processing') {
            this.timer = setTimeout(() => this.poll(), POLL_INTERVAL);
        }
    },

    destroy() {
        clearTimeout(this.timer);
    },

    get failed() {
        return this.status === 'failed';
    },

    get done() {
        return this.status === 'completed' || this.step >= TOTAL_STEPS;
    },

    get percent() {
        return Math.round((Math.min(this.step, TOTAL_STEPS) / TOTAL_STEPS) * 100) + '%';
    },

    get title() {
        if (this.done) {
            return 'Fertig! Dein Feedback ist da.';
        }

        return this.step >= 2 ? 'Die KI korrigiert deinen Text …' : this.readingTitle;
    },

    stepState(index) {
        if (this.failed && index === this.step) {
            return 'failed';
        }

        if (index < this.step || this.status === 'completed') {
            return 'done';
        }

        return index === this.step ? 'active' : 'pending';
    },

    stepMark(index) {
        const state = this.stepState(index);

        if (state === 'failed') {
            return '!';
        }

        return state === 'done' ? '✓' : String(index + 1);
    },

    async poll() {
        try {
            const response = await fetch(this.statusUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (response.ok) {
                const data = await response.json();

                this.step = data.progress_step;
                this.status = data.status;

                if (data.status === 'completed') {
                    this.step = TOTAL_STEPS;
                    setTimeout(() => window.location.assign(data.redirect_url), 800);

                    return;
                }

                if (data.status === 'failed') {
                    return;
                }
            }
        } catch {
            // Network hiccup: simply try again.
        }

        this.timer = setTimeout(() => this.poll(), POLL_INTERVAL);
    },
});
