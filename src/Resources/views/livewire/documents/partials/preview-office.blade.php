@php
    $isDataUrl = str_starts_with($fileUrl, 'data:');
    $isBlobUrl = str_starts_with($fileUrl, 'blob:');

    if ($isDataUrl) {
        $extension = 'bin';

        // Override extension based on MIME for data URLs.
        if (str_contains($fileUrl, 'wordprocessingml')) {
            $extension = 'docx';
        } elseif (str_contains($fileUrl, 'spreadsheetml')) {
            $extension = 'xlsx';
        } elseif (str_contains($fileUrl, 'ms-excel')) {
            $extension = 'xls';
        } elseif (str_contains($fileUrl, 'msword')) {
            $extension = 'doc';
        }
    } elseif ($isBlobUrl) {
        // Blob URLs have no path/extension, so derive the extension from the
        // original file name or MIME type supplied by the upload field.
        $extension = strtolower(pathinfo((string) $fileName, PATHINFO_EXTENSION));
        $mime = strtolower((string) $fileMime);

        if ($extension === '' || $extension === 'bin') {
            if (str_contains($mime, 'wordprocessingml') || $mime === 'application/msword') {
                $extension = 'docx';
            } elseif (str_contains($mime, 'spreadsheetml') || $mime === 'application/vnd.ms-excel') {
                $extension = 'xlsx';
            }
        }
    } else {
        $extension = strtolower(pathinfo($fileUrl, PATHINFO_EXTENSION));
    }
@endphp

<div x-data="{
    fileUrl: '{{ $fileUrl }}',
    extension: '{{ $extension }}',
    isDataUrl: {{ $isDataUrl ? 'true' : 'false' }},
    
    async init() {
        await this.loadScripts();
        this.loadPreview();
    },

    async loadScripts() {
        const loadScript = (id, src) => {
            if (document.getElementById(id)) return Promise.resolve();
            return new Promise((resolve) => {
                const script = document.createElement('script');
                script.id = id;
                script.src = src;
                script.onload = resolve;
                document.head.appendChild(script);
            });
        };

        if (this.extension === 'docx') {
            await loadScript('jszip-lib', 'https://unpkg.com/jszip@3.10.1/dist/jszip.min.js');
            await loadScript('docx-preview-lib', 'https://cdn.jsdelivr.net/npm/docx-preview@0.3.5/dist/docx-preview.js');
        } else if (this.extension === 'xlsx' || this.extension === 'xls') {
            await loadScript('xlsx-lib', 'https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js');
        } else if (this.extension === 'doc') {
            // Old .doc format not supported
        }
    },

    loadPreview() {
        const container = this.$refs.previewContainer;
        if (!container) return;
        
        container.innerHTML = `<div class='text-center p-5'><div class='spinner-border text-primary'></div><p class='mt-2'>Loading preview...</p></div>`;
        
        if (this.extension === 'docx') {
            this.previewDocx(container);
        } else if (this.extension === 'xlsx' || this.extension === 'xls') {
            this.previewXlsx(container);
        } else if (this.extension === 'doc') {
            container.innerHTML = `<div class='alert alert-warning'>Preview for older .doc files is not supported. Please download the file.</div>`;
        } else {
            container.innerHTML = `<div class='alert alert-warning'>Preview not available for this file type.</div>`;
        }
    },

    previewDocx(container) {
        if (typeof window.docx === 'undefined') {
            container.innerHTML = `<div class='alert alert-danger'>docx-preview library failed to load.</div>`;
            return;
        }
        fetch(this.fileUrl)
            .then(res => res.arrayBuffer())
            .then(data => {
                container.innerHTML = ''; 
                return window.docx.renderAsync(data, container, null, {
                    inWrapper: true,
                    ignoreWidth: false,
                    ignoreHeight: false
                });
            })
            .catch(err => {
                container.innerHTML = `<div class='alert alert-danger'>DOCX Error: ${err.message}. Check CORS settings.</div>`;
            });
    },

    previewXlsx(container) {
        if (typeof XLSX === 'undefined') {
            container.innerHTML = `<div class='alert alert-danger'>XLSX library failed to load.</div>`;
            return;
        }
        fetch(this.fileUrl)
            .then(res => res.arrayBuffer())
            .then(data => {
                const wb = XLSX.read(data, { type: 'array' });
                const html = XLSX.utils.sheet_to_html(wb.Sheets[wb.SheetNames[0]]);
                container.innerHTML = `<div class='table-responsive'>${html}</div>`;
                const table = container.querySelector('table');
                if (table) table.classList.add('table', 'table-bordered', 'table-sm', 'table-striped');
            })
            .catch(err => {
                container.innerHTML = `<div class='alert alert-danger'>Excel Error: ${err.message}</div>`;
            });
    }
}" class="w-100">
    <div x-ref="previewContainer" style="min-height: 500px; overflow: auto;" class="bg-white border p-3 rounded"></div>
    <div class="text-center mt-3">
        <a :href="fileUrl" class="btn btn-primary" download>
            <i class="fas fa-download me-1"></i> Download Original
        </a>
    </div>
</div>