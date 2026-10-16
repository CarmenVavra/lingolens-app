/**
 * Setup & upload form ("Weichen-Formular"): school type → department →
 * text type, plus the text itself as photo, PDF, Word or TXT file.
 * Switching the school type resets the text type.
 */
const KINDS = {
    photo: ['jpg', 'jpeg', 'png', 'heic', 'heif'],
    pdf: ['pdf'],
    word: ['docx'],
    text: ['txt'],
};

const KIND_LABELS = { photo: 'Foto', pdf: 'PDF', word: 'Word', text: 'TXT' };

function extensionOf(name) {
    const match = /\.([a-z0-9]+)$/i.exec(name || '');

    return match ? match[1].toLowerCase() : '';
}

function kindOf(extension) {
    return Object.keys(KINDS).find((kind) => KINDS[kind].includes(extension)) ?? null;
}

function formatSize(bytes) {
    if (bytes < 1024 * 1024) {
        return Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    return (bytes / (1024 * 1024)).toFixed(1).replace('.', ',') + ' MB';
}

export default (config) => ({
    school: config.school,
    department: config.department,
    textType: config.textType,
    textTypes: config.textTypes,
    chemistry: config.chemistry,
    extensions: config.extensions,
    maxBytes: config.maxBytes,
    previewUrl: null,
    previewBroken: false,
    fileName: '',
    fileSize: 0,
    kind: null,
    useSample: false,
    dragging: false,
    error: '',
    submitting: false,

    get types() {
        return this.textTypes[this.school] ?? [];
    },

    get isBhs() {
        return this.school === 'BHS';
    },

    get isChemistry() {
        return this.isBhs && this.department === this.chemistry;
    },

    get hasFile() {
        return this.kind !== null || this.useSample;
    },

    get kindLabel() {
        return KIND_LABELS[this.kind] ?? '';
    },

    get readyLabel() {
        if (this.useSample) {
            return 'Beispieltext bereit';
        }

        return this.kind === 'photo' ? 'Foto bereit' : this.kindLabel + ' bereit';
    },

    get fileMeta() {
        if (this.useSample || !this.fileSize) {
            return '';
        }

        const hint = {
            photo: 'wird per Handschrift-Erkennung gelesen',
            pdf: 'getippt: direkt ausgelesen · Scan: Handschrift-Erkennung',
            word: 'Text wird direkt übernommen',
            text: 'Text wird direkt übernommen',
        }[this.kind];

        return this.kindLabel + ' · ' + formatSize(this.fileSize) + ' · ' + hint;
    },

    pickSchool(school) {
        if (this.school === school) {
            return;
        }

        this.school = school;
        this.textType = this.types[0];
    },

    onFile(event) {
        const file = event.target.files && event.target.files[0];

        if (file) {
            this.accept(file);
        }
    },

    onCamera(event) {
        const file = event.target.files && event.target.files[0];

        if (file) {
            this.handOver(file);
        }

        event.target.value = '';
    },

    onDrop(event) {
        this.dragging = false;
        const file = event.dataTransfer && event.dataTransfer.files[0];

        if (file) {
            this.handOver(file);
        }
    },

    /** Put a file from the camera or drag & drop into the submitted input. */
    handOver(file) {
        try {
            const transfer = new DataTransfer();
            transfer.items.add(file);
            this.$refs.file.files = transfer.files;
        } catch {
            this.error = 'Dein Browser unterstützt das nicht. Bitte wähle die Datei über „Datei hochladen“.';

            return;
        }

        this.accept(file);
    },

    accept(file) {
        const extension = extensionOf(file.name);
        const kind = kindOf(extension);

        this.reset();

        if (!kind || !this.extensions.includes(extension)) {
            this.error = 'Dieses Format wird nicht unterstützt. Erlaubt sind PDF, Fotos (JPG, PNG, HEIC), Word (.docx) und TXT.';
            this.$refs.file.value = '';

            return;
        }

        if (file.size > this.maxBytes) {
            this.error = 'Die Datei ist größer als 10 MB. Bitte verkleinere sie oder speichere sie als PDF.';
            this.$refs.file.value = '';

            return;
        }

        this.kind = kind;
        this.fileName = file.name;
        this.fileSize = file.size;

        if (kind === 'photo') {
            this.previewUrl = URL.createObjectURL(file);
        }
    },

    useDemo() {
        this.clear();
        this.useSample = true;
        this.fileName = 'beispieltext-lab-report.jpg';
    },

    clear() {
        this.reset();

        if (this.$refs.file) {
            this.$refs.file.value = '';
        }
    },

    reset() {
        if (this.previewUrl) {
            URL.revokeObjectURL(this.previewUrl);
        }

        this.previewUrl = null;
        this.previewBroken = false;
        this.fileName = '';
        this.fileSize = 0;
        this.kind = null;
        this.useSample = false;
        this.error = '';
    },

    submit(event) {
        if (!this.hasFile || this.submitting) {
            event.preventDefault();

            return;
        }

        this.submitting = true;
    },
});
