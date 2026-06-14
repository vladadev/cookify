/* Cookify — recipe-form.js
   Handles dynamic ingredient rows on add/edit recipe pages.
   Requires BASE_URL to be defined in a preceding <script> block. */

(function () {
    'use strict';

    const list = document.getElementById('ingredients-list');
    const addBtn = document.getElementById('add-ingredient');

    if (!list || !addBtn) return;

    function buildRow(ingredients, selectedId, selectedQty) {
        const row = document.createElement('div');
        row.className = 'ingredient-row';

        const sel = document.createElement('select');
        sel.name = 'ingredient_id[]';
        sel.required = true;

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Select ingredient…';
        sel.appendChild(placeholder);

        ingredients.forEach(function (ing) {
            const opt = document.createElement('option');
            opt.value = ing.id;
            opt.textContent = ing.name + ' (' + ing.unit + ')';
            if (String(ing.id) === String(selectedId)) opt.selected = true;
            sel.appendChild(opt);
        });

        const qty = document.createElement('input');
        qty.type = 'number';
        qty.name = 'ingredient_qty[]';
        qty.placeholder = 'Quantity';
        qty.step = '0.01';
        qty.min = '0.01';
        qty.required = true;
        if (selectedQty) qty.value = selectedQty;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-small btn-danger remove-ingredient';
        btn.textContent = '✕';

        row.appendChild(sel);
        row.appendChild(qty);
        row.appendChild(btn);
        return row;
    }

    fetch(BASE_URL + '/api/get_ingredients.php')
        .then(function (r) { return r.json(); })
        .then(function (ingredients) {

            // Populate existing rows (edit page) or build first empty row (add page)
            const existingRows = list.querySelectorAll('.ingredient-row');

            if (existingRows.length === 0) {
                list.appendChild(buildRow(ingredients, null, null));
            } else {
                existingRows.forEach(function (row) {
                    const hiddenId  = row.dataset.ingId  || null;
                    const hiddenQty = row.dataset.ingQty || null;
                    const newRow = buildRow(ingredients, hiddenId, hiddenQty);
                    row.replaceWith(newRow);
                });
            }

            addBtn.addEventListener('click', function () {
                list.appendChild(buildRow(ingredients, null, null));
            });

            list.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-ingredient')) {
                    if (list.querySelectorAll('.ingredient-row').length > 1) {
                        e.target.closest('.ingredient-row').remove();
                    }
                }
            });
        })
        .catch(function () {
            list.innerHTML = '<p class="error">Failed to load ingredients.</p>';
        });
}());
