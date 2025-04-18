<main class="flex-grow-1 py-5" id="featured">
    <div class="container">
        <h2 class="text-center mb-5 fw-bold text-primary">Featured Collection</h2>

        <!-- Loading Spinner -->
        <div id="loader" class="text-center my-5">
            <div class="spinner-border text-primary" role="status" aria-label="Loading..."></div>
        </div>

        <!-- Books Grid -->
        <div class="row g-4" id="booksGrid">
            <!-- Dynamic content loaded from API -->
        </div>
    </div>
</main>

<!-- Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const loader = document.getElementById('loader');
        const booksGrid = document.getElementById('booksGrid');

        const loadFeaturedBooks = async () => {
            try {
                loader.style.display = 'block';
                const { data } = await axios.get('/api/v1/featured-books');

                if (data?.status === 'success' && Array.isArray(data.books)) {
                    renderBooks(data.books);
                } else {
                    showError('No books found in the collection.');
                }
            } catch (error) {
                console.error('Error loading featured books:', error);
                showError('An error occurred while fetching featured books.');
            } finally {
                loader.style.display = 'none';
            }
        };

        const renderBooks = (books) => {
            if (!books.length) return showError('No featured books available.');

            booksGrid.innerHTML = books.map(book => `
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="card h-100 shadow-sm border-0 book-card">
                        <img src="${book.cover || '/assets/images/placeholder-book.jpg'}"
                             alt="${book.title} cover"
                             class="card-img-top book-cover"
                             onerror="this.src='/assets/images/placeholder-book.jpg'">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title mb-1 text-truncate" title="${book.title}">${book.title}</h5>
                            <p class="text-muted mb-3 small">${book.author || 'Unknown Author'}</p>
                            <div class="d-flex justify-content-between mt-auto align-items-center">
                                <span class="badge bg-info text-dark">${book.genre || 'General'}</span>
                                <a href="/book/${book.id}" class="btn btn-sm btn-outline-primary">
                                    Details <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        };

        const showError = (message) => {
            booksGrid.innerHTML = `
                <div class="col-12 text-center text-danger">
                    <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                    <p class="fw-semibold">${message}</p>
                </div>
            `;
        };

        loadFeaturedBooks();
    });
</script>