/* MASS BUSINESS WEAR — Hero cinématique
   Cascade entre slides, swipe tactile, auto-play, reduced-motion */
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
  var AUTO_MS = 6000;

  function setCopy(i) {
    if (titleEl) titleEl.textContent = titles[i] || ('Look ' + (i + 1));
    if (tagEl) tagEl.textContent = tags[i] || '';
    if (indexEl) indexEl.textContent = String(i + 1);
  }

  function go(next) {
    if (locked || next === current || next < 0 || next >= slides.length) return;
    locked = true;

    var prev = current;
    current = next;

    // Sortie cascade de l'ancien
    slides[prev].classList.remove('is-active', 'is-entering');
    slides[prev].classList.add('is-leaving');
    slides[prev].setAttribute('aria-hidden', 'true');

    // Entrée cascade du nouveau
    var el = slides[current];
    el.classList.remove('is-leaving', 'is-entering');
    // force reflow pour relancer l'animation
    void el.offsetWidth;
    el.classList.add('is-entering', 'is-active');
    el.removeAttribute('aria-hidden');

    window.setTimeout(function () {
      el.classList.remove('is-entering');
      slides[prev].classList.remove('is-leaving');
      locked = false;
    }, reduced ? 280 : 960);

    dots.forEach(function (d, i) {
      var on = i === current;
      d.classList.toggle('is-active', on);
      d.setAttribute('aria-selected', on ? 'true' : 'false');
    });

    setCopy(current);

    if (!reduced) {
      root.classList.remove('is-recascade');
      void root.offsetWidth;
      root.classList.add('is-recascade');
      window.setTimeout(function () {
        root.classList.remove('is-recascade');
      }, 1000);
    }

    preload(current + 1);
    preload(current - 1);
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
  var axis = null;

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
    if (axis !== 'x') { startAuto(); return; }

    var dx = e.clientX - startX;
    var threshold = Math.min(72, root.clientWidth * 0.16);

    if (dx <= -threshold) {
      go(current + 1 < slides.length ? current + 1 : 0);
    } else if (dx >= threshold) {
      go(current - 1 >= 0 ? current - 1 : slides.length - 1);
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
  root.addEventListener('dragstart', function (e) { e.preventDefault(); });

  // ---------------------------------------------------------- dots
  dots.forEach(function (d) {
    d.addEventListener('click', function () {
      var i = parseInt(d.getAttribute('data-dot'), 10);
      if (!isNaN(i)) {
        go(i);
        stopAuto();
        startAuto();
      }
    });
  });

  // ---------------------------------------------------------- clavier
  root.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowRight') {
      e.preventDefault();
      go(current + 1 < slides.length ? current + 1 : 0);
      stopAuto();
      startAuto();
    } else if (e.key === 'ArrowLeft') {
      e.preventDefault();
      go(current - 1 >= 0 ? current - 1 : slides.length - 1);
      stopAuto();
      startAuto();
    }
  });

  // ---------------------------------------------------------- auto-play
  function startAuto() {
    if (reduced || slides.length < 2) return;
    stopAuto();
    autoTimer = window.setInterval(function () {
      go(current + 1 < slides.length ? current + 1 : 0);
    }, AUTO_MS);
  }

  function stopAuto() {
    if (autoTimer) {
      window.clearInterval(autoTimer);
      autoTimer = null;
    }
  }

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
  requestAnimationFrame(function () {
    requestAnimationFrame(function () {
      root.classList.add('is-ready');
    });
  });

  preload(1);

  root.addEventListener('touchstart', stopAuto, { passive: true });
  root.addEventListener('touchend', function () { startAuto(); }, { passive: true });
})();
