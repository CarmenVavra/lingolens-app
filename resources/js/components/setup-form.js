/**
 * Setup & upload form ("Weichen-Formular"): school type → department →
 * text type, plus the text itself as photo, PDF, Word or TXT file and an
 * optional task prompt (photo, document or typed).
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

/**
 * Copy a picked file into memory. iOS Safari deletes the temporary camera file
 * as soon as the camera input changes again; the form would then be sent with
 * an empty body ("Page Expired"). An in-memory copy stays readable.
 */
async function keepInMemory(file) {
    try {
        const buffer = await file.arrayBuffer();

        return new File([buffer], file.name, { type: file.type || 'application/octet-stream', lastModified: file.lastModified });
    } catch {
        return null;
    }
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
    photoFormats: config.photoFormats,
    maxBytes: config.maxBytes,
    tokenUrl: config.tokenUrl,
    previewUrl: null,
    previewBroken: false,
    fileName: '',
    fileSize: 0,
    kind: null,
    useSample: false,
    dragging: false,
    error: '',
    submitting: false,
    taskName: '',
    taskError: '',
    textFile: null,
    taskFile: null,
    serverErrors: [],

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

    async accept(file) {
        const extension = extensionOf(file.name);
        const kind = kindOf(extension);

        this.reset();

        if (!kind || !this.extensions.includes(extension)) {
            this.error = `Dieses Format wird nicht unterstützt. Erlaubt sind PDF, Fotos (${this.photoFormats}), Word (.docx) und TXT.`;
            this.$refs.file.value = '';

            return;
        }

        if (file.size > this.maxBytes) {
            this.error = 'Die Datei ist größer als 10 MB. Bitte verkleinere sie oder speichere sie als PDF.';
            this.$refs.file.value = '';

            return;
        }

        // Copy first: the button only becomes active once the file is safe in memory.
        this.textFile = await keepInMemory(file);
        this.kind = kind;
        this.fileName = file.name;
        this.fileSize = file.size;

        if (kind === 'photo') {
            this.previewUrl = URL.createObjectURL(this.textFile ?? file);
        }
    },

    /** Optional task prompt (photo or document of the worksheet). */
    onTaskFile(event) {
        const file = event.target.files && event.target.files[0];

        if (file) {
            this.acceptTask(file);
        }
    },

    onTaskCamera(event) {
        const file = event.target.files && event.target.files[0];

        if (!file) {
            return;
        }

        try {
            const transfer = new DataTransfer();
            transfer.items.add(file);
            this.$refs.taskFile.files = transfer.files;
        } catch {
            this.taskError = 'Dein Browser unterstützt das nicht. Bitte wähle die Datei über „Aufgabe hochladen“.';

            return;
        }

        this.acceptTask(file);
    },

    async acceptTask(file) {
        const extension = extensionOf(file.name);
        this.taskError = '';

        if (!kindOf(extension) || !this.extensions.includes(extension)) {
            this.taskError = `Dieses Format wird nicht unterstützt. Erlaubt sind PDF, Fotos (${this.photoFormats}), Word (.docx) und TXT.`;
            this.clearTask(false);

            return;
        }

        if (file.size > this.maxBytes) {
            this.taskError = 'Die Aufgabenstellung ist größer als 10 MB.';
            this.clearTask(false);

            return;
        }

        this.taskName = file.name + ' · ' + formatSize(file.size);
        this.taskFile = await keepInMemory(file);
    },

    clearTask(resetError = true) {
        this.taskName = '';
        this.taskFile = null;

        if (resetError) {
            this.taskError = '';
        }

        if (this.$refs.taskFile) {
            this.$refs.taskFile.value = '';
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
        this.textFile = null;
    },

    /**
     * Send the form with fetch(): fresh CSRF token, the photos from memory
     * (see keepInMemory) and errors shown right in the form. Without fetch the
     * browser submits the form the classic way.
     */
    async submit(event) {
        event.preventDefault();

        if (!this.hasFile || this.submitting) {
            return;
        }

        this.submitting = true;
        this.serverErrors = [];
        const form = event.target;
        const token = (await this.freshToken()) ?? form.querySelector('input[name="_token"]').value;

        if (!window.fetch || !window.FormData) {
            form.querySelector('input[name="_token"]').value = token;
            form.submit();

            return;
        }

        const data = new FormData(form);
        data.set('_token', token);
        data.delete('file');
        data.delete('task_file');

        if (!this.useSample) {
            if (!this.textFile) {
                this.fail(['Dein Foto bzw. deine Datei konnte nicht gelesen werden. Bitte wähle sie noch einmal aus.']);

                return;
            }

            data.append('file', this.textFile, this.textFile.name);
        }

        if (this.taskFile) {
            data.append('task_file', this.taskFile, this.taskFile.name);
        }

        let response;

        try {
            response = await fetch(form.action, {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
            });
        } catch {
            this.fail(['Der Text konnte nicht gesendet werden. Bitte prüfe deine Internetverbindung und versuche es noch einmal.']);

            return;
        }

        // Success: Laravel redirects to the processing screen, fetch followed it.
        if (response.ok) {
            window.location.assign(response.url);

            return;
        }

        const body = await response.json().catch(() => ({}));

        if (response.status === 422) {
            this.fail(Object.values(body.errors ?? {}).flat());
        } else if (response.status === 419) {
            this.fail(['Deine Sitzung ist abgelaufen. Bitte lade die Seite neu und melde dich gegebenenfalls wieder an.']);
        } else if (response.status === 429) {
            this.fail(['Zu viele Versuche in kurzer Zeit. Bitte warte eine Minute und versuche es dann noch einmal.']);
        } else if (response.status === 401) {
            window.location.assign('/login');
        } else {
            this.fail(['Beim Hochladen ist ein Fehler aufgetreten (' + response.status + '). Bitte versuche es noch einmal.']);
        }
    },

    /** Current CSRF token of the session (null when offline or logged out). */
    async freshToken() {
        try {
            const response = await fetch(this.tokenUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (response.ok && !response.redirected) {
                const { token } = await response.json();

                return token || null;
            }
        } catch {
            // Offline or blocked.
        }

        return null;
    },

    fail(messages) {
        this.serverErrors = messages.length ? messages : ['Bitte überprüfe deine Angaben.'];
        this.submitting = false;
        this.$nextTick(() => this.$refs.serverErrors?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
    },
});
