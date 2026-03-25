<?php
/**
 * PDF Viewer — PDF.js with reader toolbar, notes (localStorage), and search.
 */
?>

<div class="pdf-reader-shell d-flex flex-column flex-grow-1 h-100" style="min-height:0;">
    <div class="pdf-toolbar border-bottom bg-body py-2 px-2 px-md-3 flex-shrink-0" id="pdfToolbar">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="btn-group btn-group-sm" role="group" aria-label="Zoom">
                <button type="button" class="btn btn-outline-secondary" id="pdfZoomOut" title="Zoom out"><i class="fas fa-minus"></i></button>
                <button type="button" class="btn btn-outline-secondary px-2" id="pdfZoomLabel" disabled style="min-width:4.5rem;">150%</button>
                <button type="button" class="btn btn-outline-secondary" id="pdfZoomIn" title="Zoom in"><i class="fas fa-plus"></i></button>
                <button type="button" class="btn btn-outline-secondary" id="pdfFitWidth" title="Fit width"><i class="fas fa-arrows-alt-h"></i></button>
                <button type="button" class="btn btn-outline-secondary" id="pdfZoomReset" title="Reset zoom (150%)">1.5×</button>
            </div>
            <div class="d-flex align-items-center gap-1">
                <label class="small text-muted mb-0 d-none d-sm-inline">Page</label>
                <input type="number" class="form-control form-control-sm" id="pdfPageInput" min="1" value="1" style="width:4rem;" title="Go to page">
                <span class="small text-muted" id="pdfPageTotal">/ —</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="pdfGoPage" title="Go to page"><i class="fas fa-location-arrow"></i></button>
            </div>
            <div class="d-flex align-items-center gap-1 flex-grow-1 flex-md-grow-0" style="min-width:120px;">
                <input type="search" class="form-control form-control-sm" id="pdfSearchInput" placeholder="Search in document…" autocomplete="off">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="pdfSearchBtn" title="Find"><i class="fas fa-search"></i></button>
            </div>
            <div class="btn-group btn-group-sm ms-auto" role="group">
                <button type="button" class="btn btn-outline-secondary" id="pdfThemeBtn" title="Reading theme"><i class="fas fa-adjust"></i></button>
                <button type="button" class="btn btn-outline-secondary" id="pdfRotateBtn" title="Rotate page preview"><i class="fas fa-redo"></i></button>
                <button type="button" class="btn btn-outline-secondary" id="pdfFullscreenBtn" title="Fullscreen"><i class="fas fa-expand"></i></button>
                <button type="button" class="btn btn-outline-secondary" id="pdfPrintBtn" title="Print page"><i class="fas fa-print"></i></button>
                <button type="button" class="btn btn-outline-primary" id="pdfNotesToggle" title="Notes"><i class="fas fa-sticky-note"></i> <span class="d-none d-md-inline">Notes</span></button>
            </div>
        </div>
    </div>

    <div class="pdf-reader-body d-flex flex-grow-1 overflow-hidden position-relative">
        <div class="pdf-container theme-light flex-grow-1 overflow-y-auto" style="min-height:0;" id="pdfContainer">
            <div id="pdfPages"></div>
        </div>

        <aside class="pdf-notes-panel border-start bg-body shadow-sm" id="pdfNotesPanel" style="display:none;">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <strong><i class="fas fa-sticky-note me-1"></i> My notes</strong>
                <button type="button" class="btn-close" id="pdfNotesClose" aria-label="Close"></button>
            </div>
            <div class="p-3 small text-muted border-bottom">
                Saved in this browser for this book only.
            </div>
            <div class="p-3">
                <label class="form-label small mb-1">Add note (page <span id="pdfNotesPageLabel">1</span>)</label>
                <textarea class="form-control form-control-sm mb-2" id="pdfNoteText" rows="3" placeholder="Thoughts, quotes, reminders…"></textarea>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-primary" id="pdfNoteSave"><i class="fas fa-plus"></i> Add</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="pdfNotesExport"><i class="fas fa-file-export"></i> Export</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="pdfNotesClear"><i class="fas fa-trash"></i> Clear all</button>
                </div>
            </div>
            <div class="px-3 pb-3 overflow-auto" style="max-height:40vh;" id="pdfNotesList"></div>
        </aside>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.5.141/pdf.min.js"></script>
