/**
 * MBW — UI : toasts, bottom sheets, debounce.
 * Aucune dépendance. Exporte window.MBW.ui
 */
(function () {
  'use strict';

  var stack = null;

  function ensureStack() {
    if (!stack) {
      stack = document.createElement('div');
      stack.className = 'toast-stack';
      stack.setAttribute('role', 'status');
      stack.setAttribute('aria-live', 'polite');
      document.body.appendChild(stack);
    }
    return stack;
  }

  function toast(message, type) {
    var el = document.createElement('div');
    el.className = 'toast toast--' + (type || 'ok');
    el.textContent = message;
    ensureStack().appendChild(el);
    setTimeout(function () {
      el.style.opacity = '0';
      el.style.transition = 'opacity .2s';
      setTimeout(function () { el.remove(); }, 220);
    }, 2600);
  }

  /* ---------------- Bottom sheets ---------------- */

  var openSheetEl = null;
  var backdrop = null;

  function ensureBackdrop() {
    if (!backdrop) {
      backdrop = document.createElement('div');
      backdrop.className = 'sheet-backdrop';
      backdrop.addEventListener('click', closeSheet);
      document.body.appendChild(backdrop);
    }
    return backdrop;
  }

  function openSheet(idOrEl) {
    var el = typeof idOrEl === 'string' ? document.getElementById(idOrEl) : idOrEl;
    if (!el) return;
    closeSheet();
    ensureBackdrop().classList.add('is-open');
    el.classList.add('is-open');
    el.removeAttribute('hidden');
    openSheetEl = el;
    document.body.style.overflow = 'hidden';
  }

  function closeSheet() {
    if (backdrop) backdrop.classList.remove('is-open');
    if (openSheetEl) {
      openSheetEl.classList.remove('is-open');
      openSheetEl = null;
    }
    document.body.style.overflow = '';
  }

  /* ---------------- Helpers ---------------- */

  function debounce(fn, delay) {
    var t;
    return function () {
      var args = arguments, self = this;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(self, args); }, delay || 300);
    };
  }

  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSheet();
  });

  window.MBW = window.MBW || {};
  window.MBW.ui = {
    toast: toast,
    openSheet: openSheet,
    closeSheet: closeSheet,
    debounce: debounce,
    csrfToken: csrfToken
  };
})();
