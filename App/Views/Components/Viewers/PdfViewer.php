<?php
/**
 * PDF Viewer Component
 * Uses PDF.js to render PDF documents in the browser
 *
 * Loads PDF bytes via fetch() + Authorization header. PDF.js cannot reliably attach
 * Bearer tokens to range requests made inside the worker, so URL+httpHeaders often fails.
 */
?>

<!-- PDF Canvas Container -->
<div class="pdf-container" style="height: 90vh; overflow-y: auto;" id="pdfContainer">
    <div id="pdfPages"></div>
</div>

<!-- PDF.js Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.5.141/pdf.min.js"></script>
<script>
    function initializePdfViewer() {
        let pdfDoc = null;
        let scale = 1.5;
        const pdfPagesContainer = document.getElementById('pdfPages');
        const spinner = document.getElementById('loadingSpinner');
        const pdfContainer = document.getElementById('pdfContainer');
        const authToken = localStorage.getItem('authToken') || sessionStorage.getItem('authToken') || '';

        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.5.141/pdf.worker.min.js';

        const pdfUrl = `/api/v1/books/${bookId}/file`;
        loadPdfWithAuth(pdfUrl, authToken);

        async function loadPdfWithAuth(url, token) {
            try {
                const headers = {};
                if (token) {
                    headers['Authorization'] = 'Bearer ' + token;
                }

                const res = await fetch(url, {
                    method: 'GET',
                    headers: headers,
                    credentials: 'same-origin'
                });

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
                const loadingTask = pdfjsLib.getDocument({
                    data: buf,
                    disableRange: true,
                    disableStream: true
                });

                const pdf = await loadingTask.promise;
                pdfDoc = pdf;
                if (spinner) spinner.style.display = 'none';
                if (pdfContainer) pdfContainer.style.display = 'block';

                renderPage(1);
                setupIntersectionObserver();
            } catch (error) {
                console.error('Error loading PDF:', error);
                handleError(error);
            }
        }

        function handleError(error) {
            if (spinner) spinner.style.display = 'none';
            const errEl = document.getElementById('errorContainer');
            const msgEl = document.getElementById('errorMessage');
            if (errEl) errEl.style.display = 'block';
            if (msgEl) {
                msgEl.textContent = 'Error loading PDF: ' + (error.message || 'Unknown error');
            }
        }

        function renderPage(pageNumber) {
            pdfDoc.getPage(pageNumber).then(page => {
                const viewport = page.getViewport({ scale });

                const canvasContainer = document.createElement('div');
                canvasContainer.className = 'pdf-page';
                canvasContainer.dataset.pageNumber = pageNumber;
                canvasContainer.style.position = 'relative';
                canvasContainer.style.margin = '20px auto';
                canvasContainer.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';

                const canvas = document.createElement('canvas');
                canvasContainer.appendChild(canvas);
                pdfPagesContainer.appendChild(canvasContainer);

                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                const pageLabel = document.createElement('div');
                pageLabel.className = 'page-number';
                pageLabel.textContent = pageNumber;
                pageLabel.style.position = 'absolute';
                pageLabel.style.bottom = '10px';
                pageLabel.style.right = '10px';
                pageLabel.style.background = 'rgba(0,0,0,0.5)';
                pageLabel.style.color = 'white';
                pageLabel.style.padding = '5px 10px';
                pageLabel.style.borderRadius = '4px';
                pageLabel.style.fontSize = '14px';
                canvasContainer.appendChild(pageLabel);

                const renderContext = { canvasContext: context, viewport };
                const task = page.render(renderContext);
                if (task && task.promise) {
                    task.promise.catch(err => console.error('Render page error', err));
                }
            });
        }

        function setupIntersectionObserver() {
            for (let i = 2; i <= pdfDoc.numPages; i++) {
                const placeholder = document.createElement('div');
                placeholder.className = 'pdf-page-placeholder';
                placeholder.dataset.pageNumber = i;
                placeholder.style.height = '800px';
                placeholder.style.margin = '20px auto';
                placeholder.style.backgroundColor = '#f8f9fa';
                placeholder.style.border = '1px solid #dee2e6';
                placeholder.style.display = 'flex';
                placeholder.style.justifyContent = 'center';
                placeholder.style.alignItems = 'center';
                placeholder.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
                placeholder.innerHTML = `<span>Page ${i}</span>`;
                pdfPagesContainer.appendChild(placeholder);
            }

            const observer = new IntersectionObserver((entries) => {
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
            }, {
                root: pdfContainer,
                rootMargin: '200px 0px',
                threshold: 0.1
            });

            document.querySelectorAll('.pdf-page-placeholder').forEach(placeholder => {
                observer.observe(placeholder);
            });
        }
    }
</script>

<style>
    .pdf-container {
        background-color: #eee;
        padding: 20px 0;
        text-align: center;
    }

    .pdf-page {
        background-color: white;
        display: inline-block;
    }

    @media (max-width: 768px) {
        .pdf-container canvas {
            max-width: 100%;
            height: auto !important;
        }
    }
</style>
