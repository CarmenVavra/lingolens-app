/**
 * Split-screen correction mode: select marks, filter error types, switch
 * between marked and corrected text, mark corrections as understood.
 */
const MOBILE_QUERY = '(max-width: 759.98px)';

export default (config) => ({
    corrections: config.corrections,
    palette: config.palette,
    updateUrl: config.updateUrl,
    byId: {},
    hidden: {},
    understood: {},
    selectedId: config.initial.selected,
    showCorrected: false,
    isMobile: false,

    init() {
        this.corrections.forEach((correction) => {
            this.byId[correction.id] = correction;
            this.understood[correction.id] = correction.understood;
        });
        config.initial.hidden.forEach((type) => {
            this.hidden[type] = true;
        });

        const media = window.matchMedia(MOBILE_QUERY);
        this.isMobile = media.matches;
        media.addEventListener('change', (event) => {
            this.isMobile = event.matches;
        });

        if (this.selectedId) {
            this.$nextTick(() => this.reveal(this.selectedId));
        }
    },

    get visible() {
        return this.corrections.filter((correction) => !this.hidden[correction.type]).map((correction) => correction.id);
    },

    get selected() {
        return this.selectedId ? this.byId[this.selectedId] : null;
    },

    get selectedMeta() {
        return this.selected ? this.palette[this.selected.type] : {};
    },

    get position() {
        return this.visible.indexOf(this.selectedId) + 1;
    },

    get doneCount() {
        return this.corrections.filter((correction) => this.understood[correction.id]).length;
    },

    get donePercent() {
        return this.corrections.length ? Math.round((this.doneCount / this.corrections.length) * 100) + '%' : '0%';
    },

    get selectedUnderstood() {
        return this.selected ? this.understood[this.selected.id] : false;
    },

    isHidden(type) {
        return Boolean(this.hidden[type]);
    },

    toggleType(type) {
        this.hidden[type] = !this.hidden[type];

        if (this.hidden[type] && this.selected && this.selected.type === type) {
            this.selectedId = null;
        }
    },

    markText(id) {
        const correction = this.byId[id];

        return this.showCorrected ? correction.correct : correction.wrong;
    },

    markState(id) {
        const correction = this.byId[id];

        return {
            'is-off': this.isHidden(correction.type),
            'is-selected': this.selectedId === id,
            'is-understood': this.understood[id] && this.selectedId !== id,
        };
    },

    markLabel(id) {
        const correction = this.byId[id];

        return this.palette[correction.type].label + ': ' + this.markText(id);
    },

    select(id) {
        if (this.isHidden(this.byId[id].type)) {
            return;
        }

        this.selectedId = id;
        this.$nextTick(() => {
            if (this.isMobile) {
                this.reveal(id);
            }
            this.focusPanel();
        });
    },

    selectFirst() {
        this.selectedId = this.visible[0] ?? null;
        this.$nextTick(() => this.reveal(this.selectedId));
    },

    step(direction) {
        const visible = this.visible;

        if (!visible.length) {
            return;
        }

        const index = visible.indexOf(this.selectedId);
        this.selectedId = visible[(((index + direction) % visible.length) + visible.length) % visible.length];
        this.$nextTick(() => this.reveal(this.selectedId));
    },

    close() {
        const id = this.selectedId;
        this.selectedId = null;

        const mark = id ? document.querySelector(`[data-correction="${id}"]`) : null;
        if (mark) {
            mark.focus({ preventScroll: true });
        }
    },

    markDone() {
        const id = this.selectedId;
        const visible = this.visible;
        const index = visible.indexOf(id);

        if (!this.understood[id]) {
            this.understood[id] = true;
            this.persist(id, true);
        }

        const next = visible.slice(index + 1).concat(visible.slice(0, index)).find((other) => !this.understood[other]);
        this.selectedId = next ?? null;

        if (next) {
            this.$nextTick(() => this.reveal(next));
        }
    },

    async persist(id, value) {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const response = await fetch(this.updateUrl.replace('__ID__', id), {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ understood: value }),
            });

            if (!response.ok) {
                throw new Error('Request failed');
            }
        } catch {
            this.understood[id] = !value;
        }
    },

    /** Scroll a mark into view (above the bottom sheet on phones). */
    reveal(id) {
        const mark = id ? document.querySelector(`[data-correction="${id}"]`) : null;

        if (!mark) {
            return;
        }

        const rect = mark.getBoundingClientRect();
        const topLimit = 72;
        const bottomLimit = this.isMobile ? window.innerHeight * 0.25 : window.innerHeight - 40;

        if (rect.top < topLimit || rect.bottom > bottomLimit) {
            window.scrollTo({ top: window.scrollY + rect.top - topLimit - 16, behavior: 'smooth' });
        }
    },

    focusPanel() {
        if (this.isMobile && this.$refs.panel) {
            this.$refs.panel.focus({ preventScroll: true });
        }
    },
});
