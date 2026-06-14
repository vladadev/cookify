/* Cookify — main.js */

(function () {
    'use strict';

    // ── Recipe listing page ──────────────────────────────────────────────────
    const grid       = document.getElementById('recipes-grid');
    const pagination = document.getElementById('pagination');

    if (!grid) return;

    const BASE_URL    = document.querySelector('meta[name="base-url"]')?.content
                        || window.location.origin + '/cookify';
    const API_URL     = BASE_URL + '/api/get_recipes.php';

    const catSelect   = document.getElementById('filter-category');
    const sortSelect  = document.getElementById('filter-sort');
    const orderSelect = document.getElementById('filter-order');

    let currentPage = 1;

    function fetchRecipes() {
        const params = new URLSearchParams({
            category_id: catSelect.value,
            sort:        sortSelect.value,
            order:       orderSelect.value,
            page:        currentPage,
            per_page:    6,
        });

        grid.innerHTML = '<p class="loading">Loading…</p>';
        pagination.innerHTML = '';

        fetch(API_URL + '?' + params.toString())
            .then(r => r.json())
            .then(data => {
                renderGrid(data.recipes);
                renderPagination(data.total_pages, data.page);
            })
            .catch(() => {
                grid.innerHTML = '<p class="error">Failed to load recipes. Please try again.</p>';
            });
    }

    function renderGrid(recipes) {
        if (recipes.length === 0) {
            grid.innerHTML = '<p class="empty">No recipes found.</p>';
            return;
        }

        grid.innerHTML = recipes.map(r => `
            <article class="recipe-card">
                <a href="${r.url}" class="card-image-link">
                    ${r.image_thumb
                        ? `<img src="${r.image_thumb}" alt="${escHtml(r.title)}" loading="lazy">`
                        : `<div class="card-no-image">🍽️</div>`}
                </a>
                <div class="card-body">
                    <span class="tag">${escHtml(r.category_name)}</span>
                    <span class="tag tag-${r.difficulty}">${capitalize(r.difficulty)}</span>
                    <h2 class="card-title"><a href="${r.url}">${escHtml(r.title)}</a></h2>
                    <p class="card-desc">${escHtml(r.description.substring(0, 100))}…</p>
                    <div class="card-meta">
                        <span>⏱ ${r.prep_time} min</span>
                        <span>⭐ ${r.avg_rating || 'No ratings'}</span>
                        <span>✍️ ${escHtml(r.author_name)}</span>
                    </div>
                </div>
            </article>
        `).join('');
    }

    function renderPagination(totalPages, currentP) {
        if (totalPages <= 1) return;

        let html = '';
        if (currentP > 1) {
            html += `<button class="page-btn" data-page="${currentP - 1}">← Prev</button>`;
        }

        for (let i = 1; i <= totalPages; i++) {
            html += `<button class="page-btn ${i === currentP ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }

        if (currentP < totalPages) {
            html += `<button class="page-btn" data-page="${currentP + 1}">Next →</button>`;
        }

        pagination.innerHTML = html;

        pagination.querySelectorAll('.page-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                currentPage = parseInt(btn.dataset.page, 10);
                fetchRecipes();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    [catSelect, sortSelect, orderSelect].forEach(el => {
        el.addEventListener('change', () => {
            currentPage = 1;
            fetchRecipes();
        });
    });

    fetchRecipes();
}());
