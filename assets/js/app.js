// Kusina ni Nanay: front-end JavaScript (no libraries)
// Style rules from the lab feedback:
//  - const / let only (no var)
//  - no JavaScript inside HTML tags (no onclick / onsubmit): every handler is attached with addEventListener
//  - nothing sensitive (like passwords) is saved in the browser
(function () {
  'use strict';

  const MAX_INGREDIENTS = 30;

  // ---------- Small pop-up message (used instead of alert) ----------
  function toast(message) {
    const box = document.createElement('div');
    box.className = 'toast';
    box.setAttribute('role', 'status');
    box.textContent = message;
    document.body.appendChild(box);
    setTimeout(function () { box.remove(); }, 3500);
  }

  // ---------- Ask "Are you sure?" before forms that have data-confirm ----------
  document.addEventListener('submit', function (event) {
    const message = event.target.getAttribute('data-confirm');
    if (message && !window.confirm(message)) {
      event.preventDefault();
    }
  });

  // ---------- Register page: the two passwords must match ----------
  const password = document.getElementById('password');
  const confirmBox = document.getElementById('confirm');
  if (password && confirmBox && document.getElementById('register-form')) {
    const checkMatch = function () {
      confirmBox.setCustomValidity(confirmBox.value !== password.value ? 'Passwords do not match.' : '');
    };
    password.addEventListener('input', checkMatch);
    confirmBox.addEventListener('input', checkMatch);
  }

  // ---------- Recipe form: add and remove ingredient fields ----------
  const rowsBox = document.getElementById('ingredient-rows');
  const addButton = document.getElementById('add-ingredient');
  const template = document.getElementById('ingredient-template');
  const errorBox = document.getElementById('ingredient-error');
  const recipeForm = document.getElementById('recipe-form');

  function showIngredientError(message) {
    if (!errorBox) { return; }
    errorBox.textContent = message;
    errorBox.hidden = message === '';
  }

  if (rowsBox && addButton && template) {
    addButton.addEventListener('click', function () {
      if (rowsBox.querySelectorAll('.ingredient-row').length >= MAX_INGREDIENTS) {
        showIngredientError('A recipe can have at most ' + MAX_INGREDIENTS + ' ingredients.');
        return;
      }
      showIngredientError('');
      rowsBox.appendChild(template.content.cloneNode(true));
      const rows = rowsBox.querySelectorAll('.ingredient-row');
      rows[rows.length - 1].querySelector('input[name="ingredient_name[]"]').focus();
    });

    rowsBox.addEventListener('click', function (event) {
      const removeButton = event.target.closest('.remove-ingredient');
      if (!removeButton) { return; }
      const row = removeButton.closest('.ingredient-row');
      if (rowsBox.querySelectorAll('.ingredient-row').length <= 1) {
        // Keep at least one row on the screen: just clear it.
        row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
        return;
      }
      row.remove();
      showIngredientError('');
    });
  }

  if (recipeForm && rowsBox) {
    recipeForm.addEventListener('submit', function (event) {
      let filled = 0;     // let: these two change while we loop
      let problem = '';
      rowsBox.querySelectorAll('.ingredient-row').forEach(function (row) {
        const qty = row.querySelector('input[name="ingredient_qty[]"]').value.trim();
        const name = row.querySelector('input[name="ingredient_name[]"]').value.trim();
        if (name !== '') {
          filled++;
        } else if (qty !== '') {
          problem = 'Every ingredient needs a name.';
        }
      });
      if (filled === 0 && problem === '') {
        problem = 'Add at least one ingredient.';
      }
      if (problem !== '') {
        event.preventDefault();
        showIngredientError(problem);
        rowsBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  }

  // ---------- Save / un-save a favorite without reloading the page ----------
  function paintFavorite(recipeId, saved, count) {
    document.querySelectorAll('.fav-btn[data-recipe-id="' + recipeId + '"]').forEach(function (button) {
      button.classList.toggle('is-on', saved);
      button.setAttribute('aria-pressed', saved ? 'true' : 'false');
      button.querySelector('.fav-icon').innerHTML = saved ? '&#9829;' : '&#9825;';
      button.querySelector('.fav-label').textContent = saved ? 'Saved' : 'Save';
    });
    document.querySelectorAll('.save-count[data-recipe-id="' + recipeId + '"]').forEach(function (span) {
      span.textContent = count;
    });
  }

  document.addEventListener('click', function (event) {
    const button = event.target.closest('.fav-btn');
    if (!button || button.disabled) { return; }

    const recipeId = button.getAttribute('data-recipe-id');
    const body = new URLSearchParams();
    body.append('recipe_id', recipeId);
    button.disabled = true; // stops double clicks while the request is running

    fetch('favorite-toggle.php', { method: 'POST', body: body, credentials: 'same-origin' })
      .then(function (response) {
        return response.json().then(function (data) { return { status: response.status, data: data }; });
      })
      .then(function (result) {
        if (result.status === 401) {
          window.location.href = 'login.php';
          return;
        }
        if (!result.data.ok) {
          toast(result.data.message || 'Something went wrong.');
          return;
        }
        paintFavorite(recipeId, result.data.favorited, result.data.saves);

        // On the favorites page, un-saving removes the card right away.
        const grid = button.closest('[data-remove-on-unfav]');
        if (grid && !result.data.favorited) {
          const card = button.closest('[data-recipe-card]');
          if (card) { card.remove(); }
          if (!grid.querySelector('[data-recipe-card]')) {
            const empty = document.getElementById('empty-favorites');
            if (empty) { empty.hidden = false; }
          }
        }
      })
      .catch(function () {
        toast('Could not reach the server. Please try again.');
      })
      .then(function () {
        button.disabled = false;
      });
  });
})();