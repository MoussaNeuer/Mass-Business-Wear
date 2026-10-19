/**
 * MBW Admin — flash toasts auto-dismiss, confirmations, helpers.
 * Aucune dépendance.
 */
(function () {
  'use strict';

  /* ---------- Flash toasts ---------- */
  var stack = document.getElementById('admin-flash');
  if (stack) {
    var flashes = stack.querySelectorAll('.admin-flash');
    flashes.forEach(function (el, i) {
      setTimeout(function () {
        el.classList.add('is-leaving');
        setTimeout(function () { el.remove(); }, 320);
      }, 3200 + i * 180);
    });
    // Vide la pile après animation
    setTimeout(function () {
      if (stack && !stack.querySelector('.admin-flash')) stack.remove();
    }, 3200 + flashes.length * 180 + 400);
  }

  /* ---------- Confirmations de suppression ---------- */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.dataset.confirm) {
      if (!window.confirm(form.dataset.confirm)) {
        e.preventDefault();
        return;
      }
    }
    // Désactive le bouton pour éviter double submit
    var btn = form.querySelector('[type="submit"]');
    if (btn) {
      btn.disabled = true;
      btn.dataset.originalText = btn.textContent;
      btn.textContent = btn.dataset.loading || '…';
      setTimeout(function () {
        if (document.contains(btn)) {
          btn.disabled = false;
          btn.textContent = btn.dataset.originalText;
        }
      }, 4000);
    }
  });

  /* ---------- Confirmation boutons data-confirm (click) ---------- */
  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-confirm-click]');
    if (el) {
      var msg = el.getAttribute('data-confirm-click');
      if (msg && !window.confirm(msg)) {
        e.preventDefault();
        e.stopPropagation();
      }
    }
  });

  /* ---------- Confirmation formulaires data-confirm (submit) ---------- */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.tagName !== 'FORM' || !form.hasAttribute('data-confirm')) return;
    var msg = form.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) {
      e.preventDefault();
      e.stopPropagation();
    }
  });

  /* ---------- Filtres admin : submit auto au changement ---------- */
  document.querySelectorAll('[data-auto-submit]').forEach(function (el) {
    el.addEventListener('change', function () {
      var form = el.form || el.closest('form');
      if (form) form.submit();
    });
  });

  /* ---------- Recherche : submit au debounce via Enter ---------- */
  document.querySelectorAll('[data-admin-search]').forEach(function (input) {
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        var form = input.form || input.closest('form');
        if (form) form.submit();
      }
    });
  });

  /* ---------- Aperçu image upload ---------- */
  document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
    input.addEventListener('change', function () {
      var target = document.querySelector(input.getAttribute('data-preview'));
      if (!target) return;
      var file = input.files && input.files[0];
      if (!file) { target.style.display = 'none'; return; }
      var url = URL.createObjectURL(file);
      target.src = url;
      target.style.display = 'block';
    });
  });

  /* ---------- Smooth scroll ancres (zones) ---------- */
  if (location.hash) {
    var target = document.querySelector(location.hash);
    if (target) {
      setTimeout(function () {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 100);
    }
  }
})();
