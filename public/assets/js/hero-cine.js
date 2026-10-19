/* MASS BUSINESS WEAR — Hero cinématique
   Swipe tactile, auto-play discret, cascade texte, prefers-reduced-motion */
(function () {
  'use strict';

  var root = document.querySelector('[data-hero-cine]');
  if (!root) return;

  var slides = Array.prototype.slice.call(root.querySelectorAll('[data-slide]'));
  var dots = Array.prototype.slice.call(root.querySelectorAll('[data-dot]'));
  var titleEl = root.querySelector('[data-hero-title]');
  var tagEl = root.querySelector('[data-hero-tag]');
  var indexEl = root.querySelector('[data-hero-index]');
  if (!slides.length) return;

  // Textes par slide (data-title / data-tag sur <figure>)
  var titles = [];
  var tags = [];
  slides.forEach(function (s, i) {
    titles[i] = s.getAttribute('data-title') || ('Look ' + (i + 1));
    tags[i] = s.getAttribute('data-tag') || '';
  });

  var current = 0;
  var locked = false;
  var autoTimer = null;
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var AUTO_MS = 5500;

  // ---------------------------------------------------------- helpers
  function pad(n) {
    return n < 10 ? '0' + n : String(n);
  }

  function setCopy(i) {
    if (titleEl) titleEl.textContent = titles[i] || ('Look ' + (i + 1));
    if (tagEl) tagEl.textContent = tags[i] || '';
    if (indexEl) indexEl.textContent = String(i + 1);
  }

  function go(next, dir) {
    if (locked || next === current || next < 0 || next >= slides.length) return;
    locked = true;

    var prev = current;
    current = next;

    slides[prev].classList.remove('is-active');
    slides[prev].classList.add('is-leaving');
    slides[prev].setAttribute('aria-hidden', 'true');

    slides[current].classList.add('is-active');
    slides[current].classList.remove('is-leaving');
    slides[current].removeAttribute('aria-hidden');

    // Nettoyer l'état leaving après la transition
    window.setTimeout(function () {
      slides[prev].classList.remove('is-leaving');
      locked = false;
    }, reduced ? 220 : 880);

    dots.forEach(function (d, i) {
      var on = i === current;
      d.classList.toggle('is-active', on);
      d.setAttribute('aria-selected', on ? 'true' : 'false');
    });

    setCopy(current);

    // Re-cascade typographique
    if (!reduced) {
      root.classList.remove('is-recascade');
      // force reflow
      void root.offsetWidth;
      root.classList.add('is-recascade');
      window.setTimeout(function () {
        root.classList.remove('is-recascade');
      }, 900);
    }

    preload(current + (dir === 'prev' ? -1 : 1));
  }

  function preload(i) {
    if (i < 0 || i >= slides.length) return;
    var img = slides[i].querySelector('img');
    if (img && !img.complete) {
      var pre = new Image();
      pre.src = img.currentSrc || img.src;
    }
  }

  // ---------------------------------------------------------- swipe
  var startX = 0;
  var startY = 0;
  var tracking = false;
  var axis = null; // 'x' | 'y'

  function onPointerDown(e) {
    if (e.pointerType === 'mouse' && e.button !== 0) return;
    tracking = true;
    axis = null;
    startX = e.clientX;
    startY = e.clientY;
    stopAuto();
  }

  function onPointerMove(e) {
    if (!tracking) return;
    var dx = e.clientX - startX;
    var dy = e.clientY - startY;
    if (axis === null) {
      if (Math.abs(dx) < 8 && Math.abs(dy) < 8) return;
      axis = Math.abs(dx) > Math.abs(dy) ? 'x' : 'y';
      if (axis === 'x' && root.setPointerCapture) {
        try { root.setPointerCapture(e.pointerId); } catch (err) { /* noop */ }
      }
    }
  }

  function onPointerUp(e) {
    if (!tracking) return;
    tracking = false;
    if (axis !== 'x') return;

    var dx = e.clientX - startX;
    var threshold = Math.min(72, root.clientWidth * 0.18);

    if (dx <= -threshold) {
      go(current + 1 < slides.length ? current + 1 : 0, 'next');
    } else if (dx >= threshold) {
      go(current - 1 >= 0 ? current - 1 : slides.length - 1, 'prev');
    }
    startAuto();
  }

  function onPointerCancel() {
    tracking = false;
    axis = null;
    startAuto();
  }

  root.addEventListener('pointerdown', onPointerDown, { passive: true });
  root.addEventListener('pointermove', onPointerMove, { passive: true });
  root.addEventListener('pointerup', onPointerUp, { passive: true });
  root.addEventListener('pointercancel', onPointerCancel, { passive: true });

  // Empêcher le drag d'image natif
  root.addEventListener('dragstart', function (e) { e.preventDefault(); });

  // ---------------------------------------------------------- dots
  dots.forEach(function (d) {
    d.addEventListener('click', function () {
      var i = parseInt(d.getAttribute('data-dot'), 10);
      if (!isNaN(i)) {
        go(i, i > current ? 'next' : 'prev');
        stopAuto();
        startAuto();
      }
    });
  });

  // ---------------------------------------------------------- clavier
  root.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowRight') {
      e.preventDefault();
      go(current + 1 < slides.length ? current + 1 : 0, 'next');
      stopAuto();
      startAuto();
    } else if (e.key === 'ArrowLeft') {
      e.preventDefault();
      go(current - 1 >= 0 ? current - 1 : slides.length - 1, 'prev');
      stopAuto();
      startAuto();
    }
  });

  // ---------------------------------------------------------- auto-play discret
  function startAuto() {
    if (reduced || slides.length < 2) return;
    stopAuto();
    autoTimer = window.setInterval(function () {
      go(current + 1 < slides.length ? current + 1 : 0, 'next');
    }, AUTO_MS);
  }

  function stopAuto() {
    if (autoTimer) {
      window.clearInterval(autoTimer);
      autoTimer = null;
    }
  }

  // Pause hors écran
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) startAuto();
        else stopAuto();
      });
    }, { threshold: 0.25 });
    io.observe(root);
  }

  // ---------------------------------------------------------- boot
  // Marquer prêt pour déclencher la cascade CSS
  requestAnimationFrame(function () {
    requestAnimationFrame(function () {
      root.classList.add('is-ready');
    });
  });

  // Précharger le 2e visuel seulement (perf mobile)
  preload(1);

  // Pause auto sur interaction tactile longue
  root.addEventListener('touchstart', stopAuto, { passive: true });
  root.addEventListener('touchend', function () { startAuto(); }, { passive: true });
})();