<script>
    function initializePdfViewer() {
        let pdfDoc = null;
        let scale = 1.5;
        let rotation = 0;
        let observer = null;
        const pdfPagesContainer = document.getElementById('pdfPages');
        const spinner = document.getElementById('loadingSpinner');
        const pdfContainer = document.getElementById('pdfContainer');
        const toolbar = document.getElementById('pdfToolbar');
        const authToken = localStorage.getItem('authToken') || sessionStorage.getItem('authToken') || '';
        const notesKey = () => 'elib_pdf_notes_' + bookId;
        const progressKey = () => 'elib_pdf_page_' + bookId;
        const zoomKey = () => 'elib_pdf_zoom_' + bookId;

        const els = {
            zoomOut: document.getElementById('pdfZoomOut'),
            zoomIn: document.getElementById('pdfZoomIn'),
            zoomLabel: document.getElementById('pdfZoomLabel'),
            fitWidth: document.getElementById('pdfFitWidth'),
            zoomReset: document.getElementById('pdfZoomReset'),
            pageInput: document.getElementById('pdfPageInput'),
            pageTotal: document.getElementById('pdfPageTotal'),
            goPage: document.getElementById('pdfGoPage'),
            searchInput: document.getElementById('pdfSearchInput'),
            searchBtn: document.getElementById('pdfSearchBtn'),
            themeBtn: document.getElementById('pdfThemeBtn'),
            rotateBtn: document.getElementById('pdfRotateBtn'),
            fullscreenBtn: document.getElementById('pdfFullscreenBtn'),
            printBtn: document.getElementById('pdfPrintBtn'),
            notesToggle: document.getElementById('pdfNotesToggle'),
            notesPanel: document.getElementById('pdfNotesPanel'),
            notesClose: document.getElementById('pdfNotesClose'),
            noteText: document.getElementById('pdfNoteText'),
            noteSave: document.getElementById('pdfNoteSave'),
            notesList: document.getElementById('pdfNotesList'),
            notesPageLabel: document.getElementById('pdfNotesPageLabel'),
            notesExport: document.getElementById('pdfNotesExport'),
            notesClear: document.getElementById('pdfNotesClear'),
            readerBody: document.querySelector('.pdf-reader-body')
        };

        let currentPage = 1;
        let themeIndex = 0;
        const themes = ['theme-light', 'theme-sepia', 'theme-dark'];

        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.5.141/pdf.worker.min.js';

        const pdfUrl = `/api/v1/books/${bookId}/file`;
        loadPdfWithAuth(pdfUrl, authToken);

        function updateZoomLabel() {
            if (els.zoomLabel) els.zoomLabel.textContent = Math.round(scale * 100) + '%';
        }

        function setScale(newScale, persist) {
            scale = Math.min(3, Math.max(0.5, newScale));
            updateZoomLabel();
            if (persist) try { localStorage.setItem(zoomKey(), String(scale)); } catch (e) {}
            rebuildPdfPages();
        }

        function rebuildPdfPages() {
            if (!pdfDoc || !pdfPagesContainer) return;
            if (observer) {
                observer.disconnect();
                observer = null;
            }
            pdfPagesContainer.innerHTML = '';
            renderPage(1);
            setupIntersectionObserver();
        }

        async function loadPdfWithAuth(url, token) {
            try {
                const headers = {};
                if (token) headers['Authorization'] = 'Bearer ' + token;

                const res = await fetch(url, { method: 'GET', headers, credentials: 'same-origin' });
                if (!res.ok) {
                    const text = await res.text();
                    let msg = 'HTTP ' + res.status;
                    try {
                        const j = JSON.parse(text);
                        if (j.message) msg = j.message;
                    } catch (_) {
                        if (text) msg += ': ' + text.slice(0, 200);
                    }
                    throw new Error(msg);
                }

                const buf = await res.arrayBuffer();
                const loadingTask = pdfjsLib.getDocument({ data: buf, disableRange: true, disableStream: true });
                const pdf = await loadingTask.promise;
                pdfDoc = pdf;

                const zStored = localStorage.getItem(zoomKey());
                if (zStored !== null && zStored !== '') {
                    const savedZ = parseFloat(zStored);
                    if (!isNaN(savedZ) && savedZ >= 0.5 && savedZ <= 3) scale = savedZ;
                } else {
                    await fitWidthIfFirstLoad();
                }
                updateZoomLabel();

                if (spinner) spinner.style.display = 'none';
                if (pdfContainer) pdfContainer.style.display = 'block';
                if (toolbar) toolbar.style.display = 'block';

                els.pageTotal.textContent = '/ ' + pdf.numPages;
                els.pageInput.max = pdf.numPages;

                rebuildPdfPages();

                const savedPage = parseInt(localStorage.getItem(progressKey()) || '1', 10);
                if (savedPage >= 1 && savedPage <= pdf.numPages) {
                    setTimeout(() => scrollToPage(savedPage), 400);
                }

                trackVisiblePage();
                renderNotesList();
            } catch (error) {
                console.error('Error loading PDF:', error);
                handleError(error);
            }
        }

        async function fitWidthIfFirstLoad() {
            if (!pdfDoc || !pdfContainer) return;
            await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));
            try {
                const page = await pdfDoc.getPage(1);
                const base = page.getViewport({ scale: 1, rotation });
                let w = pdfContainer.clientWidth - 48;
                if (w < 80) w = window.innerWidth - 48;
                if (w > 80 && base.width > 0) {
                    scale = w / base.width;
                    updateZoomLabel();
                }
            } catch (e) { console.warn('fitWidth', e); }
        }

        function handleError(error) {
            if (spinner) spinner.style.display = 'none';
            const errEl = document.getElementById('errorContainer');
            const msgEl = document.getElementById('errorMessage');
            if (errEl) errEl.style.display = 'block';
            if (msgEl) msgEl.textContent = 'Error loading PDF: ' + (error.message || 'Unknown error');
        }

        function renderPage(pageNumber) {
            pdfDoc.getPage(pageNumber).then(page => {
                const viewport = page.getViewport({ scale, rotation });

                const canvasContainer = document.createElement('div');
                canvasContainer.className = 'pdf-page';
                canvasContainer.dataset.pageNumber = String(pageNumber);
                canvasContainer.style.position = 'relative';
                canvasContainer.style.margin = '20px auto';
                canvasContainer.style.boxShadow = '0 4px 8px rgba(0,0,0,0.12)';

                const canvas = document.createElement('canvas');
                canvasContainer.appendChild(canvas);
                pdfPagesContainer.appendChild(canvasContainer);

                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                const pageLabel = document.createElement('div');
                pageLabel.className = 'page-number';
                pageLabel.textContent = pageNumber;
                pageLabel.style.cssText = 'position:absolute;bottom:10px;right:10px;background:rgba(0,0,0,0.5);color:#fff;padding:5px 10px;border-radius:4px;font-size:13px;';
                canvasContainer.appendChild(pageLabel);

                const renderContext = { canvasContext: context, viewport };
                const task = page.render(renderContext);
                if (task && task.promise) task.promise.catch(err => console.error('Render page error', err));
            });
        }

        function setupIntersectionObserver() {
            for (let i = 2; i <= pdfDoc.numPages; i++) {
                const placeholder = document.createElement('div');
                placeholder.className = 'pdf-page-placeholder';
                placeholder.dataset.pageNumber = String(i);
                placeholder.style.cssText = 'height:800px;margin:20px auto;background:#f8f9fa;border:1px solid #dee2e6;display:flex;justify-content:center;align-items:center;box-shadow:0 2px 6px rgba(0,0,0,0.06);';
                placeholder.innerHTML = '<span class="text-muted">Page ' + i + '</span>';
                pdfPagesContainer.appendChild(placeholder);
            }

            observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const pageNumber = parseInt(entry.target.dataset.pageNumber, 10);
                        if (!entry.target.classList.contains('pdf-page')) {
                            renderPage(pageNumber);
                            entry.target.remove();
                        }
                        observer.unobserve(entry.target);
                    }
                });
            }, { root: pdfContainer, rootMargin: '200px 0px', threshold: 0.1 });

            document.querySelectorAll('.pdf-page-placeholder').forEach(ph => observer.observe(ph));
        }

        function scrollToPage(num) {
            if (!pdfDoc || num < 1 || num > pdfDoc.numPages) return;
            const el = document.querySelector('.pdf-page[data-page-number="' + num + '"], .pdf-page-placeholder[data-page-number="' + num + '"]');
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            currentPage = num;
            els.pageInput.value = num;
            els.notesPageLabel.textContent = num;
            try { localStorage.setItem(progressKey(), String(num)); } catch (e) {}
        }

        function trackVisiblePage() {
            if (!pdfContainer) return;
            const io = new IntersectionObserver((entries) => {
                entries.forEach(en => {
                    if (en.isIntersecting && en.intersectionRatio > 0.35) {
                        const p = parseInt(en.target.dataset.pageNumber, 10);
                        if (!isNaN(p)) {
                            currentPage = p;
                            els.pageInput.value = p;
                            els.notesPageLabel.textContent = p;
                            try { localStorage.setItem(progressKey(), String(p)); } catch (e) {}
                        }
                    }
                });
            }, { root: pdfContainer, threshold: [0.35, 0.5] });

            const watch = () => {
                document.querySelectorAll('.pdf-page').forEach(node => io.observe(node));
            };
            watch();
            const mo = new MutationObserver(watch);
            mo.observe(pdfPagesContainer, { childList: true, subtree: true });
        }

        function loadNotes() {
            try {
                const raw = localStorage.getItem(notesKey());
                const arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) { return []; }
        }

        function saveNotes(arr) {
            try { localStorage.setItem(notesKey(), JSON.stringify(arr)); } catch (e) {}
            renderNotesList();
        }

        function renderNotesList() {
            const notes = loadNotes().sort((a, b) => (a.page || 1) - (b.page || 1) || (b.ts || 0) - (a.ts || 0));
            if (!els.notesList) return;
            if (!notes.length) {
                els.notesList.innerHTML = '<p class="text-muted small mb-0">No notes yet.</p>';
                return;
            }
            els.notesList.innerHTML = notes.map(n => {
                const id = String(n.id || '');
                const esc = (s) => String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
                return '<div class="card card-body py-2 px-2 mb-2 small" data-note-id="' + esc(id) + '">' +
                    '<div class="d-flex justify-content-between align-items-start gap-2">' +
                    '<button type="button" class="btn btn-link btn-sm p-0 text-start text-decoration-none" data-goto-page="' + (n.page || 1) + '">Page ' + (n.page || 1) + '</button>' +
                    '<button type="button" class="btn-close btn-sm" data-del-note="' + esc(id) + '" aria-label="Delete"></button></div>' +
                    '<div class="mt-1 text-break">' + esc(n.text) + '</div></div>';
            }).join('');

            els.notesList.querySelectorAll('[data-goto-page]').forEach(btn => {
                btn.addEventListener('click', () => scrollToPage(parseInt(btn.getAttribute('data-goto-page'), 10)));
            });
            els.notesList.querySelectorAll('[data-del-note]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-del-note');
                    saveNotes(loadNotes().filter(x => String(x.id) !== id));
                });
            });
        }

        async function searchInPdf() {
            const q = (els.searchInput && els.searchInput.value || '').trim().toLowerCase();
            if (!q || !pdfDoc) return;
            els.searchBtn.disabled = true;
            try {
                for (let p = 1; p <= pdfDoc.numPages; p++) {
                    const page = await pdfDoc.getPage(p);
                    const text = await page.getTextContent();
                    const str = text.items.map(i => i.str).join('\u2003').toLowerCase();
                    if (str.includes(q)) {
                        scrollToPage(p);
                        return;
                    }
                }
                alert('No matches found.');
            } finally {
                els.searchBtn.disabled = false;
            }
        }

        function cycleTheme() {
            themeIndex = (themeIndex + 1) % themes.length;
            themes.forEach(t => pdfContainer.classList.remove(t));
            pdfContainer.classList.add(themes[themeIndex]);
        }

        function toggleNotes() {
            const open = els.notesPanel.style.display !== 'none';
            els.notesPanel.style.display = open ? 'none' : 'block';
        }

        function exportNotes() {
            const data = JSON.stringify(loadNotes(), null, 2);
            navigator.clipboard.writeText(data).then(() => alert('Notes copied to clipboard as JSON.')).catch(() => {
                prompt('Copy:', data);
            });
        }

        /* Toolbar wiring */
        if (els.zoomOut) els.zoomOut.addEventListener('click', () => setScale(scale / 1.15, true));
        if (els.zoomIn) els.zoomIn.addEventListener('click', () => setScale(scale * 1.15, true));
        if (els.zoomReset) els.zoomReset.addEventListener('click', () => { scale = 1.5; updateZoomLabel(); try { localStorage.setItem(zoomKey(), String(scale)); } catch (e) {} rebuildPdfPages(); });
        if (els.fitWidth) els.fitWidth.addEventListener('click', async () => {
            if (!pdfDoc || !pdfContainer) return;
            const page = await pdfDoc.getPage(1);
            const base = page.getViewport({ scale: 1, rotation });
            const w = pdfContainer.clientWidth - 48;
            if (base.width > 0) setScale(w / base.width, true);
        });
        if (els.goPage) els.goPage.addEventListener('click', () => {
            const n = parseInt(els.pageInput.value, 10);
            scrollToPage(n);
        });
        if (els.pageInput) els.pageInput.addEventListener('change', () => {
            const n = parseInt(els.pageInput.value, 10);
            scrollToPage(n);
        });
        if (els.searchBtn) els.searchBtn.addEventListener('click', searchInPdf);
        if (els.searchInput) els.searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); searchInPdf(); } });
        if (els.themeBtn) els.themeBtn.addEventListener('click', cycleTheme);
        if (els.rotateBtn) els.rotateBtn.addEventListener('click', () => {
            rotation = (rotation + 90) % 360;
            rebuildPdfPages();
        });
        if (els.fullscreenBtn) els.fullscreenBtn.addEventListener('click', () => {
            const shell = document.querySelector('.pdf-reader-shell');
            if (!document.fullscreenElement) (shell || pdfContainer).requestFullscreen?.();
            else document.exitFullscreen?.();
        });
        if (els.printBtn) els.printBtn.addEventListener('click', () => window.print());
        if (els.notesToggle) els.notesToggle.addEventListener('click', toggleNotes);
        if (els.notesClose) els.notesClose.addEventListener('click', () => { els.notesPanel.style.display = 'none'; });
        if (els.noteSave) els.noteSave.addEventListener('click', () => {
            const text = (els.noteText.value || '').trim();
            if (!text) return;
            const page = currentPage || 1;
            const notes = loadNotes();
            notes.push({ id: Date.now() + '_' + Math.random().toString(36).slice(2), page, text, ts: Date.now() });
            els.noteText.value = '';
            saveNotes(notes);
        });
        if (els.notesExport) els.notesExport.addEventListener('click', exportNotes);
        if (els.notesClear) els.notesClear.addEventListener('click', () => {
            if (confirm('Delete all notes for this book in this browser?')) saveNotes([]);
        });

        document.addEventListener('keydown', e => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            if (e.key === '+' || e.key === '=') { e.preventDefault(); setScale(scale * 1.1, true); }
            if (e.key === '-' || e.key === '_') { e.preventDefault(); setScale(scale / 1.1, true); }
        });
    }
</script>

<style>
    .pdf-reader-shell { min-height: 60vh; }
    .pdf-toolbar { display: none; }
    .pdf-container.theme-light { background-color: #e9ecef; }
    .pdf-container.theme-sepia { background-color: #f4ecd8; }
    .pdf-container.theme-dark { background-color: #1a1d21; }
    .pdf-container { padding: 16px 0; text-align: center; }
    .pdf-page { background-color: #fff; display: inline-block; }
    .theme-dark .pdf-page { box-shadow: 0 4px 12px rgba(0,0,0,0.45) !important; }
    .pdf-notes-panel {
        width: 100%;
        max-width: 320px;
        flex-shrink: 0;
        overflow-y: auto;
    }
    @media (max-width: 991px) {
        .pdf-notes-panel {
            position: absolute;
            top: 0; right: 0; bottom: 0;
            z-index: 20;
            max-width: 100%;
            width: min(100%, 360px);
        }
    }
    @media print {
        .pdf-toolbar, .document-controls, .card-header, #pdfNotesPanel { display: none !important; }
        .pdf-container { overflow: visible !important; height: auto !important; }
    }
    @media (max-width: 768px) {
        .pdf-container canvas { max-width: 100%; height: auto !important; }
    }
</style>
