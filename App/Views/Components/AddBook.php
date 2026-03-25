<?php
$bookCategories = [
    'Computer Science',
    'Electronics',
    'Mathematics',
    'Networking',
    'Physics',
    'Programming',
    'Robotics',
    'Telecommunications',
];
?>
<main class="add-book-page">
    <section class="page-hero page-hero--add-book text-white position-relative overflow-hidden" aria-labelledby="add-book-heading">
        <div class="page-hero-backdrop" aria-hidden="true"></div>
        <div class="container position-relative py-4 py-lg-5">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb page-hero-breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Add book</li>
                </ol>
            </nav>
            <div class="page-hero-accent bg-primary rounded mb-3" role="presentation"></div>
            <h1 id="add-book-heading" class="page-hero-title display-6 fw-bold mb-2">Add a new book</h1>
            <p class="page-hero-lead text-white-50 mb-0 col-lg-8">
                Upload a PDF and enter catalog details. A cover thumbnail is generated after upload.
            </p>
        </div>
    </section>

    <div class="container pb-5">
        <div id="formAlert" class="alert d-none border-0 shadow-sm mb-4" role="alert"></div>

        <form id="bookForm" method="POST" action="" enctype="multipart/form-data" novalidate>
            <?php if (function_exists('csrf_field')): ?>
                <?= csrf_field() ?>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card add-book-card border-0 shadow-sm h-100">
                        <div class="card-body p-4 p-lg-5">
                            <h2 class="h5 fw-semibold mb-4 add-book-section-title">
                                <span class="add-book-step-label">Step 1</span>
                                <span class="add-book-step-heading">Book details</span>
                            </h2>

                            <div class="mb-4">
                                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" id="title" name="title" required
                                       placeholder="e.g. Introduction to Algorithms" autocomplete="off">
                            </div>

                            <div class="mb-4">
                                <label for="author" class="form-label">Author <span class="text-muted fw-normal small">(optional)</span></label>
                                <input type="text" class="form-control" id="author" name="author"
                                       placeholder="Author name" autocomplete="off">
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label for="year" class="form-label">Year <span class="text-muted fw-normal small">(optional)</span></label>
                                    <input type="number" class="form-control" id="year" name="year" min="0" placeholder="2024">
                                </div>
                                <div class="col-md-8">
                                    <label for="isbnFormatted" class="form-label">ISBN <span class="text-muted fw-normal small">(optional)</span></label>
                                    <input type="text" class="form-control" id="isbnFormatted" name="isbn"
                                           placeholder="ISBN-10 or ISBN-13" inputmode="numeric" autocomplete="off">
                                    <div id="isbnHelp" class="form-text">
                                        <span id="isbnValidation" class="small"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-0">
                                <label for="description" class="form-label">Description <span class="text-muted fw-normal small">(optional)</span></label>
                                <textarea class="form-control" id="description" name="description" rows="5"
                                          placeholder="Summary, edition notes, or why this title belongs in the library…"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="add-book-sticky">
                        <div class="card add-book-card border-0 shadow-sm mb-4">
                            <div class="card-body p-4 p-lg-5">
                                <h2 class="h5 fw-semibold mb-3 add-book-section-title">
                                    <span class="add-book-step-label">Step 2</span>
                                    <span class="add-book-step-heading">Upload PDF <span class="text-danger">*</span></span>
                                </h2>

                                <label class="visually-hidden" for="bookFile">PDF file</label>
                                <input type="file" id="bookFile" name="bookFile" accept=".pdf,application/pdf" required class="visually-hidden">

                                <div class="pdf-dropzone rounded-3 mb-2" id="pdfDropzone" tabindex="0" role="button"
                                     aria-label="Upload PDF by click or drag and drop">
                                    <div class="pdf-dropzone-inner" id="dropzoneEmpty">
                                        <div class="pdf-dropzone-icon mb-3">
                                            <i class="fas fa-cloud-arrow-up" aria-hidden="true"></i>
                                        </div>
                                        <p class="fw-semibold mb-1">Drop PDF here or click to browse</p>
                                        <p class="text-muted small mb-0">One PDF per title · max size depends on server limits</p>
                                    </div>
                                    <div class="pdf-dropzone-inner d-none" id="dropzoneFilled">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="pdf-file-badge flex-shrink-0">
                                                <i class="fas fa-file-pdf" aria-hidden="true"></i>
                                            </div>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="fw-semibold text-truncate" id="pdfFileName"></div>
                                                <div class="small text-muted" id="pdfFileSize"></div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" id="pdfRemoveBtn"
                                                    aria-label="Remove selected PDF">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <p class="small text-muted mb-0"><i class="fas fa-lock me-1 opacity-75"></i>Only PDF files are accepted.</p>
                            </div>
                        </div>

                        <div class="card add-book-card border-0 shadow-sm mb-4">
                            <div class="card-body p-4 p-lg-5">
                                <h2 class="h5 fw-semibold mb-2">Categories</h2>
                                <p class="small text-muted mb-3">Pick topics from the list, search, or type a new category at the bottom of the dropdown.</p>

                                <label for="categorySelect" class="form-label small fw-semibold text-secondary">Categories</label>
                                <div class="add-book-multiselect-wrap mb-2">
                                    <select id="categorySelect" class="category-select-native" multiple
                                            data-placeholder="Choose categories…">
                                        <?php foreach ($bookCategories as $cat) : ?>
                                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <p class="form-text small text-muted mb-0">Open the field, then use the text box inside the dropdown to create categories not in the list.</p>
                            </div>
                        </div>

                        <div class="card add-book-card border-0 shadow-sm mb-4">
                            <div class="card-body p-4 p-lg-5">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <h2 class="h6 fw-semibold mb-1">Allow download</h2>
                                        <p class="small text-muted mb-0">Let readers download the PDF (preview may still be available separately).</p>
                                    </div>
                                    <div class="form-check form-switch m-0 pt-1">
                                        <input class="form-check-input" type="checkbox" role="switch" id="downloadableSwitch" checked
                                               aria-describedby="downloadableHelp">
                                    </div>
                                </div>
                                <input type="hidden" name="downloadable" id="downloadableField" value="true">
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-sm-flex justify-content-sm-end">
                            <button type="reset" class="btn btn-outline-secondary order-2 order-sm-1 px-4" id="clearForm">
                                Reset form
                            </button>
                            <button type="submit" class="btn btn-primary order-1 order-sm-2 px-4" id="submitBookBtn">
                                <span class="submit-label"><i class="fas fa-plus-circle me-2"></i>Add to library</span>
                                <span class="submit-loading d-none"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Saving…</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<script src="/assets/js/MultiSelect.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("bookForm");
    const yearInput = document.getElementById("year");
    const clearForm = document.getElementById("clearForm");
    const formAlert = document.getElementById("formAlert");
    const bookFileInput = document.getElementById("bookFile");
    const pdfDropzone = document.getElementById("pdfDropzone");
    const dropzoneEmpty = document.getElementById("dropzoneEmpty");
    const dropzoneFilled = document.getElementById("dropzoneFilled");
    const pdfFileName = document.getElementById("pdfFileName");
    const pdfFileSize = document.getElementById("pdfFileSize");
    const pdfRemoveBtn = document.getElementById("pdfRemoveBtn");
    const downloadableSwitch = document.getElementById("downloadableSwitch");
    const downloadableField = document.getElementById("downloadableField");
    const submitBookBtn = document.getElementById("submitBookBtn");

    const categorySelectEl = document.getElementById("categorySelect");
    let categoryMultiSelect = null;
    if (typeof MultiSelect !== "undefined" && categorySelectEl) {
        categoryMultiSelect = new MultiSelect(categorySelectEl, {
            placeholder: "Choose categories…",
            search: false,
            selectAll: false,
            listAll: true,
            closeListOnItemSelect: false
        });
        const refreshBase = categoryMultiSelect.refresh.bind(categoryMultiSelect);
        categoryMultiSelect.refresh = function () {
            refreshBase();
            attachCategoryDropdownAddNew();
        };
        attachCategoryDropdownAddNew();
    }

    yearInput.max = new Date().getFullYear();

    function normalizeCategory(s) {
        return s.trim().replace(/\s+/g, " ");
    }

    /**
     * Text input + Add at bottom of MultiSelect panel; new options use addItem() so they stay in the list.
     * Re-attached after each refresh() because the library rebuilds the dropdown DOM.
     */
    function attachCategoryDropdownAddNew() {
        if (!categoryMultiSelect) {
            return;
        }
        const optionsEl = categoryMultiSelect.element.querySelector(".multi-select-options");
        if (!optionsEl || optionsEl.querySelector(".multi-select-add-new")) {
            return;
        }
        const footer = document.createElement("div");
        footer.className = "multi-select-add-new";
        footer.innerHTML =
            '<div class="input-group input-group-sm px-2 py-2 gap-1 align-items-stretch">' +
            '<input type="text" class="form-control form-control-sm" maxlength="80" autocomplete="off" ' +
            'placeholder="New category…" aria-label="Type a new category name">' +
            '<button type="button" class="btn btn-sm btn-primary flex-shrink-0">Add</button>' +
            "</div>";
        const input = footer.querySelector("input");
        const btn = footer.querySelector("button");
        const tryAdd = () => {
            const n = normalizeCategory(input.value);
            if (!n) {
                return;
            }
            const lower = n.toLowerCase();
            const match = categoryMultiSelect.data.find((d) => String(d.value).toLowerCase() === lower);
            if (match) {
                categoryMultiSelect.select(match.value);
            } else {
                categoryMultiSelect.addItem({ value: n, text: n, selected: true });
            }
            input.value = "";
            input.focus();
        };
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            e.preventDefault();
            tryAdd();
        });
        input.addEventListener("keydown", (e) => {
            e.stopPropagation();
            if (e.key === "Enter") {
                e.preventDefault();
                tryAdd();
            }
        });
        input.addEventListener("click", (e) => e.stopPropagation());
        footer.addEventListener("mousedown", (e) => e.stopPropagation());
        optionsEl.appendChild(footer);
    }

    function collectCategories() {
        if (!categoryMultiSelect) {
            return [];
        }
        return categoryMultiSelect.selectedValues.slice();
    }

    function resetCategories() {
        if (categoryMultiSelect) {
            categoryMultiSelect.reset();
        }
    }

    function showAlert(type, message) {
        formAlert.className = "alert border-0 shadow-sm mb-4 alert-" + type;
        formAlert.textContent = message;
        formAlert.classList.remove("d-none");
        formAlert.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }

    function hideAlert() {
        formAlert.classList.add("d-none");
    }

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + " B";
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + " KB";
        return (bytes / 1048576).toFixed(1) + " MB";
    }

    function syncDropzoneUI() {
        const f = bookFileInput.files[0];
        if (f) {
            dropzoneEmpty.classList.add("d-none");
            dropzoneFilled.classList.remove("d-none");
            pdfFileName.textContent = f.name;
            pdfFileSize.textContent = formatBytes(f.size);
            pdfDropzone.classList.add("pdf-dropzone--has-file");
        } else {
            dropzoneEmpty.classList.remove("d-none");
            dropzoneFilled.classList.add("d-none");
            pdfDropzone.classList.remove("pdf-dropzone--has-file");
        }
    }

    pdfDropzone.addEventListener("click", (e) => {
        if (e.target.closest("#pdfRemoveBtn")) return;
        bookFileInput.click();
    });

    pdfDropzone.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.key === " ") {
            e.preventDefault();
            bookFileInput.click();
        }
    });

    ["dragenter", "dragover"].forEach((ev) => {
        pdfDropzone.addEventListener(ev, (e) => {
            e.preventDefault();
            e.stopPropagation();
            pdfDropzone.classList.add("pdf-dropzone--drag");
        });
    });

    ["dragleave", "drop"].forEach((ev) => {
        pdfDropzone.addEventListener(ev, (e) => {
            e.preventDefault();
            e.stopPropagation();
            pdfDropzone.classList.remove("pdf-dropzone--drag");
        });
    });

    pdfDropzone.addEventListener("drop", (e) => {
        const files = e.dataTransfer.files;
        if (files.length && files[0].type === "application/pdf") {
            const dt = new DataTransfer();
            dt.items.add(files[0]);
            bookFileInput.files = dt.files;
            syncDropzoneUI();
        } else if (files.length) {
            showAlert("warning", "Please drop a PDF file only.");
        }
    });

    bookFileInput.addEventListener("change", () => {
        if (bookFileInput.files[0] && bookFileInput.files[0].type !== "application/pdf") {
            bookFileInput.value = "";
            showAlert("warning", "Only PDF files are allowed.");
            syncDropzoneUI();
            return;
        }
        syncDropzoneUI();
        hideAlert();
    });

    pdfRemoveBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        bookFileInput.value = "";
        syncDropzoneUI();
    });

    downloadableSwitch.addEventListener("change", () => {
        downloadableField.value = downloadableSwitch.checked ? "true" : "false";
    });

    clearForm.addEventListener("click", () => {
        setTimeout(() => {
            rawIsbn = "";
            document.getElementById("isbnValidation").textContent = "";
            document.getElementById("isbnValidation").className = "small";
            downloadableSwitch.checked = true;
            downloadableField.value = "true";
            syncDropzoneUI();
            resetCategories();
            hideAlert();
        }, 0);
    });

    const isbnFormatted = document.getElementById("isbnFormatted");
    const isbnValidation = document.getElementById("isbnValidation");
    let rawIsbn = "";

    isbnFormatted.addEventListener("input", function () {
        const cursorPosition = this.selectionStart;
        let value = this.value.replace(/[^0-9X]/gi, "");
        value = value.replace(/x/g, "X");
        rawIsbn = value;
        if (value.length > 13) {
            value = value.slice(0, 13);
            rawIsbn = value;
        }
        let formatted = formatISBN(value);
        if (this.value !== formatted) {
            this.value = formatted;
            let newPosition = cursorPosition;
            if (value.length >= 1 && cursorPosition > 1) newPosition++;
            if (value.length >= 4 && cursorPosition > 4) newPosition++;
            if (value.length >= 7 && cursorPosition > 7) newPosition++;
            if (value.length >= 12 && cursorPosition > 12) newPosition++;
            this.setSelectionRange(newPosition, newPosition);
        }
        const msg = validateISBN(rawIsbn);
        isbnValidation.textContent = msg;
        isbnValidation.classList.remove("text-success", "text-danger", "text-muted");
        if (!rawIsbn) {
            isbnValidation.classList.add("text-muted");
        } else if (msg === "Valid ISBN-10" || msg === "Valid ISBN-13") {
            isbnValidation.classList.add("text-success");
        } else if (msg.startsWith("Continue")) {
            isbnValidation.classList.add("text-muted");
        } else {
            isbnValidation.classList.add("text-danger");
        }
    });

    function formatISBN(isbn) {
        if (!isbn) return "";
        if (isbn.length <= 1) return isbn;
        if (isbn.length <= 4) {
            return isbn.substring(0, 1) + (isbn.length > 1 ? "-" + isbn.substring(1) : "");
        }
        if (isbn.length <= 7) {
            return isbn.substring(0, 1) + "-" + isbn.substring(1, 4) + (isbn.length > 4 ? "-" + isbn.substring(4) : "");
        }
        if (isbn.length <= 10) {
            if (isbn.length === 10) {
                return isbn.substring(0, 1) + "-" + isbn.substring(1, 4) + "-" + isbn.substring(4, 9) + "-" + isbn.substring(9, 10);
            }
            return isbn.substring(0, 1) + "-" + isbn.substring(1, 4) + "-" + isbn.substring(4);
        }
        if (isbn.length === 13) {
            return isbn.substring(0, 3) + "-" + isbn.substring(3, 4) + "-" + isbn.substring(4, 7) + "-" + isbn.substring(7, 12) + "-" + isbn.substring(12, 13);
        }
        return isbn.substring(0, 3) + "-" + isbn.substring(3, 4) + "-" + isbn.substring(4, 7) + "-" + isbn.substring(7);
    }

    function validateISBN(isbn) {
        if (!isbn) return "";
        if (isbn.length < 10) {
            return "Continue entering digits (ISBN-10: 10 digits, ISBN-13: 13 digits)";
        }
        if (isbn.length !== 10 && isbn.length !== 13) {
            return "ISBN must be 10 or 13 characters long";
        }
        if (isbn.length === 10) {
            if (/[X]/i.test(isbn.substring(0, 9))) {
                return "Only the last character of ISBN-10 can be X";
            }
            let sum = 0;
            for (let i = 0; i < 9; i++) {
                sum += parseInt(isbn.charAt(i), 10) * (10 - i);
            }
            let checkDigit = 11 - (sum % 11);
            if (checkDigit === 11) checkDigit = 0;
            if (checkDigit === 10) checkDigit = "X";
            const lastChar = isbn.charAt(9).toUpperCase();
            if (lastChar !== checkDigit.toString()) {
                return "Invalid ISBN-10 checksum";
            }
            return "Valid ISBN-10";
        }
        if (isbn.length === 13) {
            if (/[X]/i.test(isbn)) {
                return "ISBN-13 cannot contain X";
            }
            let sum = 0;
            for (let i = 0; i < 12; i++) {
                sum += parseInt(isbn.charAt(i), 10) * (i % 2 === 0 ? 1 : 3);
            }
            let checkDigit = 10 - (sum % 10);
            if (checkDigit === 10) checkDigit = 0;
            if (parseInt(isbn.charAt(12), 10) !== checkDigit) {
                return "Invalid ISBN-13 checksum";
            }
            return "Valid ISBN-13";
        }
        return "";
    }

    function setSubmitLoading(loading) {
        submitBookBtn.disabled = loading;
        clearForm.disabled = loading;
        submitBookBtn.querySelector(".submit-label").classList.toggle("d-none", loading);
        submitBookBtn.querySelector(".submit-loading").classList.toggle("d-none", !loading);
    }

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        hideAlert();

        const title = document.getElementById("title").value.trim();
        const author = document.getElementById("author").value.trim();
        const selectedCategories = collectCategories();
        const year = yearInput.value;
        const description = document.getElementById("description").value.trim();
        const isbn = rawIsbn;
        const bookPdf = bookFileInput.files[0];
        const downloadable = downloadableField.value;
        const token = localStorage.getItem("authToken") || sessionStorage.getItem("authToken");

        if (!title) {
            showAlert("danger", "Please enter a title.");
            document.getElementById("title").focus();
            return;
        }
        if (!bookPdf) {
            showAlert("warning", "Please choose a PDF file.");
            pdfDropzone.focus();
            return;
        }
        const isbnError = validateISBN(isbn);
        if (isbn && isbnError !== "Valid ISBN-10" && isbnError !== "Valid ISBN-13") {
            showAlert("danger", "Fix the ISBN or clear the field.");
            isbnFormatted.focus();
            return;
        }
        if (!token) {
            showAlert("warning", "You need to be logged in as an administrator to add books.");
            return;
        }

        const formData = new FormData();
        formData.append("title", title);
        formData.append("author", author);
        formData.append("categories", JSON.stringify(selectedCategories));
        formData.append("year", year);
        formData.append("isbn", isbn);
        formData.append("description", description);
        formData.append("bookFile", bookPdf);
        formData.append("downloadable", downloadable);

        setSubmitLoading(true);

        axios
            .post("/api/v1/books", formData, {
                headers: {
                    Authorization: "Bearer " + token,
                },
            })
            .then(() => {
                rawIsbn = "";
                showAlert("success", "Book added successfully.");
                form.reset();
                downloadableSwitch.checked = true;
                downloadableField.value = "true";
                document.getElementById("isbnValidation").textContent = "";
                document.getElementById("isbnValidation").className = "small";
                syncDropzoneUI();
                resetCategories();
            })
            .catch((error) => {
                let msg = "Something went wrong. Please try again.";
                if (error.response && error.response.data && error.response.data.message) {
                    msg = error.response.data.message;
                }
                showAlert("danger", msg);
            })
            .finally(() => {
                setSubmitLoading(false);
            });
    });
});
</script>
